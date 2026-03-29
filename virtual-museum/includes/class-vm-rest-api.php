<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Rest_Api {

    private const NAMESPACE = 'vm/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        $settings = get_option( 'vm_settings', [] );
        if ( empty( $settings['enable_rest_api'] ) ) return;

        $ns = self::NAMESPACE;

        register_rest_route( $ns, '/rooms',                              [ 'methods' => 'GET', 'callback' => [ $this, 'get_rooms' ],            'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/rooms/(?P<id>\d+)',                  [ 'methods' => 'GET', 'callback' => [ $this, 'get_room' ],             'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/rooms/(?P<id>\d+)/contents',         [ 'methods' => 'GET', 'callback' => [ $this, 'get_room_contents' ],    'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/rooms/(?P<id>\d+)/vitrines',         [ 'methods' => 'GET', 'callback' => [ $this, 'get_room_vitrines' ],    'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/rooms/(?P<id>\d+)/galleries',        [ 'methods' => 'GET', 'callback' => [ $this, 'get_room_galleries' ],   'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/rooms/(?P<id>\d+)/objects',          [ 'methods' => 'GET', 'callback' => [ $this, 'get_room_objects' ],     'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/vitrines/(?P<id>\d+)',               [ 'methods' => 'GET', 'callback' => [ $this, 'get_vitrine' ],          'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/vitrines/(?P<id>\d+)/contents',      [ 'methods' => 'GET', 'callback' => [ $this, 'get_vitrine_contents' ], 'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/galleries/(?P<id>\d+)',              [ 'methods' => 'GET', 'callback' => [ $this, 'get_gallery' ],          'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/galleries/(?P<id>\d+)/objects',      [ 'methods' => 'GET', 'callback' => [ $this, 'get_gallery_objects' ],  'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/objects/(?P<id>\d+)',                [ 'methods' => 'GET', 'callback' => [ $this, 'get_object' ],           'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/objects/(?P<id>\d+)/contexts',       [ 'methods' => 'GET', 'callback' => [ $this, 'get_object_contexts' ],  'permission_callback' => '__return_true' ] );
        register_rest_route( $ns, '/search',                             [ 'methods' => 'GET', 'callback' => [ $this, 'search' ],               'permission_callback' => '__return_true',
            'args' => [
                'q'    => [ 'sanitize_callback' => 'sanitize_text_field' ],
                'type' => [ 'sanitize_callback' => 'sanitize_text_field' ],
                'room' => [ 'sanitize_callback' => 'absint' ],
            ],
        ] );
    }

    public function get_rooms(): WP_REST_Response {
        $rooms = get_posts( [ 'post_type' => 'museum_room', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC' ] );
        return rest_ensure_response( array_map( [ $this, 'format_room' ], $rooms ) );
    }

    public function get_room( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'museum_room' ) return new WP_Error( 'not_found', 'Raum nicht gefunden.', [ 'status' => 404 ] );
        return rest_ensure_response( $this->format_room( $post ) );
    }

    public function get_room_contents( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $room_id = (int) $request['id'];
        $post    = get_post( $room_id );
        if ( ! $post || $post->post_type !== 'museum_room' ) return new WP_Error( 'not_found', 'Raum nicht gefunden.', [ 'status' => 404 ] );

        $contents = VM_Relations::get_room_contents( $room_id );
        $data     = array_map( fn( $item ) => [
            'type'     => $item['type'],
            'position' => $item['position'],
            'post'     => $this->format_post( $item['post'], $item['type'] ),
        ], $contents );
        return rest_ensure_response( $data );
    }

    public function get_room_vitrines( WP_REST_Request $request ): WP_REST_Response {
        return rest_ensure_response( array_map( fn( $v ) => $this->format_post( $v, 'vitrine' ), VM_Relations::get_vitrines( (int) $request['id'] ) ) );
    }

    public function get_room_galleries( WP_REST_Request $request ): WP_REST_Response {
        return rest_ensure_response( array_map( fn( $g ) => $this->format_post( $g, 'gallery' ), VM_Relations::get_galleries( 'room', (int) $request['id'] ) ) );
    }

    public function get_room_objects( WP_REST_Request $request ): WP_REST_Response {
        return rest_ensure_response( array_map( fn( $o ) => $this->format_post( $o, 'object' ), VM_Relations::get_objects( 'room', (int) $request['id'] ) ) );
    }

    public function get_vitrine( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'museum_vitrine' ) return new WP_Error( 'not_found', 'Vitrine nicht gefunden.', [ 'status' => 404 ] );
        return rest_ensure_response( $this->format_post( $post, 'vitrine' ) );
    }

    public function get_vitrine_contents( WP_REST_Request $request ): WP_REST_Response {
        $id = (int) $request['id'];
        return rest_ensure_response( [
            'galleries' => array_map( fn( $g ) => $this->format_post( $g, 'gallery' ), VM_Relations::get_galleries( 'vitrine', $id ) ),
            'objects'   => array_map( fn( $o ) => $this->format_post( $o, 'object' ), VM_Relations::get_objects( 'vitrine', $id ) ),
        ] );
    }

    public function get_gallery( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'museum_gallery' ) return new WP_Error( 'not_found', 'Galerie nicht gefunden.', [ 'status' => 404 ] );
        return rest_ensure_response( $this->format_post( $post, 'gallery' ) );
    }

    public function get_gallery_objects( WP_REST_Request $request ): WP_REST_Response {
        return rest_ensure_response( array_map( fn( $o ) => $this->format_post( $o, 'object' ), VM_Relations::get_objects( 'gallery', (int) $request['id'] ) ) );
    }

    public function get_object( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post = get_post( (int) $request['id'] );
        if ( ! $post || $post->post_type !== 'museum_object' ) return new WP_Error( 'not_found', 'Objekt nicht gefunden.', [ 'status' => 404 ] );
        return rest_ensure_response( $this->format_post( $post, 'object' ) );
    }

    public function get_object_contexts( WP_REST_Request $request ): WP_REST_Response {
        $id = (int) $request['id'];
        return rest_ensure_response( [
            'rooms'    => array_map( fn( $r ) => $this->format_post( $r, 'room' ),    VM_Relations::get_all_rooms_for( 'object', $id ) ),
            'galleries'=> array_map( fn( $g ) => $this->format_post( $g, 'gallery' ), VM_Relations::get_parents( 'object', $id, 'gallery' ) ),
            'vitrines' => array_map( fn( $v ) => $this->format_post( $v, 'vitrine' ), VM_Relations::get_parents( 'object', $id, 'vitrine' ) ),
        ] );
    }

    public function search( WP_REST_Request $request ): WP_REST_Response {
        $q    = $request->get_param( 'q' ) ?? '';
        $type = $request->get_param( 'type' ) ?? 'all';

        $post_types = match( $type ) {
            'rooms'     => [ 'museum_room' ],
            'galleries' => [ 'museum_gallery' ],
            'vitrines'  => [ 'museum_vitrine' ],
            'objects'   => [ 'museum_object' ],
            default     => [ 'museum_room', 'museum_gallery', 'museum_vitrine', 'museum_object' ],
        };

        $wp_query = new WP_Query( [ 's' => $q, 'post_type' => $post_types, 'post_status' => 'publish', 'posts_per_page' => 20 ] );
        $results  = array_map( fn( $p ) => $this->format_post( $p, VM_Relations::cpt_to_type( $p->post_type ) ), $wp_query->posts );

        return rest_ensure_response( [ 'results' => $results, 'total' => $wp_query->found_posts ] );
    }

    private function format_room( WP_Post $post ): array {
        $data           = $this->format_post( $post, 'room' );
        $data['meta']   = [
            'color'         => get_post_meta( $post->ID, 'vm_room_color', true ),
            'era'           => get_post_meta( $post->ID, 'vm_room_era', true ),
            'display_order' => (int) get_post_meta( $post->ID, 'vm_room_display_order', true ),
        ];
        $ns = self::NAMESPACE;
        $data['_links'] = [
            'contents' => [ 'href' => rest_url( "{$ns}/rooms/{$post->ID}/contents" ) ],
            'vitrines' => [ 'href' => rest_url( "{$ns}/rooms/{$post->ID}/vitrines" ) ],
            'galleries'=> [ 'href' => rest_url( "{$ns}/rooms/{$post->ID}/galleries" ) ],
            'objects'  => [ 'href' => rest_url( "{$ns}/rooms/{$post->ID}/objects" ) ],
        ];
        return $data;
    }

    private function format_post( WP_Post $post, string $type ): array {
        return [
            'id'      => $post->ID,
            'type'    => $type,
            'title'   => get_the_title( $post ),
            'excerpt' => get_the_excerpt( $post ),
            'url'     => get_permalink( $post->ID ),
            'thumb'   => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, 'medium' ) : null,
            'date'    => $post->post_date,
        ];
    }
}
