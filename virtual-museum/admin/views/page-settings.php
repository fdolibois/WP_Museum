<?php if ( ! defined( 'ABSPATH' ) ) exit;
$s = get_option( 'vm_settings', [] );

// Handle "recreate museum page" action
if ( isset( $_POST['vm_recreate_page'] ) && check_admin_referer( 'vm_recreate_page' ) ) {
    // Force recreation by clearing the stored ID
    $s['museum_page_id'] = 0;
    update_option( 'vm_settings', $s );
    VM_Activator::create_museum_page();
    $s = get_option( 'vm_settings', [] );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Museumsseite wurde neu erstellt.', 'vmuseum' ) . '</p></div>';
}

$museum_page_id = (int) ( $s['museum_page_id'] ?? 0 );
$museum_page    = $museum_page_id ? get_post( $museum_page_id ) : null;
?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Museum Einstellungen', 'vmuseum' ); ?></h1>

    <!-- Museum Entrance Page Info -->
    <div class="vm-rel-stats" style="margin-bottom:20px">
        <strong><?php esc_html_e( 'Museumsseite (Eingangsseite):', 'vmuseum' ); ?></strong>
        <?php if ( $museum_page && $museum_page->post_status === 'publish' ) : ?>
            &nbsp;
            <a href="<?php echo esc_url( get_permalink( $museum_page_id ) ); ?>" target="_blank">
                <?php echo esc_html( get_the_title( $museum_page_id ) ); ?>
            </a>
            &nbsp;|&nbsp;
            <a href="<?php echo esc_url( get_edit_post_link( $museum_page_id ) ); ?>">
                <?php esc_html_e( 'Seite bearbeiten', 'vmuseum' ); ?>
            </a>
        <?php else : ?>
            <span style="color:#b32d2e"><?php esc_html_e( 'Nicht gefunden', 'vmuseum' ); ?></span>
        <?php endif; ?>
        &nbsp;&nbsp;
        <form method="post" style="display:inline">
            <?php wp_nonce_field( 'vm_recreate_page' ); ?>
            <button type="submit" name="vm_recreate_page" class="button" onclick="return confirm('<?php esc_attr_e( 'Museumsseite neu erstellen?', 'vmuseum' ); ?>')">
                <?php esc_html_e( 'Seite neu erstellen', 'vmuseum' ); ?>
            </button>
        </form>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'vm_save_settings', 'vm_settings_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th><label for="archive_per_page"><?php esc_html_e( 'Objekte pro Seite', 'vmuseum' ); ?></label></th>
                <td><input type="number" id="archive_per_page" name="archive_per_page" value="<?php echo esc_attr( $s['archive_per_page'] ?? 24 ); ?>" min="1" max="200" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="default_room_layout"><?php esc_html_e( 'Standard Raum-Layout', 'vmuseum' ); ?></label></th>
                <td>
                    <select id="default_room_layout" name="default_room_layout">
                        <?php foreach ( [ 'sections', 'flat', 'tabs' ] as $opt ) : ?>
                            <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $s['default_room_layout'] ?? 'sections', $opt ); ?>><?php echo esc_html( ucfirst( $opt ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="default_gallery_mode"><?php esc_html_e( 'Standard Galerie-Modus', 'vmuseum' ); ?></label></th>
                <td>
                    <select id="default_gallery_mode" name="default_gallery_mode">
                        <?php foreach ( [ 'slider', 'masonry', 'grid', 'filmstrip' ] as $opt ) : ?>
                            <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $s['default_gallery_mode'] ?? 'slider', $opt ); ?>><?php echo esc_html( ucfirst( $opt ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Features', 'vmuseum' ); ?></th>
                <td>
                    <label><input type="checkbox" name="enable_lightbox"       value="1" <?php checked( ! empty( $s['enable_lightbox'] ) ); ?>>    <?php esc_html_e( 'Lightbox aktivieren', 'vmuseum' ); ?></label><br>
                    <label><input type="checkbox" name="enable_360"            value="1" <?php checked( ! empty( $s['enable_360'] ) ); ?>>          <?php esc_html_e( '360° Panorama aktivieren', 'vmuseum' ); ?></label><br>
                    <label><input type="checkbox" name="enable_breadcrumb"     value="1" <?php checked( ! empty( $s['enable_breadcrumb'] ) ); ?>>   <?php esc_html_e( 'Kontext-Breadcrumb aktivieren', 'vmuseum' ); ?></label><br>
                    <label><input type="checkbox" name="show_relation_badge"   value="1" <?php checked( ! empty( $s['show_relation_badge'] ) ); ?>><?php esc_html_e( '"Auch zu finden in" Badge', 'vmuseum' ); ?></label><br>
                    <label><input type="checkbox" name="enable_rest_api"       value="1" <?php checked( ! empty( $s['enable_rest_api'] ) ); ?>>    <?php esc_html_e( 'REST-API aktivieren', 'vmuseum' ); ?></label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Deinstallation', 'vmuseum' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="uninstall_delete_data" value="1" <?php checked( ! empty( $s['uninstall_delete_data'] ) ); ?>>
                        <strong><?php esc_html_e( 'Alle Daten bei Deinstallation löschen', 'vmuseum' ); ?></strong>
                    </label>
                    <p class="description"><?php esc_html_e( 'Achtung: Löscht alle Museumsdaten unwiderruflich!', 'vmuseum' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Einstellungen speichern', 'vmuseum' ) ); ?>
    </form>
</div>
