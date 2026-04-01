<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Massenimport', 'vmuseum' ); ?></h1>
    <?php
    if ( isset( $_POST['vm_import_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_import_nonce'] ) ), 'vm_bulk_import' ) && current_user_can( 'upload_files' ) ) {
        if ( ! empty( $_FILES['import_file']['tmp_name'] ) ) {
            // B008: Sicherheitsprüfungen für den Datei-Upload
            $upload_error = null;
            if ( ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
                $upload_error = __( 'Ungültige Datei-Upload-Quelle.', 'vmuseum' );
            } elseif ( filesize( $_FILES['import_file']['tmp_name'] ) > 5 * 1024 * 1024 ) {
                $upload_error = __( 'Datei zu groß. Maximum: 5 MB.', 'vmuseum' );
            } else {
                $file_type = wp_check_filetype( sanitize_file_name( $_FILES['import_file']['name'] ) );
                if ( ! in_array( $file_type['ext'], [ 'csv', 'txt' ], true ) ) {
                    $upload_error = __( 'Nur CSV- oder TXT-Dateien sind erlaubt.', 'vmuseum' );
                }
            }
            if ( $upload_error ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $upload_error ) . '</p></div>';
            } else {
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
            } // end upload_error check
        }
    }
    ?>

    <!-- CSV Import -->
    <div class="vm-import-section">
        <h2><?php esc_html_e( 'CSV-Import', 'vmuseum' ); ?></h2>
        <p><?php esc_html_e( 'Importieren Sie Räume, Vitrinen, Galerien und Objekte inkl. ihrer Beziehungen aus einer CSV-Datei.', 'vmuseum' ); ?></p>
        <p><?php esc_html_e( 'Bilder werden beim Import automatisch heruntergeladen und als Beitragsbild gesetzt, sofern eine image_url angegeben ist.', 'vmuseum' ); ?></p>
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

    <hr>

    <!-- Pending Image Processor -->
    <div class="vm-import-section" id="vm-image-processor">
        <h2><?php esc_html_e( 'Beitragsbilder verarbeiten', 'vmuseum' ); ?></h2>
        <p>
            <?php esc_html_e( 'Objekte, bei denen eine image_url gespeichert ist, aber noch kein Beitragsbild gesetzt wurde, können hier nachbearbeitet werden.', 'vmuseum' ); ?><br>
            <?php esc_html_e( 'Bilder werden in kleinen Gruppen heruntergeladen, um Server-Timeouts zu vermeiden.', 'vmuseum' ); ?>
        </p>

        <p id="vm-pending-info" style="font-style:italic;color:#666"><?php esc_html_e( 'Lade Status…', 'vmuseum' ); ?></p>

        <div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem">
            <button id="vm-start-images" class="button button-primary" disabled>
                <?php esc_html_e( 'Bilder verarbeiten', 'vmuseum' ); ?>
            </button>
            <label>
                <?php esc_html_e( 'Bilder pro Durchlauf:', 'vmuseum' ); ?>
                <select id="vm-batch-size" style="margin-left:4px">
                    <option value="3">3</option>
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                </select>
            </label>
        </div>

        <div id="vm-image-progress" style="display:none;margin-bottom:1rem">
            <div style="background:#e0e0e0;border-radius:4px;overflow:hidden;height:20px;width:400px;max-width:100%">
                <div id="vm-progress-bar" style="height:100%;background:#0073aa;width:0;transition:width .3s"></div>
            </div>
            <p id="vm-progress-text" style="margin:.4rem 0 0;font-size:.85rem"></p>
        </div>

        <div id="vm-image-log" style="display:none;background:#f6f7f7;border:1px solid #ddd;padding:.75rem 1rem;max-height:200px;overflow:auto;font-size:.8rem;font-family:monospace"></div>
    </div>
</div>

<script>
(function($) {
    var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'vm_admin_nonce' ) ); ?>;
    var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var total   = 0;
    var done    = 0;
    var running = false;

    function log(msg) {
        var $log = $('#vm-image-log');
        $log.show().append($('<div>').text(msg));
        $log.scrollTop($log[0].scrollHeight);
    }

    function updateProgress(remaining) {
        if (total === 0) return;
        var pct = Math.round(((total - remaining) / total) * 100);
        $('#vm-progress-bar').css('width', pct + '%');
        $('#vm-progress-text').text((total - remaining) + ' / ' + total + ' verarbeitet (' + pct + '%)');
    }

    // Load pending count on page load
    $.post(ajaxUrl, { action: 'vm_pending_images', nonce: nonce }, function(res) {
        if (!res.success) return;
        var count = res.data.count;
        total = count;
        if (count === 0) {
            $('#vm-pending-info').text(<?php echo wp_json_encode( __( 'Alle Beitragsbilder sind bereits gesetzt — nichts zu tun.', 'vmuseum' ) ); ?>);
        } else {
            $('#vm-pending-info').text(count + ' ' + <?php echo wp_json_encode( __( 'Objekte ohne Beitragsbild gefunden.', 'vmuseum' ) ); ?>);
            $('#vm-start-images').prop('disabled', false);
        }
    });

    $('#vm-start-images').on('click', function() {
        if (running) return;
        running = true;
        $(this).prop('disabled', true).text(<?php echo wp_json_encode( __( 'Läuft…', 'vmuseum' ) ); ?>);
        $('#vm-image-progress').show();
        log(<?php echo wp_json_encode( __( 'Starte Bildverarbeitung…', 'vmuseum' ) ); ?>);
        done = 0;
        processBatch();
    });

    function processBatch() {
        var batch = parseInt($('#vm-batch-size').val(), 10);
        $.post(ajaxUrl, { action: 'vm_process_images', nonce: nonce, batch: batch }, function(res) {
            if (!res.success) {
                log('Fehler: ' + (res.data || 'Unbekannt'));
                finish();
                return;
            }
            var d = res.data;
            done += d.processed;
            var msg = d.processed + ' verarbeitet';
            if (d.failed && d.failed.length) msg += ', ' + d.failed.length + ' Fehler (IDs: ' + d.failed.join(', ') + ')';
            msg += ' — noch ' + d.remaining + ' ausstehend';
            log(msg);
            updateProgress(d.remaining);

            if (d.done) {
                log(<?php echo wp_json_encode( __( 'Fertig! Alle Bilder wurden verarbeitet.', 'vmuseum' ) ); ?>);
                finish();
            } else {
                // Slight pause to avoid hammering the server
                setTimeout(processBatch, 800);
            }
        }).fail(function() {
            log('HTTP-Fehler. Abgebrochen.');
            finish();
        });
    }

    function finish() {
        running = false;
        $('#vm-start-images').prop('disabled', false).text(<?php echo wp_json_encode( __( 'Bilder verarbeiten', 'vmuseum' ) ); ?>);
    }
})(jQuery);
</script>
