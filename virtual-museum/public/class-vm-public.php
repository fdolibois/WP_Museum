<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Public {

    public function __construct() {
        add_action( 'wp_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
        add_filter( 'template_include',    [ $this, 'load_templates' ] );
    }

    public function enqueue_assets(): void {
        if ( ! $this->is_museum_page() ) return;

        wp_enqueue_style( 'vm-main',      VM_PLUGIN_URL . 'public/assets/css/vm-main.css',      [], VM_PLUGIN_VERSION );
        wp_enqueue_style( 'vm-grid',      VM_PLUGIN_URL . 'public/assets/css/vm-grid.css',      [], VM_PLUGIN_VERSION );
        wp_enqueue_style( 'vm-lightbox',  VM_PLUGIN_URL . 'public/assets/css/vm-lightbox.css',  [], VM_PLUGIN_VERSION );
        wp_enqueue_style( 'vm-vitrine',   VM_PLUGIN_URL . 'public/assets/css/vm-vitrine.css',   [], VM_PLUGIN_VERSION );
        wp_enqueue_style( 'vm-gallery',   VM_PLUGIN_URL . 'public/assets/css/vm-gallery.css',   [], VM_PLUGIN_VERSION );
        wp_enqueue_style( 'vm-responsive',VM_PLUGIN_URL . 'public/assets/css/vm-responsive.css',[], VM_PLUGIN_VERSION );

        wp_enqueue_script( 'vm-main',       VM_PLUGIN_URL . 'public/assets/js/vm-main.js',       [ 'jquery' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-lightbox',   VM_PLUGIN_URL . 'public/assets/js/vm-lightbox.js',   [ 'vm-main' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-filter',     VM_PLUGIN_URL . 'public/assets/js/vm-filter.js',     [ 'vm-main' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-search',     VM_PLUGIN_URL . 'public/assets/js/vm-search.js',     [ 'vm-main' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-breadcrumb', VM_PLUGIN_URL . 'public/assets/js/vm-breadcrumb.js', [ 'vm-main' ], VM_PLUGIN_VERSION, true );
        wp_enqueue_script( 'vm-lazy',       VM_PLUGIN_URL . 'public/assets/js/vm-lazy.js',       [], VM_PLUGIN_VERSION, true );

        $settings = get_option( 'vm_settings', [] );
        if ( ! empty( $settings['enable_360'] ) && is_singular( 'museum_object' ) ) {
            wp_enqueue_script( 'pannellum', VM_PLUGIN_URL . 'public/assets/lib/pannellum/pannellum.js', [], '2.5.6', true );
            wp_enqueue_style( 'pannellum',  VM_PLUGIN_URL . 'public/assets/lib/pannellum/pannellum.css', [], '2.5.6' );
        }

        wp_localize_script( 'vm-main', 'vmPublic', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'settings' => [
                'lightbox'    => ! empty( $settings['enable_lightbox'] ),
                'breadcrumb'  => ! empty( $settings['enable_breadcrumb'] ),
                'badge'       => ! empty( $settings['show_relation_badge'] ),
            ],
            'lazy' => [
                'nonce'   => wp_create_nonce( 'vm_lazy_load' ),
                'perPage' => (int) ( $settings['lazy_per_page'] ?? 12 ),
            ],
            'i18n' => [
                'close'    => __( 'Schließen', 'vmuseum' ),
                'prev'     => __( 'Vorheriges', 'vmuseum' ),
                'next'     => __( 'Nächstes', 'vmuseum' ),
                'loading'  => __( 'Lade...', 'vmuseum' ),
                'loadMore' => __( 'Mehr laden', 'vmuseum' ),
                'noMore'   => __( 'Alle Objekte geladen', 'vmuseum' ),
            ],
        ] );
    }

    public function load_templates( string $template ): string {
        if ( is_singular( 'museum_room' ) ) {
            $custom = VM_PLUGIN_DIR . 'public/templates/single-museum-room.php';
            if ( file_exists( $custom ) ) return $custom;
        }
        if ( is_singular( 'museum_vitrine' ) ) {
            $custom = VM_PLUGIN_DIR . 'public/templates/single-museum-vitrine.php';
            if ( file_exists( $custom ) ) return $custom;
        }
        if ( is_singular( 'museum_gallery' ) ) {
            $custom = VM_PLUGIN_DIR . 'public/templates/single-museum-gallery.php';
            if ( file_exists( $custom ) ) return $custom;
        }
        if ( is_singular( 'museum_object' ) ) {
            $custom = VM_PLUGIN_DIR . 'public/templates/single-museum-object.php';
            if ( file_exists( $custom ) ) return $custom;
        }
        if ( is_post_type_archive( 'museum_object' ) ) {
            $custom = VM_PLUGIN_DIR . 'public/templates/archive-museum-object.php';
            if ( file_exists( $custom ) ) return $custom;
        }
        return $template;
    }

    private function is_museum_page(): bool {
        return is_singular( [ 'museum_room', 'museum_vitrine', 'museum_gallery', 'museum_object' ] )
            || is_post_type_archive( [ 'museum_room', 'museum_vitrine', 'museum_gallery', 'museum_object' ] )
            || is_tax( 'museum_era' );
    }
}
