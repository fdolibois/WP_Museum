<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers all museum sidebar widgets.
 */
class VM_Widgets {

    public function __construct() {
        add_action( 'widgets_init', [ $this, 'register' ] );
    }

    public function register(): void {
        register_widget( 'VM_Museum_Nav_Widget' );
        register_widget( 'VM_Context_Nav_Widget' );
        register_widget( 'VM_Recent_Objects_Widget' );
        register_widget( 'VM_Museum_Stats_Widget' );
        register_widget( 'VM_Museum_Search_Widget' );
    }
}


/* ============================================================
   Widget 1: Museum Navigation (Raumübersicht)
   ============================================================ */
class VM_Museum_Nav_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vm_museum_nav',
            __( 'VM: Museumsnavigation', 'vmuseum' ),
            [ 'description' => __( 'Zeigt alle Museumsräume als Navigation.', 'vmuseum' ) ]
        );
    }

    public function widget( $args, $instance ): void {
        $title     = apply_filters( 'widget_title', $instance['title'] ?? __( 'Ausstellungsräume', 'vmuseum' ) );
        $show_count = ! empty( $instance['show_count'] );

        $rooms = get_posts( [
            'post_type'      => 'museum_room',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ] );

        if ( ! $rooms ) return;

        $current_room_id = is_singular( 'museum_room' ) ? get_the_ID() : 0;

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        echo '<nav class="vm-widget-nav" aria-label="' . esc_attr__( 'Ausstellungsräume', 'vmuseum' ) . '"><ul class="vm-widget-nav__list">';

        foreach ( $rooms as $room ) {
            $color   = get_post_meta( $room->ID, 'vm_room_color', true ) ?: '#8B4513';
            $active  = $current_room_id === $room->ID ? ' vm-widget-nav__item--active' : '';
            $url     = get_permalink( $room->ID );

            echo '<li class="vm-widget-nav__item' . esc_attr( $active ) . '" style="--vm-item-color:' . esc_attr( $color ) . '">';
            echo '<a href="' . esc_url( $url ) . '" class="vm-widget-nav__link">';
            echo '<span class="vm-widget-nav__dot" aria-hidden="true"></span>';
            echo esc_html( get_the_title( $room ) );

            if ( $show_count ) {
                $total = count( VM_Relations::get_children( 'room', $room->ID, 'object', true ) );
                if ( $total ) {
                    echo ' <span class="vm-widget-nav__count">(' . esc_html( $total ) . ')</span>';
                }
            }

            echo '</a>';

            // Show sub-items (vitrines + galleries) only when viewing this room
            if ( $current_room_id === $room->ID ) {
                $vitrines  = VM_Relations::get_vitrines( $room->ID );
                $galleries = VM_Relations::get_galleries( 'room', $room->ID );

                if ( $vitrines || $galleries ) {
                    echo '<ul class="vm-widget-nav__sub">';
                    foreach ( $vitrines as $v ) {
                        echo '<li><a href="' . esc_url( get_permalink( $v->ID ) ) . '">🗄️ ' . esc_html( get_the_title( $v ) ) . '</a></li>';
                    }
                    foreach ( $galleries as $g ) {
                        echo '<li><a href="' . esc_url( get_permalink( $g->ID ) ) . '">🖼️ ' . esc_html( get_the_title( $g ) ) . '</a></li>';
                    }
                    echo '</ul>';
                }
            }

            echo '</li>';
        }

        echo '</ul></nav>';
        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title      = $instance['title']      ?? __( 'Ausstellungsräume', 'vmuseum' );
        $show_count = ! empty( $instance['show_count'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Titel:', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label>
                <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_count' ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'show_count' ) ); ?>" value="1"
                       <?php checked( $show_count ); ?>>
                <?php esc_html_e( 'Objektanzahl anzeigen', 'vmuseum' ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new, $old ): array {
        return [
            'title'      => sanitize_text_field( $new['title'] ?? '' ),
            'show_count' => ! empty( $new['show_count'] ) ? 1 : 0,
        ];
    }
}


/* ============================================================
   Widget 2: Context Navigation (Raum-Inhalt / Objekt-Kontexte)
   ============================================================ */
class VM_Context_Nav_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vm_context_nav',
            __( 'VM: Kontext-Navigation', 'vmuseum' ),
            [ 'description' => __( 'Zeigt kontextabhängige Navigation: Vitrinen/Galerien im aktuellen Raum oder alle Fundorte eines Objekts.', 'vmuseum' ) ]
        );
    }

    public function widget( $args, $instance ): void {
        if ( is_singular( 'museum_room' ) ) {
            $this->render_room_context( $args, $instance );
        } elseif ( is_singular( 'museum_object' ) ) {
            $this->render_object_context( $args, $instance );
        } elseif ( is_singular( 'museum_vitrine' ) ) {
            $this->render_vitrine_context( $args, $instance );
        } elseif ( is_singular( 'museum_gallery' ) ) {
            $this->render_gallery_context( $args, $instance );
        }
    }

    private function render_room_context( $args, $instance ): void {
        $room_id   = get_the_ID();
        $vitrines  = VM_Relations::get_vitrines( $room_id );
        $galleries = VM_Relations::get_galleries( 'room', $room_id );

        if ( ! $vitrines && ! $galleries ) return;

        $title = $instance['title'] ?? get_the_title( $room_id );

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        if ( $vitrines ) {
            echo '<p class="vm-widget-sub-label">🗄️ ' . esc_html__( 'Vitrinen', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $vitrines as $v ) {
                $obj_count = count( VM_Relations::get_objects( 'vitrine', $v->ID ) );
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $v->ID ) ) . '" class="vm-widget-nav__link">'
                    . esc_html( get_the_title( $v ) );
                if ( $obj_count ) echo ' <span class="vm-widget-nav__count">(' . esc_html( $obj_count ) . ')</span>';
                echo '</a></li>';
            }
            echo '</ul>';
        }

        if ( $galleries ) {
            echo '<p class="vm-widget-sub-label">🖼️ ' . esc_html__( 'Galerien', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $galleries as $g ) {
                $obj_count = count( VM_Relations::get_objects( 'gallery', $g->ID ) );
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $g->ID ) ) . '" class="vm-widget-nav__link">'
                    . esc_html( get_the_title( $g ) );
                if ( $obj_count ) echo ' <span class="vm-widget-nav__count">(' . esc_html( $obj_count ) . ')</span>';
                echo '</a></li>';
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }

    private function render_object_context( $args, $instance ): void {
        $obj_id  = get_the_ID();
        $rooms   = VM_Relations::get_all_rooms_for( 'object', $obj_id );
        $vitrines = VM_Relations::get_parents( 'object', $obj_id, 'vitrine' );
        $galleries = VM_Relations::get_parents( 'object', $obj_id, 'gallery' );

        if ( ! $rooms && ! $vitrines && ! $galleries ) return;

        $title = $instance['title'] ?? __( 'Dieses Objekt in:', 'vmuseum' );

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        if ( $rooms ) {
            echo '<p class="vm-widget-sub-label">🚪 ' . esc_html__( 'Räume', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $rooms as $r ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $r->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $r ) ) . '</a></li>';
            }
            echo '</ul>';
        }
        if ( $vitrines ) {
            echo '<p class="vm-widget-sub-label">🗄️ ' . esc_html__( 'Vitrinen', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $vitrines as $v ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $v->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $v ) ) . '</a></li>';
            }
            echo '</ul>';
        }
        if ( $galleries ) {
            echo '<p class="vm-widget-sub-label">🖼️ ' . esc_html__( 'Galerien', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $galleries as $g ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $g->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $g ) ) . '</a></li>';
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }

    private function render_vitrine_context( $args, $instance ): void {
        $vitrine_id  = get_the_ID();
        $parent_rooms = VM_Relations::get_parents( 'vitrine', $vitrine_id, 'room' );
        $galleries    = VM_Relations::get_galleries( 'vitrine', $vitrine_id );

        if ( ! $parent_rooms && ! $galleries ) return;

        $title = $instance['title'] ?? __( 'In dieser Vitrine', 'vmuseum' );

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        if ( $parent_rooms ) {
            echo '<p class="vm-widget-sub-label">🚪 ' . esc_html__( 'Zugehörige Räume', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $parent_rooms as $r ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $r->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $r ) ) . '</a></li>';
            }
            echo '</ul>';
        }
        if ( $galleries ) {
            echo '<p class="vm-widget-sub-label">🖼️ ' . esc_html__( 'Galerien', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $galleries as $g ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $g->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $g ) ) . '</a></li>';
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }

    private function render_gallery_context( $args, $instance ): void {
        $gallery_id  = get_the_ID();
        $room_parents = VM_Relations::get_parents( 'gallery', $gallery_id, 'room' );
        $vit_parents  = VM_Relations::get_parents( 'gallery', $gallery_id, 'vitrine' );

        if ( ! $room_parents && ! $vit_parents ) return;

        $title = $instance['title'] ?? __( 'Galerie-Kontext', 'vmuseum' );

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        if ( $room_parents ) {
            echo '<p class="vm-widget-sub-label">🚪 ' . esc_html__( 'Räume', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $room_parents as $r ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $r->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $r ) ) . '</a></li>';
            }
            echo '</ul>';
        }
        if ( $vit_parents ) {
            echo '<p class="vm-widget-sub-label">🗄️ ' . esc_html__( 'Vitrinen', 'vmuseum' ) . '</p>';
            echo '<ul class="vm-widget-nav__list">';
            foreach ( $vit_parents as $v ) {
                echo '<li class="vm-widget-nav__item"><a href="' . esc_url( get_permalink( $v->ID ) ) . '" class="vm-widget-nav__link">' . esc_html( get_the_title( $v ) ) . '</a></li>';
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title = $instance['title'] ?? '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Titel (leer = automatisch):', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p class="description"><?php esc_html_e( 'Der Inhalt wird automatisch je nach aktueller Seite angepasst.', 'vmuseum' ); ?></p>
        <?php
    }

    public function update( $new, $old ): array {
        return [ 'title' => sanitize_text_field( $new['title'] ?? '' ) ];
    }
}


