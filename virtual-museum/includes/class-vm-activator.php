<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Activator {

    public static function activate(): void {
        self::create_tables();
        self::set_default_options();

        // Register post types before flushing rewrite rules
        $post_types = new VM_Post_Types();
        $post_types->register();
        flush_rewrite_rules();

        self::create_museum_page();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * Creates the museum entrance page if it does not yet exist.
     * Stores the page ID in vm_settings['museum_page_id'].
     */
    public static function create_museum_page(): void {
        $settings = get_option( 'vm_settings', [] );

        // If a valid page already exists, skip.
        if ( ! empty( $settings['museum_page_id'] ) ) {
            $existing = get_post( (int) $settings['museum_page_id'] );
            if ( $existing && $existing->post_status !== 'trash' ) {
                return;
            }
        }

        $page_id = wp_insert_post( [
            'post_title'     => __( 'Virtuelles Museum', 'vmuseum' ),
            'post_name'      => 'virtuelles-museum',
            'post_content'   => '',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ] );

        if ( $page_id && ! is_wp_error( $page_id ) ) {
            // Mark it as the museum entrance page
            update_post_meta( $page_id, '_vm_is_entrance_page', '1' );

            $settings['museum_page_id'] = $page_id;
            update_option( 'vm_settings', $settings );
        }
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql_relations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vm_relations (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_type   ENUM('room','vitrine','gallery') NOT NULL,
            parent_id     BIGINT UNSIGNED NOT NULL,
            child_type    ENUM('object','gallery','vitrine') NOT NULL,
            child_id      BIGINT UNSIGNED NOT NULL,
            position      SMALLINT UNSIGNED DEFAULT 0,
            added_by      BIGINT UNSIGNED,
            added_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY   (id),
            UNIQUE KEY    unique_relation (parent_type, parent_id, child_type, child_id),
            KEY           parent_lookup (parent_type, parent_id, position),
            KEY           child_lookup  (child_type, child_id),
            KEY           child_id_idx  (child_id)
        ) ENGINE=InnoDB $charset;";

        $sql_search = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vm_search_index (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id       BIGINT UNSIGNED NOT NULL,
            post_type     ENUM('room','gallery','vitrine','object') NOT NULL,
            title         VARCHAR(400),
            search_text   LONGTEXT,
            era_slug      VARCHAR(200),
            year_start    SMALLINT,
            year_end      SMALLINT,
            media_type    VARCHAR(50),
            room_ids      TEXT,
            gallery_ids   TEXT,
            vitrine_ids   TEXT,
            image_count   SMALLINT DEFAULT 0,
            updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY   (id),
            UNIQUE KEY    post_id (post_id),
            KEY           post_type (post_type),
            KEY           era_slug (era_slug),
            FULLTEXT KEY  fulltext_search (title, search_text)
        ) ENGINE=InnoDB $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_relations );
        dbDelta( $sql_search );

        update_option( 'vm_db_version', VM_PLUGIN_VERSION );
    }

    private static function set_default_options(): void {
        $defaults = [
            'vm_settings' => [
                'archive_per_page'         => 24,
                'default_room_layout'      => 'sections',
                'default_gallery_mode'     => 'slider',
                'default_vitrine_layout'   => 'showcase',
                'default_vitrine_theme'    => 'light',
                'enable_lightbox'          => true,
                'enable_360'               => true,
                'enable_breadcrumb'        => true,
                'show_relation_badge'      => true,
                'enable_rest_api'          => true,
                'uninstall_delete_data'    => false,
            ],
        ];
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}
