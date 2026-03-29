<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Meta_Boxes {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_all_meta_boxes' ] );
        add_action( 'save_post',      [ $this, 'save_meta_boxes' ], 10, 2 );
    }

    public function add_all_meta_boxes(): void {
        add_meta_box( 'vm_room_details',      __( 'Raum-Details', 'vmuseum' ),           [ $this, 'render_room_details' ],      'museum_room',    'side' );
        add_meta_box( 'vm_room_relations',    __( 'Inhalte & Verknüpfungen', 'vmuseum' ), [ $this, 'render_room_relations' ],    'museum_room',    'normal', 'high' );
        add_meta_box( 'vm_vitrine_details',   __( 'Vitrinen-Details', 'vmuseum' ),        [ $this, 'render_vitrine_details' ],   'museum_vitrine', 'side' );
        add_meta_box( 'vm_vitrine_relations', __( 'Inhalte & Verknüpfungen', 'vmuseum' ), [ $this, 'render_vitrine_relations' ], 'museum_vitrine', 'normal', 'high' );
        add_meta_box( 'vm_gallery_details',   __( 'Galerie-Details', 'vmuseum' ),         [ $this, 'render_gallery_details' ],   'museum_gallery', 'side' );
        add_meta_box( 'vm_gallery_relations', __( 'Objekte dieser Galerie', 'vmuseum' ),  [ $this, 'render_gallery_relations' ], 'museum_gallery', 'normal', 'high' );
        add_meta_box( 'vm_object_details',    __( 'Objekt-Details', 'vmuseum' ),          [ $this, 'render_object_details' ],    'museum_object',  'side' );
        add_meta_box( 'vm_object_relations',  __( 'Verwendung & Verlinkung', 'vmuseum' ), [ $this, 'render_object_relations' ],  'museum_object',  'normal', 'high' );
    }

    public function render_room_details( WP_Post $post ): void {
        wp_nonce_field( 'vm_meta_room_' . $post->ID, 'vm_meta_room_nonce' );
        $color = get_post_meta( $post->ID, 'vm_room_color', true );
        $era   = get_post_meta( $post->ID, 'vm_room_era', true );
        $order = get_post_meta( $post->ID, 'vm_room_display_order', true );
        ?>
        <p>
            <label for="vm_room_color"><strong><?php esc_html_e( 'Akzentfarbe', 'vmuseum' ); ?></strong></label><br>
            <input type="color" id="vm_room_color" name="vm_room_color" value="<?php echo esc_attr( $color ?: '#8B4513' ); ?>">
        </p>
        <p>
            <label for="vm_room_era"><strong><?php esc_html_e( 'Hauptepoche', 'vmuseum' ); ?></strong></label><br>
            <input type="text" id="vm_room_era" name="vm_room_era" value="<?php echo esc_attr( $era ); ?>" class="widefat">
        </p>
        <p>
            <label for="vm_room_display_order"><strong><?php esc_html_e( 'Anzeigereihenfolge', 'vmuseum' ); ?></strong></label><br>
            <input type="number" id="vm_room_display_order" name="vm_room_display_order" value="<?php echo esc_attr( $order ?: 0 ); ?>" min="0" class="small-text">
        </p>
        <?php
    }

    public function render_room_relations( WP_Post $post ): void {
        $room_id = $post->ID;
        if ( ! $room_id ) {
            echo '<p>' . esc_html__( 'Bitte erst speichern.', 'vmuseum' ) . '</p>';
            return;
        }
        wp_nonce_field( 'vm_relations_' . $room_id, 'vm_relations_nonce' );
        $vitrines  = VM_Relations::get_vitrines( $room_id );
        $galleries = VM_Relations::get_galleries( 'room', $room_id );
        $objects   = VM_Relations::get_objects( 'room', $room_id );
        ?>
        <div class="vm-relation-editor" data-parent-type="room" data-parent-id="<?php echo esc_attr( $room_id ); ?>">
            <?php $this->render_child_section( __( 'Vitrinen in diesem Raum', 'vmuseum' ), 'vitrine', $vitrines ); ?>
            <?php $this->render_child_section( __( 'Galerien in diesem Raum', 'vmuseum' ), 'gallery', $galleries ); ?>
            <?php $this->render_child_section( __( 'Direkte Objekte in diesem Raum', 'vmuseum' ), 'object', $objects ); ?>
        </div>
        <?php
    }

    public function render_vitrine_details( WP_Post $post ): void {
        wp_nonce_field( 'vm_meta_vitrine_' . $post->ID, 'vm_meta_vitrine_nonce' );
        $layout      = get_post_meta( $post->ID, 'vm_vitrine_layout', true ) ?: 'showcase';
        $theme       = get_post_meta( $post->ID, 'vm_vitrine_theme', true ) ?: 'light';
        $description = get_post_meta( $post->ID, 'vm_vitrine_description', true );
        ?>
        <p>
            <label for="vm_vitrine_layout"><strong><?php esc_html_e( 'Layout', 'vmuseum' ); ?></strong></label><br>
            <select id="vm_vitrine_layout" name="vm_vitrine_layout" class="widefat">
                <?php foreach ( [ 'showcase', 'grid', 'shelf', 'spotlight' ] as $opt ) : ?>
                    <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $layout, $opt ); ?>><?php echo esc_html( ucfirst( $opt ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="vm_vitrine_theme"><strong><?php esc_html_e( 'Thema', 'vmuseum' ); ?></strong></label><br>
            <select id="vm_vitrine_theme" name="vm_vitrine_theme" class="widefat">
                <?php foreach ( [ 'light', 'dark', 'wood', 'glass' ] as $opt ) : ?>
                    <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $theme, $opt ); ?>><?php echo esc_html( ucfirst( $opt ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="vm_vitrine_description"><strong><?php esc_html_e( 'Kuratorischer Text', 'vmuseum' ); ?></strong></label><br>
            <textarea id="vm_vitrine_description" name="vm_vitrine_description" class="widefat" rows="4"><?php echo esc_textarea( $description ); ?></textarea>
        </p>
        <?php
    }

    public function render_vitrine_relations( WP_Post $post ): void {
        $vitrine_id = $post->ID;
        if ( ! $vitrine_id ) {
            echo '<p>' . esc_html__( 'Bitte erst speichern.', 'vmuseum' ) . '</p>';
            return;
        }
        wp_nonce_field( 'vm_relations_' . $vitrine_id, 'vm_relations_nonce' );
        $galleries = VM_Relations::get_galleries( 'vitrine', $vitrine_id );
        $objects   = VM_Relations::get_objects( 'vitrine', $vitrine_id );
        $parents   = VM_Relations::get_parents( 'vitrine', $vitrine_id, 'room' );
        ?>
        <div class="vm-relation-editor" data-parent-type="vitrine" data-parent-id="<?php echo esc_attr( $vitrine_id ); ?>">
            <?php $this->render_child_section( __( 'Galerien in dieser Vitrine', 'vmuseum' ), 'gallery', $galleries ); ?>
            <?php $this->render_child_section( __( 'Direkte Objekte in dieser Vitrine', 'vmuseum' ), 'object', $objects ); ?>
            <?php if ( $parents ) : ?>
            <div class="vm-rel-parents">
                <h4><?php esc_html_e( 'Verwendet in folgenden Räumen:', 'vmuseum' ); ?></h4>
                <ul><?php foreach ( $parents as $p ) : ?><li>🚪 <a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_gallery_details( WP_Post $post ): void {
        wp_nonce_field( 'vm_meta_gallery_' . $post->ID, 'vm_meta_gallery_nonce' );
        $mode     = get_post_meta( $post->ID, 'vm_gallery_display_mode', true ) ?: 'slider';
        $lightbox = get_post_meta( $post->ID, 'vm_gallery_lightbox', true );
        $caption  = get_post_meta( $post->ID, 'vm_gallery_caption_pos', true ) ?: 'below';
        ?>
        <p>
            <label for="vm_gallery_display_mode"><strong><?php esc_html_e( 'Anzeigemodus', 'vmuseum' ); ?></strong></label><br>
            <select id="vm_gallery_display_mode" name="vm_gallery_display_mode" class="widefat">
                <?php foreach ( [ 'slider', 'masonry', 'grid', 'filmstrip' ] as $opt ) : ?>
                    <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $mode, $opt ); ?>><?php echo esc_html( ucfirst( $opt ) ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="vm_gallery_caption_pos"><strong><?php esc_html_e( 'Bildunterschrift', 'vmuseum' ); ?></strong></label><br>
            <select id="vm_gallery_caption_pos" name="vm_gallery_caption_pos" class="widefat">
                <option value="below"   <?php selected( $caption, 'below' ); ?>><?php esc_html_e( 'Unterhalb', 'vmuseum' ); ?></option>
                <option value="overlay" <?php selected( $caption, 'overlay' ); ?>><?php esc_html_e( 'Überlagerung', 'vmuseum' ); ?></option>
                <option value="none"    <?php selected( $caption, 'none' ); ?>><?php esc_html_e( 'Keine', 'vmuseum' ); ?></option>
            </select>
        </p>
        <p><label><input type="checkbox" name="vm_gallery_lightbox" value="1" <?php checked( $lightbox, '1' ); ?>> <?php esc_html_e( 'Objekte in Lightbox öffnen', 'vmuseum' ); ?></label></p>
        <?php
    }

    public function render_gallery_relations( WP_Post $post ): void {
        $gallery_id = $post->ID;
        if ( ! $gallery_id ) {
            echo '<p>' . esc_html__( 'Bitte erst speichern.', 'vmuseum' ) . '</p>';
            return;
        }
        wp_nonce_field( 'vm_relations_' . $gallery_id, 'vm_relations_nonce' );
        $objects      = VM_Relations::get_objects( 'gallery', $gallery_id );
        $room_parents = VM_Relations::get_parents( 'gallery', $gallery_id, 'room' );
        $vit_parents  = VM_Relations::get_parents( 'gallery', $gallery_id, 'vitrine' );
        ?>
        <div class="vm-relation-editor" data-parent-type="gallery" data-parent-id="<?php echo esc_attr( $gallery_id ); ?>">
            <?php $this->render_child_section( __( 'Objekte (geordnet)', 'vmuseum' ), 'object', $objects, true ); ?>
            <?php if ( $room_parents || $vit_parents ) : ?>
            <div class="vm-rel-parents">
                <h4><?php esc_html_e( 'Verwendet in:', 'vmuseum' ); ?></h4>
                <ul>
                    <?php foreach ( $room_parents as $p ) : ?><li>🚪 <a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a> (<?php esc_html_e( 'Raum', 'vmuseum' ); ?>)</li><?php endforeach; ?>
                    <?php foreach ( $vit_parents as $p ) : ?><li>🗄️ <a href="<?php echo esc_url( get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a> (<?php esc_html_e( 'Vitrine', 'vmuseum' ); ?>)</li><?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_object_details( WP_Post $post ): void {
        wp_nonce_field( 'vm_meta_object_' . $post->ID, 'vm_meta_object_nonce' );
        $media_type = get_post_meta( $post->ID, 'vm_media_type', true ) ?: 'image';
        $year       = get_post_meta( $post->ID, 'vm_year', true );
        $year_end   = get_post_meta( $post->ID, 'vm_year_end', true );
        $copyright  = get_post_meta( $post->ID, 'vm_copyright', true );
        $media_url  = get_post_meta( $post->ID, 'vm_media_url', true );
        $types = [
            'image'    => __( 'Bild', 'vmuseum' ),
            'audio'    => __( 'Audio', 'vmuseum' ),
            'video'    => __( 'Video', 'vmuseum' ),
            '360'      => __( '360° Panorama', 'vmuseum' ),
            'document' => __( 'Dokument', 'vmuseum' ),
            'nopics'   => __( 'Kein Bild / Text', 'vmuseum' ),
        ];
        ?>
        <p>
            <label for="vm_media_type"><strong><?php esc_html_e( 'Medientyp', 'vmuseum' ); ?></strong></label><br>
            <select id="vm_media_type" name="vm_media_type" class="widefat">
                <?php foreach ( $types as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $media_type, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="vm_media_url"><strong><?php esc_html_e( 'Medien-URL (Audio/Video/360°)', 'vmuseum' ); ?></strong></label><br>
            <input type="url" id="vm_media_url" name="vm_media_url" value="<?php echo esc_attr( $media_url ); ?>" class="widefat">
        </p>
        <p>
            <label for="vm_year"><strong><?php esc_html_e( 'Jahr', 'vmuseum' ); ?></strong></label><br>
            <input type="number" id="vm_year" name="vm_year" value="<?php echo esc_attr( $year ); ?>" class="small-text" min="1" max="2100">
            &ndash;
            <input type="number" id="vm_year_end" name="vm_year_end" value="<?php echo esc_attr( $year_end ); ?>" class="small-text" min="1" max="2100">
        </p>
        <p>
            <label for="vm_copyright"><strong><?php esc_html_e( 'Copyright / Quelle', 'vmuseum' ); ?></strong></label><br>
            <input type="text" id="vm_copyright" name="vm_copyright" value="<?php echo esc_attr( $copyright ); ?>" class="widefat">
        </p>
        <?php
    }

    public function render_object_relations( WP_Post $post ): void {
        $object_id = $post->ID;
        if ( ! $object_id ) {
            echo '<p>' . esc_html__( 'Bitte erst speichern.', 'vmuseum' ) . '</p>';
            return;
        }
        $room_parents    = VM_Relations::get_parents( 'object', $object_id, 'room' );
        $gallery_parents = VM_Relations::get_parents( 'object', $object_id, 'gallery' );
        $vitrine_parents = VM_Relations::get_parents( 'object', $object_id, 'vitrine' );
        $usage           = VM_Relations::get_usage_count( 'object', $object_id );
        ?>
        <div class="vm-relation-editor" data-child-type="object" data-child-id="<?php echo esc_attr( $object_id ); ?>">
            <?php $this->render_parent_section( '🚪 ' . __( 'Räume (direkt)', 'vmuseum' ), 'room', $object_id, $room_parents ); ?>
            <?php $this->render_parent_section( '🖼️ ' . __( 'Galerien', 'vmuseum' ), 'gallery', $object_id, $gallery_parents ); ?>
            <?php $this->render_parent_section( '🗄️ ' . __( 'Vitrinen', 'vmuseum' ), 'vitrine', $object_id, $vitrine_parents ); ?>
            <div class="vm-rel-stats">
                📊 <?php printf(
                    esc_html__( 'Gesamt: In %d Räumen · %d Galerien · %d Vitrinen eingebunden', 'vmuseum' ),
                    $usage['rooms'], $usage['galleries'], $usage['vitrines']
                ); ?>
            </div>
        </div>
        <?php
    }

    private function render_child_section( string $title, string $child_type, array $items, bool $show_thumb = false ): void {
        $add_labels = [
            'vitrine' => __( 'Vitrine hinzufügen', 'vmuseum' ),
            'gallery' => __( 'Galerie hinzufügen', 'vmuseum' ),
            'object'  => __( 'Objekt hinzufügen', 'vmuseum' ),
        ];
        ?>
        <div class="vm-rel-section">
            <h4><?php echo esc_html( $title ); ?>
                <button type="button" class="button button-small vm-add-relation" data-child-type="<?php echo esc_attr( $child_type ); ?>">
                    + <?php echo esc_html( $add_labels[ $child_type ] ?? __( 'Hinzufügen', 'vmuseum' ) ); ?>
                </button>
            </h4>
            <ul class="vm-sortable-list" data-child-type="<?php echo esc_attr( $child_type ); ?>">
                <?php foreach ( $items as $item ) : ?>
                    <?php $this->render_relation_item( $item, $child_type, $show_thumb ); ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    private function render_parent_section( string $title, string $parent_type, int $object_id, array $parents ): void {
        $link_labels = [
            'room'    => __( 'In Raum verlinken', 'vmuseum' ),
            'gallery' => __( 'In Galerie verlinken', 'vmuseum' ),
            'vitrine' => __( 'In Vitrine verlinken', 'vmuseum' ),
        ];
        ?>
        <div class="vm-rel-section">
            <h4><?php echo esc_html( $title ); ?>
                <button type="button" class="button button-small vm-link-to-parent" data-parent-type="<?php echo esc_attr( $parent_type ); ?>">
                    + <?php echo esc_html( $link_labels[ $parent_type ] ?? __( 'Verlinken', 'vmuseum' ) ); ?>
                </button>
            </h4>
            <ul>
                <?php foreach ( $parents as $parent ) : ?>
                <li>
                    <a href="<?php echo esc_url( get_edit_post_link( $parent->ID ) ); ?>"><?php echo esc_html( get_the_title( $parent ) ); ?></a>
                    <button type="button" class="button button-small vm-remove-relation"
                        data-parent-type="<?php echo esc_attr( $parent_type ); ?>"
                        data-parent-id="<?php echo esc_attr( $parent->ID ); ?>"
                        data-child-type="object"
                        data-child-id="<?php echo esc_attr( $object_id ); ?>">
                        <?php esc_html_e( 'Entfernen', 'vmuseum' ); ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    private function render_relation_item( WP_Post $post, string $type, bool $show_thumb = false ): void {
        $icons = [ 'room' => '🚪', 'gallery' => '🖼️', 'vitrine' => '🗄️', 'object' => '🎨' ];
        $icon        = $icons[ $type ] ?? '📌';
        $media_type  = ( $type === 'object' ) ? get_post_meta( $post->ID, 'vm_media_type', true ) : '';
        $media_label = $media_type ? ' [' . esc_html( strtoupper( $media_type ) ) . ']' : '';
        $relation_id = $post->vm_relation_id ?? 0;
        ?>
        <li class="vm-rel-item" data-relation-id="<?php echo esc_attr( $relation_id ); ?>" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
            <span class="dashicons dashicons-menu vm-drag-handle"></span>
            <?php if ( $show_thumb && has_post_thumbnail( $post->ID ) ) : ?>
                <span class="vm-rel-thumb"><?php echo get_the_post_thumbnail( $post->ID, [ 40, 40 ] ); ?></span>
            <?php endif; ?>
            <span class="vm-rel-icon"><?php echo $icon; ?></span>
            <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="vm-rel-title">
                <?php echo esc_html( get_the_title( $post ) ); ?><?php echo $media_label; ?>
            </a>
            <span class="vm-rel-actions">
                <button type="button" class="button-link vm-remove-child-relation" data-relation-id="<?php echo esc_attr( $relation_id ); ?>">🗑️</button>
            </span>
        </li>
        <?php
    }

    public function save_meta_boxes( int $post_id, WP_Post $post ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        switch ( $post->post_type ) {
            case 'museum_room':
                if ( ! isset( $_POST['vm_meta_room_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_meta_room_nonce'] ) ), 'vm_meta_room_' . $post_id ) ) return;
                update_post_meta( $post_id, 'vm_room_color',         sanitize_hex_color( $_POST['vm_room_color'] ?? '#8B4513' ) );
                update_post_meta( $post_id, 'vm_room_era',           sanitize_text_field( wp_unslash( $_POST['vm_room_era'] ?? '' ) ) );
                update_post_meta( $post_id, 'vm_room_display_order', (int) ( $_POST['vm_room_display_order'] ?? 0 ) );
                break;

            case 'museum_vitrine':
                if ( ! isset( $_POST['vm_meta_vitrine_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_meta_vitrine_nonce'] ) ), 'vm_meta_vitrine_' . $post_id ) ) return;
                $layout = sanitize_text_field( wp_unslash( $_POST['vm_vitrine_layout'] ?? 'showcase' ) );
                $theme  = sanitize_text_field( wp_unslash( $_POST['vm_vitrine_theme'] ?? 'light' ) );
                update_post_meta( $post_id, 'vm_vitrine_layout',      in_array( $layout, [ 'showcase', 'grid', 'shelf', 'spotlight' ], true ) ? $layout : 'showcase' );
                update_post_meta( $post_id, 'vm_vitrine_theme',       in_array( $theme,  [ 'light', 'dark', 'wood', 'glass' ], true ) ? $theme : 'light' );
                update_post_meta( $post_id, 'vm_vitrine_description', sanitize_textarea_field( wp_unslash( $_POST['vm_vitrine_description'] ?? '' ) ) );
                break;

            case 'museum_gallery':
                if ( ! isset( $_POST['vm_meta_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_meta_gallery_nonce'] ) ), 'vm_meta_gallery_' . $post_id ) ) return;
                $mode    = sanitize_text_field( wp_unslash( $_POST['vm_gallery_display_mode'] ?? 'slider' ) );
                $caption = sanitize_text_field( wp_unslash( $_POST['vm_gallery_caption_pos'] ?? 'below' ) );
                update_post_meta( $post_id, 'vm_gallery_display_mode', in_array( $mode,    [ 'slider', 'masonry', 'grid', 'filmstrip' ], true ) ? $mode : 'slider' );
                update_post_meta( $post_id, 'vm_gallery_caption_pos',  in_array( $caption, [ 'below', 'overlay', 'none' ], true ) ? $caption : 'below' );
                update_post_meta( $post_id, 'vm_gallery_lightbox',     isset( $_POST['vm_gallery_lightbox'] ) ? '1' : '0' );
                break;

            case 'museum_object':
                if ( ! isset( $_POST['vm_meta_object_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vm_meta_object_nonce'] ) ), 'vm_meta_object_' . $post_id ) ) return;
                $media_type = sanitize_text_field( wp_unslash( $_POST['vm_media_type'] ?? 'image' ) );
                update_post_meta( $post_id, 'vm_media_type', in_array( $media_type, [ 'image', 'audio', 'video', '360', 'document', 'nopics' ], true ) ? $media_type : 'image' );
                update_post_meta( $post_id, 'vm_media_url',  esc_url_raw( wp_unslash( $_POST['vm_media_url'] ?? '' ) ) );
                update_post_meta( $post_id, 'vm_year',       (int) ( $_POST['vm_year'] ?? 0 ) ?: '' );
                update_post_meta( $post_id, 'vm_year_end',   (int) ( $_POST['vm_year_end'] ?? 0 ) ?: '' );
                update_post_meta( $post_id, 'vm_copyright',  sanitize_text_field( wp_unslash( $_POST['vm_copyright'] ?? '' ) ) );
                break;
        }
    }
}
