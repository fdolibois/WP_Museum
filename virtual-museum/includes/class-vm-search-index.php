<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Search_Index {

    public function __construct() {
        add_action( 'save_post',       [ $this, 'on_save_post' ], 20, 2 );
        add_action( 'delete_post',     [ $this, 'on_delete_post' ] );
        add_action( 'vm_relation_added',   [ $this, 'on_relation_change' ], 10, 5 );
        add_action( 'vm_relation_removed', [ $this, 'on_relation_removed' ], 10, 4 );
    }

    public function on_save_post( int $post_id, WP_Post $post ): void {
        if ( in_array( $post->post_type, [ 'museum_room', 'museum_gallery', 'museum_vitrine', 'museum_object' ], true ) ) {
            self::update_post( $post_id );
        }
    }

    public function on_delete_post( int $post_id ): void {
        self::delete_post( $post_id );
    }

    public function on_relation_change( int $relation_id, string $parent_type, int $parent_id, string $child_type, int $child_id ): void {
        self::update_post( $parent_id );
        self::update_post( $child_id );
    }

    public function on_relation_removed( string $parent_type, int $parent_id, string $child_type, int $child_id ): void {
        self::update_post( $parent_id );
        self::update_post( $child_id );
    }

    public static function update_post( int $post_id ): void {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return;
        }
        if ( ! in_array( $post->post_type, [ 'museum_room', 'museum_gallery', 'museum_vitrine', 'museum_object' ], true ) ) {
            return;
        }

        $type        = VM_Relations::cpt_to_type( $post->post_type );
        $search_text = strip_tags( $post->post_content . ' ' . $post->post_excerpt );

        foreach ( [ 'vm_room_era', 'vm_vitrine_description', 'vm_copyright' ] as $field ) {
            $val = get_post_meta( $post_id, $field, true );
            if ( $val ) $search_text .= ' ' . $val;
        }

        $era_terms = get_the_terms( $post_id, 'museum_era' );
        $era_slug  = ( $era_terms && ! is_wp_error( $era_terms ) )
            ? implode( ',', array_column( $era_terms, 'slug' ) )
            : '';

        $year_start  = (int) get_post_meta( $post_id, 'vm_year', true );
        $year_end    = (int) get_post_meta( $post_id, 'vm_year_end', true );
        $media_type  = get_post_meta( $post_id, 'vm_media_type', true );
        $room_ids    = $type !== 'room' ? array_column( VM_Relations::get_all_rooms_for( $type, $post_id ), 'ID' ) : [];
        $gallery_ids = VM_Relations::get_parents( $type, $post_id, 'gallery', false );
        $vitrine_ids = VM_Relations::get_parents( $type, $post_id, 'vitrine', false );
        $image_count = 0;

        if ( in_array( $type, [ 'gallery', 'vitrine' ], true ) ) {
            $image_count = count( VM_Relations::get_objects( $type, $post_id ) );
        }

        global $wpdb;
        $wpdb->replace( $wpdb->prefix . 'vm_search_index', [
            'post_id'     => $post_id,
            'post_type'   => $type,
            'title'       => mb_substr( get_the_title( $post ), 0, 400 ),
            'search_text' => $search_text,
            'era_slug'    => mb_substr( $era_slug, 0, 200 ),
            'year_start'  => $year_start ?: null,
            'year_end'    => $year_end ?: null,
            'media_type'  => mb_substr( (string) $media_type, 0, 50 ),
            'room_ids'    => wp_json_encode( $room_ids ),
            'gallery_ids' => wp_json_encode( $gallery_ids ),
            'vitrine_ids' => wp_json_encode( $vitrine_ids ),
            'image_count' => $image_count,
        ], [ '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' ] );
    }

    public static function delete_post( int $post_id ): void {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'vm_search_index', [ 'post_id' => $post_id ], [ '%d' ] );
    }

    public static function rebuild_all(): void {
        foreach ( [ 'museum_room', 'museum_gallery', 'museum_vitrine', 'museum_object' ] as $pt ) {
            $posts = get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'publish' ] );
            foreach ( $posts as $post ) {
                self::update_post( $post->ID );
            }
        }
    }
}
