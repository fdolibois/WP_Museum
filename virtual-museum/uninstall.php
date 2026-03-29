<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$delete_data = get_option( 'vm_uninstall_delete_data', false );

if ( $delete_data ) {
    // Remove custom tables
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vm_relations" );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vm_search_index" );

    // Remove all CPT posts
    $post_types = [ 'museum_room', 'museum_gallery', 'museum_vitrine', 'museum_object' ];
    foreach ( $post_types as $pt ) {
        $posts = get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any' ] );
        foreach ( $posts as $post ) {
            wp_delete_post( $post->ID, true );
        }
    }

    // Remove options
    $options = [
        'vm_db_version', 'vm_settings', 'vm_uninstall_delete_data',
        'vm_default_room_layout', 'vm_default_gallery_mode',
    ];
    foreach ( $options as $option ) {
        delete_option( $option );
    }
}
