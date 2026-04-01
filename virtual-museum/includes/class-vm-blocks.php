<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers all Gutenberg (block editor) blocks for the Virtual Museum.
 * All blocks are dynamic – rendering happens server-side via PHP callbacks.
 */
class VM_Blocks {

    public function __construct() {
        add_action( 'init',                      [ $this, 'register_blocks' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
        add_filter( 'block_categories_all',      [ $this, 'register_category' ], 10, 2 );
    }

    // =========================================================
    // Block Category
    // =========================================================

    public function register_category( array $categories, $post ): array {
        return array_merge( [
            [
                'slug'  => 'virtual-museum',
                'title' => __( 'Virtuelles Museum', 'vmuseum' ),
                'icon'  => null,
            ],
        ], $categories );
    }

    // =========================================================
    // Register Blocks
    // =========================================================

    public function register_blocks(): void {
        if ( ! function_exists( 'register_block_type' ) ) return;

        $blocks = [
            'museum-entrance'  => [ 'render' => [ $this, 'render_museum_entrance' ],  'attrs' => [] ],
            'room-grid'        => [ 'render' => [ $this, 'render_room_grid' ],        'attrs' => $this->attrs_room_grid() ],
            'single-room'      => [ 'render' => [ $this, 'render_single_room' ],      'attrs' => $this->attrs_single_room() ],
            'object-grid'      => [ 'render' => [ $this, 'render_object_grid' ],      'attrs' => $this->attrs_object_grid() ],
            'recent-objects'   => [ 'render' => [ $this, 'render_recent_objects' ],   'attrs' => $this->attrs_recent_objects() ],
            'vitrine-objects'  => [ 'render' => [ $this, 'render_vitrine_objects' ],  'attrs' => $this->attrs_vitrine_objects() ],
            'gallery-objects'  => [ 'render' => [ $this, 'render_gallery_objects' ],  'attrs' => $this->attrs_gallery_objects() ],
            'museum-stats'     => [ 'render' => [ $this, 'render_museum_stats' ],     'attrs' => $this->attrs_museum_stats() ],
            'museum-search'    => [ 'render' => [ $this, 'render_museum_search' ],    'attrs' => $this->attrs_museum_search() ],
            'museum-nav'       => [ 'render' => [ $this, 'render_museum_nav' ],       'attrs' => $this->attrs_museum_nav() ],
        ];

        foreach ( $blocks as $name => $cfg ) {
            register_block_type( 'vm/' . $name, [
                'render_callback' => $cfg['render'],
                'attributes'      => $cfg['attrs'],
            ] );
        }
    }

    // =========================================================
    // Attribute Schemas
    // =========================================================

    private function attrs_room_grid(): array {
        return [
            'columns'    => [ 'type' => 'number',  'default' => 3 ],
            'show_count' => [ 'type' => 'boolean', 'default' => true ],
            'limit'      => [ 'type' => 'number',  'default' => 0 ],
        ];
    }

    private function attrs_single_room(): array {
        return [
            'room_id' => [ 'type' => 'number', 'default' => 0 ],
        ];
    }

    private function attrs_object_grid(): array {
        return [
            'parent_type' => [ 'type' => 'string', 'default' => '' ],
            'parent_id'   => [ 'type' => 'number', 'default' => 0 ],
            'per_page'    => [ 'type' => 'number', 'default' => 12 ],
            'orderby'     => [ 'type' => 'string', 'default' => 'date' ],
            'columns'     => [ 'type' => 'number', 'default' => 4 ],
        ];
    }

    private function attrs_recent_objects(): array {
        return [
            'count'   => [ 'type' => 'number', 'default' => 8 ],
            'columns' => [ 'type' => 'number', 'default' => 4 ],
        ];
    }

    private function attrs_vitrine_objects(): array {
        return [
            'vitrine_id' => [ 'type' => 'number', 'default' => 0 ],
            'per_page'   => [ 'type' => 'number', 'default' => 12 ],
        ];
    }

    private function attrs_gallery_objects(): array {
        return [
            'gallery_id' => [ 'type' => 'number', 'default' => 0 ],
            'per_page'   => [ 'type' => 'number', 'default' => 12 ],
        ];
    }

    private function attrs_museum_stats(): array {
        return [
            'show_rooms'     => [ 'type' => 'boolean', 'default' => true ],
            'show_vitrines'  => [ 'type' => 'boolean', 'default' => true ],
            'show_galleries' => [ 'type' => 'boolean', 'default' => true ],
            'show_objects'   => [ 'type' => 'boolean', 'default' => true ],
        ];
    }

    private function attrs_museum_search(): array {
        return [
            'placeholder' => [ 'type' => 'string', 'default' => '' ],
        ];
    }

    private function attrs_museum_nav(): array {
        return [
            'show_count' => [ 'type' => 'boolean', 'default' => true ],
            'show_sub'   => [ 'type' => 'boolean', 'default' => true ],
        ];
    }

    // =========================================================
    // Editor Assets
    // =========================================================

    public function enqueue_editor_assets(): void {
        wp_enqueue_script(
            'vm-blocks',
            VM_PLUGIN_URL . 'admin/assets/vm-blocks.js',
            [ 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-server-side-render', 'wp-i18n' ],
            VM_PLUGIN_VERSION,
            true
        );

        // Pass all rooms, vitrines, galleries for dropdowns in the editor
        wp_localize_script( 'vm-blocks', 'vmBlocksData', [
            'rooms'    => $this->get_select_options( 'museum_room' ),
            'vitrines' => $this->get_select_options( 'museum_vitrine' ),
            'galleries'=> $this->get_select_options( 'museum_gallery' ),
        ] );

        wp_enqueue_style( 'vm-blocks-editor', VM_PLUGIN_URL . 'admin/assets/vm-blocks-editor.css', [], VM_PLUGIN_VERSION );
    }

    private function get_select_options( string $post_type ): array {
        $posts = get_posts( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $options = [ [ 'value' => 0, 'label' => __( '— Bitte wählen —', 'vmuseum' ) ] ];
        foreach ( $posts as $post ) {
            $options[] = [ 'value' => $post->ID, 'label' => get_the_title( $post ) ];
        }
        return $options;
    }

    // =========================================================
    // Render Helpers
    // =========================================================

    private function enqueue_public_assets(): void {
        static $done = false;
        if ( $done ) return;
        $done = true;

        foreach ( [ 'vm-main', 'vm-grid', 'vm-gallery', 'vm-vitrine', 'vm-responsive' ] as $handle ) {
            wp_enqueue_style( $handle, VM_PLUGIN_URL . 'public/assets/css/' . $handle . '.css', [], VM_PLUGIN_VERSION );
        }
        wp_enqueue_script( 'vm-main',   VM_PLUGIN_URL . 'public/assets/js/vm-main.js',   [ 'jquery' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-search', VM_PLUGIN_URL . 'public/assets/js/vm-search.js', [ 'vm-main' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-lazy',   VM_PLUGIN_URL . 'public/assets/js/vm-lazy.js',   [], VM_PLUGIN_VERSION, true );

        $settings = get_option( 'vm_settings', [] );
        wp_localize_script( 'vm-main', 'vmPublic', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'settings' => [ 'lightbox' => false, 'breadcrumb' => false, 'badge' => false ],
            'lazy'     => [
                'nonce'   => wp_create_nonce( 'vm_lazy_load' ),
                'perPage' => (int) ( $settings['archive_per_page'] ?? 12 ),
            ],
            'i18n' => [
                'loadMore' => __( 'Mehr laden', 'vmuseum' ),
                'noMore'   => __( 'Alle Objekte geladen', 'vmuseum' ),
            ],
        ] );
    }

    private function render_cards( array $posts, string $partial_file ): string {
        global $post;
        $html = '';
        foreach ( $posts as $post ) {
            setup_postdata( $post );
            ob_start();
            include $partial_file;
            $html .= ob_get_clean();
        }
        wp_reset_postdata();
        return $html;
    }

    // =========================================================
    // Render Callbacks
    // =========================================================

    /** Full museum entrance page as embeddable block */
    public function render_museum_entrance( array $attrs ): string {
        $this->enqueue_public_assets();

        $settings   = get_option( 'vm_settings', [] );
        $rooms      = get_posts( [ 'post_type' => 'museum_room', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
        $r_count    = wp_count_posts( 'museum_room' )->publish    ?? 0;
        $v_count    = wp_count_posts( 'museum_vitrine' )->publish ?? 0;
        $g_count    = wp_count_posts( 'museum_gallery' )->publish ?? 0;
        $o_count    = wp_count_posts( 'museum_object' )->publish  ?? 0;

        ob_start(); ?>
        <div class="vm-entrance vm-page vm-block-entrance">
            <nav class="vm-entrance__stats">
                <div class="vm-entrance__stat"><strong class="vm-entrance__stat-num"><?php echo esc_html( $r_count ); ?></strong><span class="vm-entrance__stat-label"><?php esc_html_e( 'Räume', 'vmuseum' ); ?></span></div>
                <div class="vm-entrance__stat"><strong class="vm-entrance__stat-num"><?php echo esc_html( $v_count ); ?></strong><span class="vm-entrance__stat-label"><?php esc_html_e( 'Vitrinen', 'vmuseum' ); ?></span></div>
                <div class="vm-entrance__stat"><strong class="vm-entrance__stat-num"><?php echo esc_html( $g_count ); ?></strong><span class="vm-entrance__stat-label"><?php esc_html_e( 'Galerien', 'vmuseum' ); ?></span></div>
                <div class="vm-entrance__stat"><strong class="vm-entrance__stat-num"><?php echo esc_html( $o_count ); ?></strong><span class="vm-entrance__stat-label"><?php esc_html_e( 'Objekte', 'vmuseum' ); ?></span></div>
            </nav>

            <div class="vm-entrance__search-wrap">
                <div class="vm-entrance__search-inner">
                    <input type="search" id="vm-live-search" class="vm-entrance__search-input"
                           placeholder="<?php esc_attr_e( 'Museum durchsuchen …', 'vmuseum' ); ?>" autocomplete="off">
                    <span class="vm-entrance__search-icon" aria-hidden="true">🔍</span>
                    <div id="vm-search-results" class="vm-entrance__search-results" hidden></div>
                </div>
            </div>

            <?php if ( $rooms ) : ?>
            <section class="vm-entrance__rooms">
                <h2 class="vm-entrance__section-title"><?php esc_html_e( 'Ausstellungsräume', 'vmuseum' ); ?></h2>
                <div class="vm-grid vm-grid--rooms vm-entrance__room-grid">
                    <?php echo $this->render_cards( $rooms, VM_PLUGIN_DIR . 'public/templates/partials/card-room.php' ); ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Grid of all (or limited) rooms */
    public function render_room_grid( array $attrs ): string {
        $this->enqueue_public_assets();

        $columns    = max( 1, min( 4, (int) ( $attrs['columns'] ?? 3 ) ) );
        $limit      = (int) ( $attrs['limit'] ?? 0 );
        $show_count = (bool) ( $attrs['show_count'] ?? true );

        $rooms = get_posts( [
            'post_type'      => 'museum_room',
            'post_status'    => 'publish',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ] );

        if ( ! $rooms ) return '<p class="vm-empty">' . esc_html__( 'Keine Räume vorhanden.', 'vmuseum' ) . '</p>';

        set_query_var( 'vm_atts', [ 'show_count' => $show_count ? 'yes' : 'no' ] );

        $style = $columns !== 3 ? ' style="grid-template-columns:repeat(' . $columns . ',1fr)"' : '';

        return '<div class="vm-grid vm-grid--rooms"' . $style . '>'
            . $this->render_cards( $rooms, VM_PLUGIN_DIR . 'public/templates/partials/card-room.php' )
            . '</div>';
    }

    /** Single room card */
    public function render_single_room( array $attrs ): string {
        $this->enqueue_public_assets();
        $room_id = (int) ( $attrs['room_id'] ?? 0 );
        if ( ! $room_id ) return '<p class="vm-empty">' . esc_html__( 'Bitte einen Raum auswählen.', 'vmuseum' ) . '</p>';

        $post = get_post( $room_id );
        if ( ! $post || $post->post_status !== 'publish' ) return '';

        return '<div class="vm-grid vm-grid--rooms" style="grid-template-columns:1fr">'
            . $this->render_cards( [ $post ], VM_PLUGIN_DIR . 'public/templates/partials/card-room.php' )
            . '</div>';
    }

    /** Object grid – optionally filtered by room / vitrine / gallery, with lazy loading */
    public function render_object_grid( array $attrs ): string {
        $this->enqueue_public_assets();

        $parent_type = sanitize_key( $attrs['parent_type'] ?? '' );
        $parent_id   = (int) ( $attrs['parent_id'] ?? 0 );
        $per_page    = max( 4, (int) ( $attrs['per_page'] ?? 12 ) );
        $orderby     = in_array( $attrs['orderby'] ?? 'date', [ 'date', 'title', 'menu_order' ], true ) ? $attrs['orderby'] : 'date';
        $columns     = max( 1, min( 6, (int) ( $attrs['columns'] ?? 4 ) ) );

        if ( $parent_type && $parent_id ) {
            $all_posts   = VM_Relations::get_children( $parent_type, $parent_id, 'object', true );
            $total       = count( $all_posts );
            $first_batch = array_slice( $all_posts, 0, $per_page );
        } else {
            $q           = new WP_Query( [ 'post_type' => 'museum_object', 'post_status' => 'publish', 'posts_per_page' => $per_page, 'orderby' => $orderby, 'order' => $orderby === 'title' ? 'ASC' : 'DESC' ] );
            $total       = (int) $q->found_posts;
            $first_batch = $q->posts;
            wp_reset_postdata();
        }

        if ( ! $first_batch ) return '<p class="vm-empty">' . esc_html__( 'Keine Objekte gefunden.', 'vmuseum' ) . '</p>';

        $total_pages = (int) ceil( $total / $per_page );
        $style       = $columns !== 4 ? ' style="grid-template-columns:repeat(' . $columns . ',1fr)"' : '';

        $grid_atts = sprintf(
            'data-vm-lazy-grid data-current-page="1" data-total-pages="%d" data-per-page="%d" data-parent-type="%s" data-parent-id="%d" data-child-type="object"',
            $total_pages, $per_page, esc_attr( $parent_type ), $parent_id
        );

        $html  = '<div class="vm-grid vm-grid--objects"' . $style . ' ' . $grid_atts . '>';
        $html .= $this->render_cards( $first_batch, VM_PLUGIN_DIR . 'public/templates/partials/card-object.php' );
        $html .= '</div>';

        if ( $total_pages > 1 ) {
            $html .= '<div class="vm-load-more-bar">'
                   . '<div id="vm-load-more-sentinel" aria-hidden="true"></div>'
                   . '<button id="vm-load-more-btn" class="vm-btn vm-btn--load-more" type="button">' . esc_html__( 'Mehr laden', 'vmuseum' ) . '</button>'
                   . '</div>';
        }

        return $html;
    }

    /** Latest N objects */
    public function render_recent_objects( array $attrs ): string {
        $this->enqueue_public_assets();

        $count   = max( 1, (int) ( $attrs['count'] ?? 8 ) );
        $columns = max( 1, min( 6, (int) ( $attrs['columns'] ?? 4 ) ) );

        $posts = get_posts( [ 'post_type' => 'museum_object', 'post_status' => 'publish', 'posts_per_page' => $count, 'orderby' => 'date', 'order' => 'DESC' ] );
        if ( ! $posts ) return '<p class="vm-empty">' . esc_html__( 'Keine Objekte vorhanden.', 'vmuseum' ) . '</p>';

        $style = $columns !== 4 ? ' style="grid-template-columns:repeat(' . $columns . ',1fr)"' : '';

        return '<div class="vm-grid vm-grid--objects"' . $style . '>'
            . $this->render_cards( $posts, VM_PLUGIN_DIR . 'public/templates/partials/card-object.php' )
            . '</div>';
    }

    /** Objects of a specific vitrine */
    public function render_vitrine_objects( array $attrs ): string {
        $this->enqueue_public_assets();

        $vitrine_id = (int) ( $attrs['vitrine_id'] ?? 0 );
        $per_page   = max( 4, (int) ( $attrs['per_page'] ?? 12 ) );

        if ( ! $vitrine_id ) return '<p class="vm-empty">' . esc_html__( 'Bitte eine Vitrine auswählen.', 'vmuseum' ) . '</p>';

        $all         = VM_Relations::get_objects( 'vitrine', $vitrine_id );
        $total       = count( $all );
        $first_batch = array_slice( $all, 0, $per_page );
        if ( ! $first_batch ) return '<p class="vm-empty">' . esc_html__( 'Diese Vitrine enthält noch keine Objekte.', 'vmuseum' ) . '</p>';

        $total_pages = (int) ceil( $total / $per_page );
        $grid_atts   = sprintf( 'data-vm-lazy-grid data-current-page="1" data-total-pages="%d" data-per-page="%d" data-parent-type="vitrine" data-parent-id="%d" data-child-type="object"', $total_pages, $per_page, $vitrine_id );

        $html  = '<div class="vm-grid vm-grid--objects" ' . $grid_atts . '>';
        $html .= $this->render_cards( $first_batch, VM_PLUGIN_DIR . 'public/templates/partials/card-object.php' );
        $html .= '</div>';

        if ( $total_pages > 1 ) {
            $html .= '<div class="vm-load-more-bar"><div id="vm-load-more-sentinel" aria-hidden="true"></div><button id="vm-load-more-btn" class="vm-btn vm-btn--load-more">' . esc_html__( 'Mehr laden', 'vmuseum' ) . '</button></div>';
        }

        return $html;
    }

    /** Objects of a specific gallery */
    public function render_gallery_objects( array $attrs ): string {
        $this->enqueue_public_assets();

        $gallery_id = (int) ( $attrs['gallery_id'] ?? 0 );
        $per_page   = max( 4, (int) ( $attrs['per_page'] ?? 12 ) );

        if ( ! $gallery_id ) return '<p class="vm-empty">' . esc_html__( 'Bitte eine Galerie auswählen.', 'vmuseum' ) . '</p>';

        $all         = VM_Relations::get_objects( 'gallery', $gallery_id );
        $total       = count( $all );
        $first_batch = array_slice( $all, 0, $per_page );
        if ( ! $first_batch ) return '<p class="vm-empty">' . esc_html__( 'Diese Galerie enthält noch keine Objekte.', 'vmuseum' ) . '</p>';

        $total_pages = (int) ceil( $total / $per_page );
        $grid_atts   = sprintf( 'data-vm-lazy-grid data-current-page="1" data-total-pages="%d" data-per-page="%d" data-parent-type="gallery" data-parent-id="%d" data-child-type="object"', $total_pages, $per_page, $gallery_id );

        $html  = '<div class="vm-grid vm-grid--objects" ' . $grid_atts . '>';
        $html .= $this->render_cards( $first_batch, VM_PLUGIN_DIR . 'public/templates/partials/card-object.php' );
        $html .= '</div>';

        if ( $total_pages > 1 ) {
            $html .= '<div class="vm-load-more-bar"><div id="vm-load-more-sentinel" aria-hidden="true"></div><button id="vm-load-more-btn" class="vm-btn vm-btn--load-more">' . esc_html__( 'Mehr laden', 'vmuseum' ) . '</button></div>';
        }

        return $html;
    }

    /** Museum statistics bar */
    public function render_museum_stats( array $attrs ): string {
        $this->enqueue_public_assets();

        $parts = [];
        if ( $attrs['show_rooms']     ?? true ) $parts[] = [ '🚪', wp_count_posts( 'museum_room' )->publish,    __( 'Räume', 'vmuseum' ) ];
        if ( $attrs['show_vitrines']  ?? true ) $parts[] = [ '🗄️', wp_count_posts( 'museum_vitrine' )->publish, __( 'Vitrinen', 'vmuseum' ) ];
        if ( $attrs['show_galleries'] ?? true ) $parts[] = [ '🖼️', wp_count_posts( 'museum_gallery' )->publish, __( 'Galerien', 'vmuseum' ) ];
        if ( $attrs['show_objects']   ?? true ) $parts[] = [ '🎨', wp_count_posts( 'museum_object' )->publish,  __( 'Objekte', 'vmuseum' ) ];

        if ( ! $parts ) return '';

        $html = '<nav class="vm-entrance__stats">';
        foreach ( $parts as [ $icon, $count, $label ] ) {
            $html .= '<div class="vm-entrance__stat">'
                   . '<span class="vm-entrance__stat-icon">' . $icon . '</span>'
                   . '<strong class="vm-entrance__stat-num">' . esc_html( $count ) . '</strong>'
                   . '<span class="vm-entrance__stat-label">' . esc_html( $label ) . '</span>'
                   . '</div>';
        }
        $html .= '</nav>';
        return $html;
    }

    /** Live search input */
    public function render_museum_search( array $attrs ): string {
        $this->enqueue_public_assets();

        $placeholder = sanitize_text_field( $attrs['placeholder'] ?? __( 'Museum durchsuchen …', 'vmuseum' ) );

        return '<div class="vm-entrance__search-wrap">
            <div class="vm-entrance__search-inner">
                <input type="search" id="vm-live-search" class="vm-entrance__search-input"
                       placeholder="' . esc_attr( $placeholder ) . '" autocomplete="off">
                <span class="vm-entrance__search-icon" aria-hidden="true">🔍</span>
                <div id="vm-search-results" class="vm-entrance__search-results" hidden></div>
            </div>
        </div>';
    }

    /** Room navigation list */
    public function render_museum_nav( array $attrs ): string {
        $this->enqueue_public_assets();

        $show_count = (bool) ( $attrs['show_count'] ?? true );
        $show_sub   = (bool) ( $attrs['show_sub']   ?? true );

        $rooms = get_posts( [ 'post_type' => 'museum_room', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
        if ( ! $rooms ) return '';

        $current_room_id = is_singular( 'museum_room' ) ? get_the_ID() : 0;

        $html  = '<nav class="vm-widget-nav"><ul class="vm-widget-nav__list">';
        foreach ( $rooms as $room ) {
            $color  = get_post_meta( $room->ID, 'vm_room_color', true ) ?: '#8B4513';
            $active = $current_room_id === $room->ID ? ' vm-widget-nav__item--active' : '';
            $html  .= '<li class="vm-widget-nav__item' . esc_attr( $active ) . '" style="--vm-item-color:' . esc_attr( $color ) . '">';
            $html  .= '<a href="' . esc_url( get_permalink( $room->ID ) ) . '" class="vm-widget-nav__link">';
            $html  .= '<span class="vm-widget-nav__dot" aria-hidden="true"></span>';
            $html  .= esc_html( get_the_title( $room ) );
            if ( $show_count ) {
                $cnt = count( VM_Relations::get_children( 'room', $room->ID, 'object', true ) );
                if ( $cnt ) $html .= ' <span class="vm-widget-nav__count">(' . esc_html( $cnt ) . ')</span>';
            }
            $html .= '</a>';
            if ( $show_sub && $current_room_id === $room->ID ) {
                $vitrines  = VM_Relations::get_vitrines( $room->ID );
                $galleries = VM_Relations::get_galleries( 'room', $room->ID );
                if ( $vitrines || $galleries ) {
                    $html .= '<ul class="vm-widget-nav__sub">';
                    foreach ( $vitrines  as $v ) $html .= '<li><a href="' . esc_url( get_permalink( $v->ID ) ) . '">🗄️ ' . esc_html( get_the_title( $v ) ) . '</a></li>';
                    foreach ( $galleries as $g ) $html .= '<li><a href="' . esc_url( get_permalink( $g->ID ) ) . '">🖼️ ' . esc_html( get_the_title( $g ) ) . '</a></li>';
                    $html .= '</ul>';
                }
            }
            $html .= '</li>';
        }
        $html .= '</ul></nav>';
        return $html;
    }
}
