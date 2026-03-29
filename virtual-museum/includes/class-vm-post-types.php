<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Post_Types {

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
    }

    public function register(): void {
        $this->register_museum_object();
        $this->register_museum_gallery();
        $this->register_museum_vitrine();
        $this->register_museum_room();
        $this->register_museum_era_taxonomy();
    }

    private function register_museum_object(): void {
        register_post_type( 'museum_object', [
            'labels' => [
                'name'               => __( 'Museumsobjekte', 'vmuseum' ),
                'singular_name'      => __( 'Museumsobjekt', 'vmuseum' ),
                'add_new'            => __( 'Neu hinzufügen', 'vmuseum' ),
                'add_new_item'       => __( 'Neues Objekt', 'vmuseum' ),
                'edit_item'          => __( 'Objekt bearbeiten', 'vmuseum' ),
                'all_items'          => __( 'Alle Objekte', 'vmuseum' ),
                'search_items'       => __( 'Objekte suchen', 'vmuseum' ),
                'not_found'          => __( 'Keine Objekte gefunden', 'vmuseum' ),
                'not_found_in_trash' => __( 'Keine Objekte im Papierkorb', 'vmuseum' ),
                'menu_name'          => __( 'Objekte', 'vmuseum' ),
            ],
            'public'        => true,
            'has_archive'   => 'museum/objekte',
            'rewrite'       => [ 'slug' => 'museum/objekt' ],
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ],
            'menu_icon'     => 'dashicons-format-image',
            'show_in_rest'  => true,
            'menu_position' => 25,
        ] );
    }

    private function register_museum_gallery(): void {
        register_post_type( 'museum_gallery', [
            'labels' => [
                'name'               => __( 'Galerien', 'vmuseum' ),
                'singular_name'      => __( 'Galerie', 'vmuseum' ),
                'add_new'            => __( 'Neu hinzufügen', 'vmuseum' ),
                'add_new_item'       => __( 'Neue Galerie', 'vmuseum' ),
                'edit_item'          => __( 'Galerie bearbeiten', 'vmuseum' ),
                'all_items'          => __( 'Alle Galerien', 'vmuseum' ),
                'search_items'       => __( 'Galerien suchen', 'vmuseum' ),
                'not_found'          => __( 'Keine Galerien gefunden', 'vmuseum' ),
                'not_found_in_trash' => __( 'Keine Galerien im Papierkorb', 'vmuseum' ),
                'menu_name'          => __( 'Galerien', 'vmuseum' ),
            ],
            'public'        => true,
            'has_archive'   => 'museum/galerien',
            'rewrite'       => [ 'slug' => 'museum/galerie' ],
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
            'menu_icon'     => 'dashicons-images-alt2',
            'show_in_rest'  => true,
            'menu_position' => 26,
        ] );
    }

    private function register_museum_vitrine(): void {
        register_post_type( 'museum_vitrine', [
            'labels' => [
                'name'               => __( 'Vitrinen', 'vmuseum' ),
                'singular_name'      => __( 'Vitrine', 'vmuseum' ),
                'add_new'            => __( 'Neu hinzufügen', 'vmuseum' ),
                'add_new_item'       => __( 'Neue Vitrine', 'vmuseum' ),
                'edit_item'          => __( 'Vitrine bearbeiten', 'vmuseum' ),
                'all_items'          => __( 'Alle Vitrinen', 'vmuseum' ),
                'search_items'       => __( 'Vitrinen suchen', 'vmuseum' ),
                'not_found'          => __( 'Keine Vitrinen gefunden', 'vmuseum' ),
                'not_found_in_trash' => __( 'Keine Vitrinen im Papierkorb', 'vmuseum' ),
                'menu_name'          => __( 'Vitrinen', 'vmuseum' ),
            ],
            'public'        => true,
            'has_archive'   => 'museum/vitrinen',
            'rewrite'       => [ 'slug' => 'museum/vitrine' ],
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
            'menu_icon'     => 'dashicons-screenoptions',
            'show_in_rest'  => true,
            'menu_position' => 27,
        ] );
    }

    private function register_museum_room(): void {
        register_post_type( 'museum_room', [
            'labels' => [
                'name'               => __( 'Museumsräume', 'vmuseum' ),
                'singular_name'      => __( 'Museumsraum', 'vmuseum' ),
                'add_new'            => __( 'Neu hinzufügen', 'vmuseum' ),
                'add_new_item'       => __( 'Neuer Raum', 'vmuseum' ),
                'edit_item'          => __( 'Raum bearbeiten', 'vmuseum' ),
                'all_items'          => __( 'Alle Räume', 'vmuseum' ),
                'search_items'       => __( 'Räume suchen', 'vmuseum' ),
                'not_found'          => __( 'Keine Räume gefunden', 'vmuseum' ),
                'not_found_in_trash' => __( 'Keine Räume im Papierkorb', 'vmuseum' ),
                'menu_name'          => __( 'Räume', 'vmuseum' ),
            ],
            'public'        => true,
            'has_archive'   => 'museum/raeume',
            'rewrite'       => [ 'slug' => 'museum/raum' ],
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
            'menu_icon'     => 'dashicons-admin-home',
            'show_in_rest'  => true,
            'menu_position' => 24,
        ] );
    }

    private function register_museum_era_taxonomy(): void {
        register_taxonomy( 'museum_era', [
            'museum_object', 'museum_gallery', 'museum_vitrine', 'museum_room'
        ], [
            'labels' => [
                'name'          => __( 'Epochen', 'vmuseum' ),
                'singular_name' => __( 'Epoche', 'vmuseum' ),
                'all_items'     => __( 'Alle Epochen', 'vmuseum' ),
                'edit_item'     => __( 'Epoche bearbeiten', 'vmuseum' ),
                'add_new_item'  => __( 'Neue Epoche', 'vmuseum' ),
                'menu_name'     => __( 'Epochen', 'vmuseum' ),
            ],
            'hierarchical'      => false,
            'rewrite'           => [ 'slug' => 'museum/epoche' ],
            'show_in_rest'      => true,
            'show_admin_column' => true,
        ] );
    }
}