/* ============================================================
   Widget 3: Neueste Objekte
   ============================================================ */
class VM_Recent_Objects_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vm_recent_objects',
            __( 'VM: Neueste Objekte', 'vmuseum' ),
            [ 'description' => __( 'Zeigt die zuletzt hinzugefügten Museumsobjekte als Linkliste.', 'vmuseum' ) ]
        );
    }

    public function widget( $args, $instance ): void {
        $title        = apply_filters( 'widget_title', $instance['title'] ?? __( 'Neueste Objekte', 'vmuseum' ) );
        $count        = max( 1, (int) ( $instance['count'] ?? 5 ) );
        $show_excerpt = ! empty( $instance['show_excerpt'] );

        $posts = get_posts( [
            'post_type'      => 'museum_object',
            'post_status'    => 'publish',
            'posts_per_page' => $count,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        if ( ! $posts ) return;

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        echo '<ul class="vm-widget-nav__list">';
        foreach ( $posts as $post ) {
            echo '<li class="vm-widget-nav__item">';
            echo '<a href="' . esc_url( get_permalink( $post->ID ) ) . '" class="vm-widget-nav__link">';
            if ( has_post_thumbnail( $post->ID ) ) {
                echo '<span class="vm-widget-recent__thumb">' . get_the_post_thumbnail( $post->ID, [ 40, 40 ] ) . '</span>';
            }
            echo '<span>';
            echo '<span class="vm-widget-nav__link-title">' . esc_html( get_the_title( $post ) ) . '</span>';
            $year = get_post_meta( $post->ID, 'vm_year', true );
            if ( $year ) echo '<span class="vm-widget-nav__count"> · ' . esc_html( $year ) . '</span>';
            if ( $show_excerpt && $post->post_excerpt ) {
                echo '<br><small style="color:var(--vm-color-text-muted,#666)">' . esc_html( wp_trim_words( $post->post_excerpt, 8 ) ) . '</small>';
            }
            echo '</span></a></li>';
        }
        echo '</ul>';

        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title        = $instance['title']        ?? __( 'Neueste Objekte', 'vmuseum' );
        $count        = $instance['count']        ?? 5;
        $show_excerpt = ! empty( $instance['show_excerpt'] );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Titel:', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Anzahl:', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
                   type="number" min="1" max="20" value="<?php echo esc_attr( $count ); ?>">
        </p>
        <p>
            <label>
                <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_excerpt' ) ); ?>"
                       name="<?php echo esc_attr( $this->get_field_name( 'show_excerpt' ) ); ?>" value="1"
                       <?php checked( $show_excerpt ); ?>>
                <?php esc_html_e( 'Beschreibung anzeigen', 'vmuseum' ); ?>
            </label>
        </p>
        <?php
    }

    public function update( $new, $old ): array {
        return [
            'title'        => sanitize_text_field( $new['title'] ?? '' ),
            'count'        => max( 1, (int) ( $new['count'] ?? 5 ) ),
            'show_excerpt' => ! empty( $new['show_excerpt'] ) ? 1 : 0,
        ];
    }
}


/* ============================================================
   Widget 4: Museum Statistiken
   ============================================================ */
class VM_Museum_Stats_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vm_museum_stats',
            __( 'VM: Statistiken', 'vmuseum' ),
            [ 'description' => __( 'Zeigt die Anzahl der Räume, Vitrinen, Galerien und Objekte.', 'vmuseum' ) ]
        );
    }

    public function widget( $args, $instance ): void {
        $title = apply_filters( 'widget_title', $instance['title'] ?? __( 'Sammlung', 'vmuseum' ) );

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        $stats = [];
        if ( ! empty( $instance['show_rooms'] ) )     $stats[] = [ '🚪', wp_count_posts( 'museum_room' )->publish,    __( 'Räume', 'vmuseum' ) ];
        if ( ! empty( $instance['show_vitrines'] ) )  $stats[] = [ '🗄️', wp_count_posts( 'museum_vitrine' )->publish, __( 'Vitrinen', 'vmuseum' ) ];
        if ( ! empty( $instance['show_galleries'] ) ) $stats[] = [ '🖼️', wp_count_posts( 'museum_gallery' )->publish, __( 'Galerien', 'vmuseum' ) ];
        if ( ! empty( $instance['show_objects'] ) )   $stats[] = [ '🎨', wp_count_posts( 'museum_object' )->publish,  __( 'Objekte', 'vmuseum' ) ];

        if ( ! $stats ) {
            echo $args['after_widget'];
            return;
        }

        echo '<ul style="list-style:none;margin:0;padding:0;">';
        foreach ( $stats as [ $icon, $count, $label ] ) {
            echo '<li style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee">';
            echo '<span>' . $icon . ' ' . esc_html( $label ) . '</span>';
            echo '<strong>' . esc_html( $count ) . '</strong>';
            echo '</li>';
        }
        echo '</ul>';

        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title          = $instance['title']          ?? __( 'Sammlung', 'vmuseum' );
        // B024: Korrektur – false branch muss 0 zurückgeben (bisher immer 1)
        $show_rooms     = ! empty( $instance['show_rooms'] )     ? 1 : 0;
        $show_vitrines  = ! empty( $instance['show_vitrines'] )  ? 1 : 0;
        $show_galleries = ! empty( $instance['show_galleries'] ) ? 1 : 0;
        $show_objects   = ! empty( $instance['show_objects'] )   ? 1 : 0;
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Titel:', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php foreach ( [
            'show_rooms'     => __( 'Räume', 'vmuseum' ),
            'show_vitrines'  => __( 'Vitrinen', 'vmuseum' ),
            'show_galleries' => __( 'Galerien', 'vmuseum' ),
            'show_objects'   => __( 'Objekte', 'vmuseum' ),
        ] as $key => $label ) : ?>
        <p>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( $key ) ); ?>" value="1"
                       <?php checked( ! empty( $instance[ $key ] ) || ! isset( $instance[ $key ] ) ); ?>>
                <?php echo esc_html( $label ); ?>
            </label>
        </p>
        <?php endforeach; ?>
        <?php
    }

    public function update( $new, $old ): array {
        return [
            'title'          => sanitize_text_field( $new['title'] ?? '' ),
            'show_rooms'     => ! empty( $new['show_rooms'] )     ? 1 : 0,
            'show_vitrines'  => ! empty( $new['show_vitrines'] )  ? 1 : 0,
            'show_galleries' => ! empty( $new['show_galleries'] ) ? 1 : 0,
            'show_objects'   => ! empty( $new['show_objects'] )   ? 1 : 0,
        ];
    }
}


