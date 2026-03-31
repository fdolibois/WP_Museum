<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Plugin {

    private static ?VM_Plugin $instance = null;

    public static function get_instance(): VM_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_components();
    }

    private function init_components(): void {
        // Core
        new VM_Post_Types();
        new VM_Meta_Boxes();
        new VM_Shortcodes();
        new VM_Ajax();
        new VM_Rest_Api();
        new VM_Search_Index();
        new VM_Widgets();

        // Admin
        if ( is_admin() ) {
            new VM_Admin();
        }

        // Public
        if ( ! is_admin() ) {
            new VM_Public();
        }

        // Common hooks
        add_action( 'before_delete_post', [ 'VM_Relations', 'remove_all_for_post' ] );
        add_action( 'save_post',          [ 'VM_Relations', 'flush_cache' ] );

        // Ensure museum entrance page exists (catches already-active installations after update)
        add_action( 'init', [ 'VM_Activator', 'create_museum_page' ] );
    }
}
