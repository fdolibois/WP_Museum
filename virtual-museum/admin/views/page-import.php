<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Massenimport', 'vmuseum' ); ?></h1>
    <?php
    if ( isset( $_POST['vm_import_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_import_nonce'] ) ), 'vm_bulk_import' ) && current_user_can( 'upload_files' ) ) {
        if ( ! empty( $_FILES['import_file']['tmp_name'] ) ) {
            $importer = new VM_Bulk_Import();
            $report   = $importer->import_csv( $_FILES['import_file']['tmp_name'] );
            echo '<div class="notice notice-' . esc_attr( $report['status'] ) . '">';
            echo '<p><strong>' . esc_html( $report['message'] ) . '</strong></p>';
            if ( ! empty( $report['details'] ) ) {
                echo '<ul style="max-height:300px;overflow:auto">';
                foreach ( array_slice( $report['details'], 0, 100 ) as $line ) {
                    echo '<li>' . esc_html( $line ) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        }
    }
    ?>
    <div class="vm-import-section">
        <h2><?php esc_html_e( 'CSV-Import', 'vmuseum' ); ?></h2>
        <p><?php esc_html_e( 'Importieren Sie Räume, Vitrinen, Galerien und Objekte inkl. ihrer Beziehungen aus einer CSV-Datei.', 'vmuseum' ); ?></p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'vm_bulk_import', 'vm_import_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="import_file"><?php esc_html_e( 'CSV-Datei', 'vmuseum' ); ?></label></th>
                    <td>
                        <input type="file" id="import_file" name="import_file" accept=".csv,.txt">
                        <p class="description"><?php esc_html_e( 'UTF-8 kodierte CSV-Datei.', 'vmuseum' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Import starten', 'vmuseum' ) ); ?>
        </form>
        <h3><?php esc_html_e( 'CSV-Format', 'vmuseum' ); ?></h3>
        <pre class="vm-code-example">type,title,description,in_rooms,in_galleries,in_vitrines,media_type,year,image_url
room,Ortsgeschichte,"Geschichte von Oberpleis",,,,,,
vitrine,Stadtentwicklung,"Dokumente","Ortsgeschichte",,,,,
gallery,Marktplatz-Ansichten,"Historische Ansichten","Ortsgeschichte","","Stadtentwicklung",,,
object,Rathaus 1904,"Das alte Rathaus","Ortsgeschichte","Marktplatz-Ansichten","Stadtentwicklung",image,1904,https://example.com/rathaus.jpg</pre>
    </div>
</div>
