<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="vm-filter-bar" id="vm-filter-bar">
    <div class="vm-filter-bar__inner">
        <div class="vm-filter-group">
            <label for="vm-filter-era"><?php esc_html_e( 'Epoche', 'vmuseum' ); ?></label>
            <select id="vm-filter-era" class="vm-filter-select" data-filter="era">
                <option value=""><?php esc_html_e( 'Alle Epochen', 'vmuseum' ); ?></option>
                <?php
                $eras = get_terms( [ 'taxonomy' => 'museum_era', 'hide_empty' => true ] );
                foreach ( $eras as $era ) :
                ?>
                    <option value="<?php echo esc_attr( $era->slug ); ?>"><?php echo esc_html( $era->name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="vm-filter-group">
            <label for="vm-filter-type"><?php esc_html_e( 'Medientyp', 'vmuseum' ); ?></label>
            <select id="vm-filter-type" class="vm-filter-select" data-filter="media_type">
                <option value=""><?php esc_html_e( 'Alle Typen', 'vmuseum' ); ?></option>
                <option value="image"><?php esc_html_e( 'Bilder', 'vmuseum' ); ?></option>
                <option value="audio"><?php esc_html_e( 'Audio', 'vmuseum' ); ?></option>
                <option value="video"><?php esc_html_e( 'Video', 'vmuseum' ); ?></option>
                <option value="360"><?php esc_html_e( '360° Panorama', 'vmuseum' ); ?></option>
                <option value="document"><?php esc_html_e( 'Dokumente', 'vmuseum' ); ?></option>
            </select>
        </div>
        <div class="vm-filter-group vm-filter-group--search">
            <label for="vm-live-search"><?php esc_html_e( 'Suche', 'vmuseum' ); ?></label>
            <input type="search" id="vm-live-search" class="vm-search-input"
                   placeholder="<?php esc_attr_e( 'Titel, Beschreibung...', 'vmuseum' ); ?>"
                   autocomplete="off">
        </div>
        <button type="button" class="button vm-filter-reset" id="vm-filter-reset">
            <?php esc_html_e( 'Zurücksetzen', 'vmuseum' ); ?>
        </button>
    </div>
    <div id="vm-search-results" class="vm-search-results" hidden></div>
</div>