/* ============================================================
   Widget 5: Museum Suche
   ============================================================ */
class VM_Museum_Search_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vm_museum_search',
            __( 'VM: Suche', 'vmuseum' ),
            [ 'description' => __( 'Live-Suchfeld für das Virtuelle Museum.', 'vmuseum' ) ]
        );
    }

    public function widget( $args, $instance ): void {
        $title       = apply_filters( 'widget_title', $instance['title'] ?? '' );
        $placeholder = $instance['placeholder'] ?? __( 'Museum durchsuchen …', 'vmuseum' );

        echo $args['before_widget'];
        if ( $title ) echo $args['before_title'] . esc_html( $title ) . $args['after_title'];

        echo '<div style="position:relative">';
        echo '<input type="search" id="vm-live-search" style="width:100%;padding:8px 36px 8px 12px;border:1px solid #ddd;border-radius:2rem;box-sizing:border-box;font-size:.9rem"';
        echo ' placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off">';
        echo '<span style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none">🔍</span>';
        echo '</div>';
        echo '<div id="vm-search-results" style="position:relative;z-index:9999" hidden></div>';

        echo $args['after_widget'];
    }

    public function form( $instance ): void {
        $title       = $instance['title']       ?? '';
        $placeholder = $instance['placeholder'] ?? __( 'Museum durchsuchen …', 'vmuseum' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Titel (optional):', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>"><?php esc_html_e( 'Platzhaltertext:', 'vmuseum' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>"
                   name="<?php echo esc_attr( $this->get_field_name( 'placeholder' ) ); ?>"
                   type="text" value="<?php echo esc_attr( $placeholder ); ?>">
        </p>
        <?php
    }

    public function update( $new, $old ): array {
        return [
            'title'       => sanitize_text_field( $new['title']       ?? '' ),
            'placeholder' => sanitize_text_field( $new['placeholder'] ?? '' ),
        ];
    }
}
