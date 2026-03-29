<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_menu(): void {
        $icon = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#a7aaad" d="M10 1L1 6v1h18V6L10 1zM2 8v9h2V8H2zm4 0v9h2V8H6zm4 0v9h2V8h-2zm4 0v9h2V8h-2zm4 0v9h-1V8h1zM1 18v1h18v-1H1z"/></svg>'
        );

        add_menu_page( __( 'Virtuelles Museum', 'vmuseum' ), __( 'Virt. Museum', 'vmuseum' ), 'edit_posts', 'vm-dashboard', [ $this, 'page_dashboard' ], $icon, 23 );

        add_submenu_page( 'vm-dashboard', __( 'Dashboard', 'vmuseum' ),       __( 'Dashboard', 'vmuseum' ),       'edit_posts',     'vm-dashboard',    [ $this, 'page_dashboard' ] );
        add_submenu_page( 'vm-dashboard', __( 'Räume', 'vmuseum' ),            __( 'Räume', 'vmuseum' ),            'edit_posts',     'edit.php?post_type=museum_room',    null );
        add_submenu_page( 'vm-dashboard', __( 'Vitrinen', 'vmuseum' ),         __( 'Vitrinen', 'vmuseum' ),         'edit_posts',     'edit.php?post_type=museum_vitrine', null );
        add_submenu_page( 'vm-dashboard', __( 'Galerien', 'vmuseum' ),         __( 'Galerien', 'vmuseum' ),         'edit_posts',     'edit.php?post_type=museum_gallery', null );
        add_submenu_page( 'vm-dashboard', __( 'Objekte', 'vmuseum' ),          __( 'Objekte', 'vmuseum' ),          'edit_posts',     'edit.php?post_type=museum_object',  null );
        add_submenu_page( 'vm-dashboard', __( 'Beziehungen', 'vmuseum' ),      __( 'Beziehungen', 'vmuseum' ),      'edit_posts',     'vm-relations',    [ $this, 'page_relations' ] );
        add_submenu_page( 'vm-dashboard', __( 'Beziehungskarte', 'vmuseum' ),  __( 'Beziehungskarte', 'vmuseum' ),  'edit_posts',     'vm-relation-map', [ $this, 'page_relation_map' ] );
        add_submenu_page( 'vm-dashboard', __( 'Massenimport', 'vmuseum' ),     __( 'Massenimport', 'vmuseum' ),     'upload_files',   'vm-import',       [ $this, 'page_import' ] );
        add_submenu_page( 'vm-dashboard', __( 'Einstellungen', 'vmuseum' ),    __( 'Einstellungen', 'vmuseum' ),    'manage_options', 'vm-settings',     [ $this, 'page_settings' ] );
        add_submenu_page( 'vm-dashboard', __( 'Statistiken', 'vmuseum' ),      __( 'Statistiken', 'vmuseum' ),      'edit_posts',     'vm-statistics',   [ $this, 'page_statistics' ] );
    }

    public function enqueue_assets( string $hook ): void {
        $screen = get_current_screen();
        $is_vm  = $screen && in_array( $screen->post_type, [ 'museum_room', 'museum_vitrine', 'museum_gallery', 'museum_object' ], true );
        $is_vm  = $is_vm || strpos( $hook, 'vm-' ) !== false || strpos( $hook, 'toplevel_page_vm' ) !== false;

        if ( ! $is_vm ) return;

        wp_enqueue_style( 'vm-admin', VM_PLUGIN_URL . 'admin/assets/admin.css', [], VM_PLUGIN_VERSION );

        wp_enqueue_script( 'vm-admin', VM_PLUGIN_URL . 'admin/assets/admin.js', [ 'jquery' ], VM_PLUGIN_VERSION, true );

        wp_localize_script( 'vm-admin', 'vmAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'vm_admin_nonce' ),
            'i18n'    => [
                'confirmRemove'     => __( 'Diese Verknüpfung wirklich entfernen?', 'vmuseum' ),
                'searchPlaceholder' => __( 'Titel eingeben...', 'vmuseum' ),
                'noResults'         => __( 'Keine Ergebnisse', 'vmuseum' ),
                'alreadyAdded'      => __( 'Bereits hinzugefügt', 'vmuseum' ),
                'add'               => __( 'Hinzufügen', 'vmuseum' ),
                'saving'            => __( 'Speichere...', 'vmuseum' ),
                'saved'             => __( 'Gespeichert', 'vmuseum' ),
                'error'             => __( 'Fehler', 'vmuseum' ),
            ],
        ] );

        wp_enqueue_script( 'vm-relation-editor', VM_PLUGIN_URL . 'admin/assets/relation-editor.js', [ 'vm-admin' ], VM_PLUGIN_VERSION, true );
    }

    public function page_dashboard():    void { include VM_PLUGIN_DIR . 'admin/views/page-dashboard.php'; }
    public function page_relations():    void { include VM_PLUGIN_DIR . 'admin/views/page-relations.php'; }
    public function page_relation_map(): void { include VM_PLUGIN_DIR . 'admin/views/page-relation-map.php'; }
    public function page_import():       void { include VM_PLUGIN_DIR . 'admin/views/page-import.php'; }
    public function page_statistics():   void { include VM_PLUGIN_DIR . 'admin/views/page-statistics.php'; }

    public function page_settings(): void {
        if ( isset( $_POST['vm_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_settings_nonce'] ) ), 'vm_save_settings' ) ) {
            $this->save_settings();
        }
        include VM_PLUGIN_DIR . 'admin/views/page-settings.php';
    }

    private function save_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $s = get_option( 'vm_settings', [] );
        $s['archive_per_page']       = (int) ( $_POST['archive_per_page'] ?? 24 );
        $s['default_room_layout']    = sanitize_text_field( wp_unslash( $_POST['default_room_layout'] ?? 'sections' ) );
        $s['default_gallery_mode']   = sanitize_text_field( wp_unslash( $_POST['default_gallery_mode'] ?? 'slider' ) );
        $s['default_vitrine_layout'] = sanitize_text_field( wp_unslash( $_POST['default_vitrine_layout'] ?? 'showcase' ) );
        $s['default_vitrine_theme']  = sanitize_text_field( wp_unslash( $_POST['default_vitrine_theme'] ?? 'light' ) );
        $s['enable_lightbox']        = isset( $_POST['enable_lightbox'] );
        $s['enable_360']             = isset( $_POST['enable_360'] );
        $s['enable_breadcrumb']      = isset( $_POST['enable_breadcrumb'] );
        $s['show_relation_badge']    = isset( $_POST['show_relation_badge'] );
        $s['enable_rest_api']        = isset( $_POST['enable_rest_api'] );
        $s['uninstall_delete_data']  = isset( $_POST['uninstall_delete_data'] );
        update_option( 'vm_settings', $s );
        update_option( 'vm_uninstall_delete_data', $s['uninstall_delete_data'] );
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Einstellungen gespeichert.', 'vmuseum' ) . '</p></div>';
        } );
    }
}
