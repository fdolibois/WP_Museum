<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Shortcodes {

    public function __construct() {
        add_shortcode( 'vm_room',            [ $this, 'shortcode_room' ] );
        add_shortcode( 'vm_vitrine',         [ $this, 'shortcode_vitrine' ] );
        add_shortcode( 'vm_gallery',         [ $this, 'shortcode_gallery' ] );
        add_shortcode( 'vm_museum_archive',  [ $this, 'shortcode_archive' ] );
        add_shortcode( 'vm_room_grid',       [ $this, 'shortcode_room_grid' ] );
        add_shortcode( 'vm_object_contexts', [ $this, 'shortcode_object_contexts' ] );
    }

    public function shortcode_room( array $atts ): string {
        $atts = shortcode_atts( [
            'id'             => '',
            'show_vitrines'  => 'yes',
            'show_galleries' => 'yes',
            'show_objects'   => 'yes',
            'layout'         => 'sections',
            'depth'          => '2',
        ], $atts, 'vm_room' );

        $room = $this->get_post( $atts['id'], 'museum_room' );
        if ( ! $room ) return '';

        ob_start();
        set_query_var( 'vm_atts', $atts );
        include VM_PLUGIN_DIR . 'public/templates/single-museum-room.php';
        return ob_get_clean();
    }

    public function shortcode_vitrine( array $atts ): string {
        $atts = shortcode_atts( [
            'id'             => '',
            'layout'         => 'showcase',
            'show_galleries' => 'yes',
            'show_objects'   => 'yes',
            'theme'          => 'light',
            'show_context'   => 'yes',
        ], $atts, 'vm_vitrine' );

        $vitrine = $this->get_post( $atts['id'], 'museum_vitrine' );
        if ( ! $vitrine ) return '';

        ob_start();
        set_query_var( 'vm_atts', $atts );
        include VM_PLUGIN_DIR . 'public/templates/single-museum-vitrine.php';
        return ob_get_clean();
    }

    public function shortcode_gallery( array $atts ): string {
        $atts = shortcode_atts( [
            'id'           => '',
            'mode'         => 'slider',
            'lightbox'     => 'yes',
            'show_context' => 'yes',
            'autoplay'     => 'no',
            'caption'      => 'below',
        ], $atts, 'vm_gallery' );

        $gallery = $this->get_post( $atts['id'], 'museum_gallery' );
        if ( ! $gallery ) return '';

        ob_start();
        set_query_var( 'vm_atts', $atts );
        include VM_PLUGIN_DIR . 'public/templates/single-museum-gallery.php';
        return ob_get_clean();
    }

    public function shortcode_archive( array $atts ): string {
        $atts = shortcode_atts( [
            'type'        => 'all',
            'room'        => '',
            'layout'      => 'grid',
            'per_page'    => '24',
            'show_filter' => 'yes',
            'show_search' => 'yes',
            'show_nav'    => 'yes',
            'orderby'     => 'date',
        ], $atts, 'vm_museum_archive' );

        ob_start();
        set_query_var( 'vm_atts', $atts );
        include VM_PLUGIN_DIR . 'public/templates/archive-museum-object.php';
        return ob_get_clean();
    }

    public function shortcode_room_grid( array $atts ): string {
        $atts = shortcode_atts( [
            'columns'       => '4',
            'show_count'    => 'yes',
            'show_vitrines' => 'yes',
            'style'         => 'card',
        ], $atts, 'vm_room_grid' );

        $rooms = get_posts( [
            'post_type'   => 'museum_room',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby'     => 'menu_order',
            'order'       => 'ASC',
        ] );

        if ( ! $rooms ) return '';

        ob_start();
        set_query_var( 'vm_atts', $atts );
        ?>
        <div class="vm-room-grid vm-room-grid--cols-<?php echo esc_attr( $atts['columns'] ); ?> vm-room-grid--<?php echo esc_attr( $atts['style'] ); ?>">
            <?php foreach ( $rooms as $room ) :
                $post = $room;
                include VM_PLUGIN_DIR . 'public/templates/partials/card-room.php';
            endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_object_contexts( array $atts ): string {
        $atts = shortcode_atts( [
            'id'    => '',
            'style' => 'badges',
        ], $atts, 'vm_object_contexts' );

        $object_id = (int) $atts['id'];
        if ( ! $object_id ) return '';

        ob_start();
        set_query_var( 'vm_context_object_id', $object_id );
        set_query_var( 'vm_context_style', $atts['style'] );
        include VM_PLUGIN_DIR . 'public/templates/partials/relation-badge.php';
        return ob_get_clean();
    }

    private function get_post( string $id_or_slug, string $post_type ): ?WP_Post {
        if ( is_numeric( $id_or_slug ) ) {
            $post = get_post( (int) $id_or_slug );
            return ( $post && $post->post_type === $post_type ) ? $post : null;
        }
        $posts = get_posts( [
            'post_type'   => $post_type,
            'name'        => sanitize_title( $id_or_slug ),
            'numberposts' => 1,
            'post_status' => 'publish',
        ] );
        return $posts[0] ?? null;
    }
}
