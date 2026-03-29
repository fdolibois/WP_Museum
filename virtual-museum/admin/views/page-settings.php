<?php if ( ! defined( 'ABSPATH' ) ) exit;
$s = get_option( 'vm_settings', [] );
?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Museum Einstellungen', 'vmuseum' ); ?></h1>
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
