<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Bulk_Import {

    private array $post_map = [];
    private array $errors   = [];
    private array $created  = [];

    public function import_csv( string $file_path, array $options = [] ): array {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return [ 'status' => 'error', 'message' => __( 'Datei nicht lesbar.', 'vmuseum' ), 'details' => [] ];
        }

        $rows = $this->parse_csv( $file_path );
        if ( empty( $rows ) ) {
            return [ 'status' => 'error', 'message' => __( 'Keine Daten gefunden.', 'vmuseum' ), 'details' => [] ];
        }

        $this->create_all_posts( $rows );
        $this->create_all_relations( $rows );
        VM_Search_Index::rebuild_all();

        return $this->generate_report();
    }

    private function parse_csv( string $file ): array {
        $rows    = [];
        $handle  = fopen( $file, 'r' );
        if ( ! $handle ) return [];

        $headers = null;
        while ( ( $line = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) {
            if ( $headers === null ) {
                $headers = array_map( 'trim', $line );
                continue;
            }
            if ( count( $line ) === count( $headers ) ) {
                $rows[] = array_combine( $headers, array_map( 'trim', $line ) );
            }
        }

        fclose( $handle );
        return $rows;
    }

    private function create_all_posts( array $rows ): void {
        $type_order = [ 'room', 'vitrine', 'gallery', 'object' ];

        usort( $rows, function( $a, $b ) use ( $type_order ) {
            $ia = array_search( strtolower( $a['type'] ?? '' ), $type_order );
            $ib = array_search( strtolower( $b['type'] ?? '' ), $type_order );
            return ( $ia === false ? 99 : $ia ) - ( $ib === false ? 99 : $ib );
        } );

        foreach ( $rows as $row ) {
            $type = strtolower( trim( $row['type'] ?? '' ) );
            if ( ! in_array( $type, [ 'room', 'vitrine', 'gallery', 'object' ], true ) ) continue;

            $title = sanitize_text_field( $row['title'] ?? '' );
            if ( ! $title ) continue;

            $post_id = wp_insert_post( [
                'post_title'   => $title,
                'post_content' => wp_kses_post( $row['description'] ?? '' ),
                'post_status'  => 'publish',
                'post_type'    => VM_Relations::type_to_cpt( $type ),
            ] );

            if ( is_wp_error( $post_id ) ) {
                $this->errors[] = sprintf( 'Fehler bei "%s": %s', $title, $post_id->get_error_message() );
                continue;
            }

            $this->post_map[ $type ][ $title ] = $post_id;
            $this->created[] = sprintf( '%s: "%s" (#%d)', $type, $title, $post_id );

            // Type-specific meta
            match( $type ) {
                'room'    => [
                    update_post_meta( $post_id, 'vm_room_color', sanitize_hex_color( $row['color'] ?? '#8B4513' ) ),
                    update_post_meta( $post_id, 'vm_room_era',   sanitize_text_field( $row['era'] ?? '' ) ),
                ],
                'vitrine' => [
                    update_post_meta( $post_id, 'vm_vitrine_layout', sanitize_text_field( $row['layout'] ?? 'showcase' ) ),
                    update_post_meta( $post_id, 'vm_vitrine_theme',  sanitize_text_field( $row['theme'] ?? 'light' ) ),
                ],
                'gallery' => [
                    update_post_meta( $post_id, 'vm_gallery_display_mode', sanitize_text_field( $row['display_mode'] ?? 'slider' ) ),
                ],
                'object'  => [
                    update_post_meta( $post_id, 'vm_media_type', sanitize_text_field( $row['media_type'] ?? 'image' ) ),
                    update_post_meta( $post_id, 'vm_year',       (int) ( $row['year'] ?? 0 ) ?: '' ),
                    ! empty( $row['image_url'] ) ? $this->sideload_featured_image( $post_id, esc_url_raw( $row['image_url'] ) ) : null,
                ],
                default => null,
            };
        }
    }

    private function create_all_relations( array $rows ): void {
        foreach ( $rows as $row ) {
            $type    = strtolower( trim( $row['type'] ?? '' ) );
            $title   = sanitize_text_field( $row['title'] ?? '' );
            $post_id = $this->post_map[ $type ][ $title ] ?? 0;
            if ( ! $post_id ) continue;

            // in_rooms
            if ( ! empty( $row['in_rooms'] ) ) {
                foreach ( explode( '|', $row['in_rooms'] ) as $room_title ) {
                    $room_id = $this->post_map['room'][ trim( $room_title ) ] ?? 0;
                    if ( $room_id ) {
                        $r = VM_Relations::add( 'room', $room_id, $type, $post_id );
                        if ( is_wp_error( $r ) ) $this->errors[] = $r->get_error_message();
                    }
                }
            }

            // in_galleries (objects only)
            if ( $type === 'object' && ! empty( $row['in_galleries'] ) ) {
                foreach ( explode( '|', $row['in_galleries'] ) as $gallery_title ) {
                    $gallery_id = $this->post_map['gallery'][ trim( $gallery_title ) ] ?? 0;
                    if ( $gallery_id ) {
                        $r = VM_Relations::add( 'gallery', $gallery_id, 'object', $post_id );
                        if ( is_wp_error( $r ) ) $this->errors[] = $r->get_error_message();
                    }
                }
            }

            // in_vitrines (objects and galleries)
            if ( in_array( $type, [ 'object', 'gallery' ], true ) && ! empty( $row['in_vitrines'] ) ) {
                foreach ( explode( '|', $row['in_vitrines'] ) as $vitrine_title ) {
                    $vitrine_id = $this->post_map['vitrine'][ trim( $vitrine_title ) ] ?? 0;
                    if ( $vitrine_id ) {
                        $r = VM_Relations::add( 'vitrine', $vitrine_id, $type, $post_id );
                        if ( is_wp_error( $r ) ) $this->errors[] = $r->get_error_message();
                    }
                }
            }
        }
    }

    /**
     * Download an external image and set it as the post's featured image.
     * Stores the original URL as vm_import_image_url regardless of outcome.
     */
    public static function sideload_featured_image( int $post_id, string $url ): bool {
        update_post_meta( $post_id, 'vm_import_image_url', $url );

        if ( has_post_thumbnail( $post_id ) ) return true; // already set

        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachment_id = media_sideload_image( $url, $post_id, null, 'id' );

        if ( is_wp_error( $attachment_id ) ) return false;

        set_post_thumbnail( $post_id, $attachment_id );
        return true;
    }

    private function generate_report(): array {
        $message = sprintf(
            __( 'Import abgeschlossen: %d Einträge erstellt, %d Fehler.', 'vmuseum' ),
            count( $this->created ),
            count( $this->errors )
        );
        return [
            'status'  => empty( $this->errors ) ? 'success' : 'warning',
            'message' => $message,
            'details' => array_merge(
                array_map( fn( $l ) => '✅ ' . $l, $this->created ),
                array_map( fn( $e ) => '❌ ' . $e, $this->errors )
            ),
        ];
    }
}
