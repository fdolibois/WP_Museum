<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Ajax {

    public function __construct() {
        // Admin AJAX
        add_action( 'wp_ajax_vm_add_relation',      [ $this, 'add_relation' ] );
        add_action( 'wp_ajax_vm_remove_relation',   [ $this, 'remove_relation' ] );
        add_action( 'wp_ajax_vm_reorder_relations', [ $this, 'reorder_relations' ] );
        add_action( 'wp_ajax_vm_search_linkable',   [ $this, 'search_linkable' ] );

        // Public AJAX
        foreach ( [ 'vm_get_room_contents', 'vm_get_vitrine_contents', 'vm_get_gallery_objects', 'vm_get_object_contexts', 'vm_search' ] as $action ) {
            add_action( 'wp_ajax_nopriv_' . $action, [ $this, str_replace( 'vm_', '', $action ) ] );
            add_action( 'wp_ajax_' . $action,        [ $this, str_replace( 'vm_', '', $action ) ] );
        }

        // Lazy loading
        add_action( 'wp_ajax_nopriv_vm_load_objects_page', [ $this, 'load_objects_page' ] );
        add_action( 'wp_ajax_vm_load_objects_page',        [ $this, 'load_objects_page' ] );
    }

    public function add_relation(): void {
        check_ajax_referer( 'vm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Keine Berechtigung.', 'vmuseum' ) ], 403 );
        }

        $parent_type = sanitize_text_field( wp_unslash( $_POST['parent_type'] ?? '' ) );
        $parent_id   = (int) ( $_POST['parent_id'] ?? 0 );
        $child_type  = sanitize_text_field( wp_unslash( $_POST['child_type'] ?? '' ) );
        $child_id    = (int) ( $_POST['child_id'] ?? 0 );
        $position    = (int) ( $_POST['position'] ?? 0 );

        if ( ! $parent_id || ! $child_id ) {
            wp_send_json_error( [ 'message' => __( 'Ungültige IDs.', 'vmuseum' ) ], 400 );
        }

        $result = VM_Relations::add( $parent_type, $parent_id, $child_type, $child_id, $position );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        VM_Search_Index::update_post( $parent_id );
        VM_Search_Index::update_post( $child_id );

        wp_send_json_success( [
            'relation_id' => $result,
            'post_id'     => $child_id,
            'title'       => get_the_title( $child_id ),
            'thumb'       => has_post_thumbnail( $child_id ) ? get_the_post_thumbnail_url( $child_id, [ 40, 40 ] ) : '',
            'edit_url'    => get_edit_post_link( $child_id, 'url' ),
        ] );
    }

    public function remove_relation(): void {
        check_ajax_referer( 'vm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'Keine Berechtigung.', 'vmuseum' ) ], 403 );
        }

        $parent_type = sanitize_text_field( wp_unslash( $_POST['parent_type'] ?? '' ) );
        $parent_id   = (int) ( $_POST['parent_id'] ?? 0 );
        $child_type  = sanitize_text_field( wp_unslash( $_POST['child_type'] ?? '' ) );
        $child_id    = (int) ( $_POST['child_id'] ?? 0 );

        // Resolve via relation_id if direct IDs not provided
        if ( ! $parent_id && isset( $_POST['relation_id'] ) ) {
            global $wpdb;
            $rel = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vm_relations WHERE id = %d",
                (int) $_POST['relation_id']
            ) );
            if ( $rel ) {
                $parent_type = $rel->parent_type;
                $parent_id   = (int) $rel->parent_id;
                $child_type  = $rel->child_type;
                $child_id    = (int) $rel->child_id;
            }
        }

        if ( ! $parent_id || ! $child_id ) {
            wp_send_json_error( [ 'message' => __( 'Ungültige IDs.', 'vmuseum' ) ], 400 );
        }

        $result = VM_Relations::remove( $parent_type, $parent_id, $child_type, $child_id );
        VM_Search_Index::update_post( $parent_id );
        VM_Search_Index::update_post( $child_id );

        $result ? wp_send_json_success() : wp_send_json_error( [ 'message' => __( 'Beziehung nicht gefunden.', 'vmuseum' ) ] );
    }

    public function reorder_relations(): void {
        check_ajax_referer( 'vm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [], 403 );
        }

        $parent_type = sanitize_text_field( wp_unslash( $_POST['parent_type'] ?? '' ) );
        $parent_id   = (int) ( $_POST['parent_id'] ?? 0 );
        $ordered_ids = $_POST['ordered_relation_ids'] ?? [];

        if ( ! $parent_id || ! is_array( $ordered_ids ) ) {
            wp_send_json_error( [ 'message' => __( 'Ungültige Daten.', 'vmuseum' ) ], 400 );
        }

        $sanitized = [];
        foreach ( $ordered_ids as $index => $rel_id ) {
            $sanitized[ (int) $rel_id ] = (int) $index;
        }

        VM_Relations::reorder_children( $parent_type, $parent_id, $sanitized )
            ? wp_send_json_success()
            : wp_send_json_error( [ 'message' => __( 'Fehler beim Speichern der Reihenfolge.', 'vmuseum' ) ] );
    }

    public function search_linkable(): void {
        check_ajax_referer( 'vm_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( [], 403 );
        }

        $search      = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
        $child_type  = sanitize_text_field( wp_unslash( $_POST['child_type'] ?? 'object' ) );
        $parent_type = sanitize_text_field( wp_unslash( $_POST['parent_type'] ?? '' ) );
        $parent_id   = (int) ( $_POST['parent_id'] ?? 0 );

        $post_type = VM_Relations::type_to_cpt( $child_type );

        $query = new WP_Query( [
            's'              => $search,
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $results = [];
        foreach ( $query->posts as $post ) {
            $already_linked = $parent_id ? VM_Relations::exists( $parent_type, $parent_id, $child_type, $post->ID ) : false;
            $usage          = VM_Relations::get_usage_count( $child_type, $post->ID );
            $results[] = [
                'id'             => $post->ID,
                'title'          => get_the_title( $post ),
                'thumb'          => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, [ 40, 40 ] ) : '',
                'media_type'     => get_post_meta( $post->ID, 'vm_media_type', true ),
                'already_linked' => $already_linked,
                'usage_total'    => $usage['total'],
                'edit_url'       => get_edit_post_link( $post->ID, 'url' ),
            ];
        }

        wp_send_json_success( $results );
    }

    public function get_room_contents(): void {
        $room_id = (int) ( $_GET['room_id'] ?? 0 );
        $type    = sanitize_text_field( wp_unslash( $_GET['type'] ?? 'all' ) );
        if ( ! $room_id ) { wp_send_json_error( [], 400 ); }

        $data = [];
        if ( in_array( $type, [ 'all', 'vitrines' ], true ) ) {
            $data['vitrines'] = array_map( fn( $v ) => $this->format_post( $v, 'vitrine' ), VM_Relations::get_vitrines( $room_id ) );
        }
        if ( in_array( $type, [ 'all', 'galleries' ], true ) ) {
            $data['galleries'] = array_map( fn( $g ) => $this->format_post( $g, 'gallery' ), VM_Relations::get_galleries( 'room', $room_id ) );
        }
        if ( in_array( $type, [ 'all', 'objects' ], true ) ) {
            $data['objects'] = array_map( fn( $o ) => $this->format_post( $o, 'object' ), VM_Relations::get_objects( 'room', $room_id ) );
        }
        wp_send_json_success( $data );
    }

    public function get_vitrine_contents(): void {
        $vitrine_id = (int) ( $_GET['vitrine_id'] ?? 0 );
        if ( ! $vitrine_id ) { wp_send_json_error( [], 400 ); }
        wp_send_json_success( [
            'galleries' => array_map( fn( $g ) => $this->format_post( $g, 'gallery' ), VM_Relations::get_galleries( 'vitrine', $vitrine_id ) ),
            'objects'   => array_map( fn( $o ) => $this->format_post( $o, 'object' ), VM_Relations::get_objects( 'vitrine', $vitrine_id ) ),
        ] );
    }

    public function get_gallery_objects(): void {
        $gallery_id = (int) ( $_GET['gallery_id'] ?? 0 );
        if ( ! $gallery_id ) { wp_send_json_error( [], 400 ); }
        wp_send_json_success( array_map( fn( $o ) => $this->format_post( $o, 'object' ), VM_Relations::get_objects( 'gallery', $gallery_id ) ) );
    }

    public function get_object_contexts(): void {
        $object_id = (int) ( $_GET['object_id'] ?? 0 );
        if ( ! $object_id ) { wp_send_json_error( [], 400 ); }
        wp_send_json_success( [
            'rooms'    => array_map( fn( $r ) => [ 'id' => $r->ID, 'title' => get_the_title( $r ), 'url' => get_permalink( $r->ID ) ], VM_Relations::get_all_rooms_for( 'object', $object_id ) ),
            'galleries'=> array_map( fn( $g ) => [ 'id' => $g->ID, 'title' => get_the_title( $g ), 'url' => get_permalink( $g->ID ) ], VM_Relations::get_parents( 'object', $object_id, 'gallery' ) ),
            'vitrines' => array_map( fn( $v ) => [ 'id' => $v->ID, 'title' => get_the_title( $v ), 'url' => get_permalink( $v->ID ) ], VM_Relations::get_parents( 'object', $object_id, 'vitrine' ) ),
        ] );
    }

    public function search(): void {
        $query      = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
        $type       = sanitize_text_field( wp_unslash( $_GET['type'] ?? 'all' ) );
        $page       = max( 1, (int) ( $_GET['page'] ?? 1 ) );

        $post_types = match( $type ) {
            'rooms'     => [ 'museum_room' ],
            'galleries' => [ 'museum_gallery' ],
            'vitrines'  => [ 'museum_vitrine' ],
            'objects'   => [ 'museum_object' ],
            default     => [ 'museum_room', 'museum_gallery', 'museum_vitrine', 'museum_object' ],
        };

        $wp_query = new WP_Query( [
            's'              => $query,
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'paged'          => $page,
        ] );

        $results = array_map( fn( $post ) => $this->format_post( $post, VM_Relations::cpt_to_type( $post->post_type ) ), $wp_query->posts );

        wp_send_json_success( [
            'results' => $results,
            'total'   => $wp_query->found_posts,
            'pages'   => $wp_query->max_num_pages,
            'current' => $page,
        ] );
    }

    public function load_objects_page(): void {
        $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
        if ( $nonce && ! wp_verify_nonce( $nonce, 'vm_lazy_load' ) ) {
            wp_send_json_error( [], 403 );
        }

        $page        = max( 1, (int) ( $_POST['page'] ?? 1 ) );
        $per_page    = min( 48, max( 4, (int) ( $_POST['per_page'] ?? 12 ) ) );
        $parent_type = sanitize_key( $_POST['parent_type'] ?? '' );
        $parent_id   = (int) ( $_POST['parent_id'] ?? 0 );
        $child_type  = sanitize_key( $_POST['child_type'] ?? 'object' );

        if ( $parent_type && $parent_id ) {
            $all_posts   = VM_Relations::get_children( $parent_type, $parent_id, $child_type, true );
            $total       = count( $all_posts );
            $posts       = array_slice( $all_posts, ( $page - 1 ) * $per_page, $per_page );
            $total_pages = (int) ceil( $total / $per_page );
        } else {
            $wp_query = new WP_Query( [
                'post_type'      => VM_Relations::type_to_cpt( $child_type ),
                'post_status'    => 'publish',
                'posts_per_page' => $per_page,
                'paged'          => $page,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
            ] );
            $posts       = $wp_query->posts;
            $total       = (int) $wp_query->found_posts;
            $total_pages = (int) $wp_query->max_num_pages;
        }

        $meta_only = ! empty( $_POST['meta_only'] );
        $html      = '';

        if ( ! $meta_only ) {
            $template = VM_PLUGIN_DIR . 'public/templates/partials/card-object.php';
            if ( $posts && file_exists( $template ) ) {
                global $post;
                foreach ( $posts as $post ) {
                    setup_postdata( $post );
                    ob_start();
                    include $template;
                    $html .= ob_get_clean();
                }
                wp_reset_postdata();
            }
        }

        wp_send_json_success( [
            'html'        => $html,
            'page'        => $page,
            'total_pages' => $total_pages,
            'total'       => $total,
        ] );
    }

    private function format_post( WP_Post $post, string $type ): array {
        $data = [
            'id'      => $post->ID,
            'type'    => $type,
            'title'   => get_the_title( $post ),
            'excerpt' => get_the_excerpt( $post ),
            'url'     => get_permalink( $post->ID ),
            'thumb'   => has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, 'medium' ) : '',
        ];
        if ( $type === 'object' ) {
            $data['media_type'] = get_post_meta( $post->ID, 'vm_media_type', true );
            $data['year']       = get_post_meta( $post->ID, 'vm_year', true );
            $data['copyright']  = get_post_meta( $post->ID, 'vm_copyright', true );
        }
        return $data;
    }
}
