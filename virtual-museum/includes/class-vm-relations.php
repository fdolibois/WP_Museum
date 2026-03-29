<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Relations {

    /** Allowed parent → child combinations */
    private static array $allowed = [
        'room'    => [ 'object', 'gallery', 'vitrine' ],
        'vitrine' => [ 'object', 'gallery' ],
        'gallery' => [ 'object' ],
    ];

    // =========================================================
    // WRITE
    // =========================================================

    public static function add( string $parent_type, int $parent_id, string $child_type, int $child_id, int $position = 0 ): int|WP_Error {
        $valid = self::validate_relation( $parent_type, $child_type );
        if ( is_wp_error( $valid ) ) {
            return $valid;
        }

        $circular = self::check_circular( $parent_type, $parent_id, $child_type, $child_id );
        if ( is_wp_error( $circular ) ) {
            return $circular;
        }

        if ( self::exists( $parent_type, $parent_id, $child_type, $child_id ) ) {
            return new WP_Error( 'vm_relation_exists', __( 'Diese Beziehung existiert bereits.', 'vmuseum' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';

        do_action( 'vm_relation_before_add', $parent_type, $parent_id, $child_type, $child_id );

        $user_id = get_current_user_id();
        $result  = $wpdb->insert( $table, [
            'parent_type' => $parent_type,
            'parent_id'   => $parent_id,
            'child_type'  => $child_type,
            'child_id'    => $child_id,
            'position'    => $position,
            'added_by'    => $user_id ?: null,
        ], [ '%s', '%d', '%s', '%d', '%d', '%d' ] );

        if ( false === $result ) {
            return new WP_Error( 'vm_db_error', __( 'Datenbankfehler beim Anlegen der Beziehung.', 'vmuseum' ) );
        }

        $relation_id = (int) $wpdb->insert_id;

        self::flush_cache( $parent_id );
        self::flush_cache( $child_id );

        do_action( 'vm_relation_added', $relation_id, $parent_type, $parent_id, $child_type, $child_id );

        return $relation_id;
    }

    public static function remove( string $parent_type, int $parent_id, string $child_type, int $child_id ): bool {
        global $wpdb;
        $table  = $wpdb->prefix . 'vm_relations';
        $result = $wpdb->delete( $table, [
            'parent_type' => $parent_type,
            'parent_id'   => $parent_id,
            'child_type'  => $child_type,
            'child_id'    => $child_id,
        ], [ '%s', '%d', '%s', '%d' ] );

        if ( $result ) {
            self::flush_cache( $parent_id );
            self::flush_cache( $child_id );
            do_action( 'vm_relation_removed', $parent_type, $parent_id, $child_type, $child_id );
        }

        return (bool) $result;
    }

    public static function remove_all_for_post( int $post_id ): void {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';

        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} WHERE parent_id = %d OR child_id = %d",
            $post_id, $post_id
        ) );

        self::flush_cache( $post_id );
    }

    public static function set_children( string $parent_type, int $parent_id, array $children ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';

        $wpdb->query( 'START TRANSACTION' );

        try {
            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$table} WHERE parent_type = %s AND parent_id = %d",
                $parent_type, $parent_id
            ) );

            foreach ( $children as $index => $child ) {
                $valid = self::validate_relation( $parent_type, $child['type'] );
                if ( is_wp_error( $valid ) ) {
                    throw new \Exception( $valid->get_error_message() );
                }
                $user_id = get_current_user_id();
                $wpdb->insert( $table, [
                    'parent_type' => $parent_type,
                    'parent_id'   => $parent_id,
                    'child_type'  => $child['type'],
                    'child_id'    => (int) $child['id'],
                    'position'    => $child['position'] ?? $index,
                    'added_by'    => $user_id ?: null,
                ], [ '%s', '%d', '%s', '%d', '%d', '%d' ] );
            }

            $wpdb->query( 'COMMIT' );
        } catch ( \Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            return false;
        }

        self::flush_cache( $parent_id );
        do_action( 'vm_relations_reordered', $parent_type, $parent_id );

        return true;
    }

    public static function reorder_children( string $parent_type, int $parent_id, array $ordered_ids ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';

        foreach ( $ordered_ids as $relation_id => $position ) {
            $wpdb->update(
                $table,
                [ 'position' => (int) $position ],
                [
                    'id'          => (int) $relation_id,
                    'parent_type' => $parent_type,
                    'parent_id'   => $parent_id,
                ],
                [ '%d' ],
                [ '%d', '%s', '%d' ]
            );
        }

        self::flush_cache( $parent_id );
        do_action( 'vm_relations_reordered', $parent_type, $parent_id );

        return true;
    }

    // =========================================================
    // READ — Children
    // =========================================================

    public static function get_children( string $parent_type, int $parent_id, ?string $child_type = null, bool $as_posts = true ): array {
        $cache_key = "vm_children_{$parent_type}_{$parent_id}_{$child_type}";
        $cached    = wp_cache_get( $cache_key, 'vm_relations' );

        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';

        if ( $child_type ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT child_id, child_type, id as relation_id, position
                 FROM {$table}
                 WHERE parent_type = %s AND parent_id = %d AND child_type = %s
                 ORDER BY position ASC",
                $parent_type, $parent_id, $child_type
            ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT child_id, child_type, id as relation_id, position
                 FROM {$table}
                 WHERE parent_type = %s AND parent_id = %d
                 ORDER BY position ASC",
                $parent_type, $parent_id
            ) );
        }

        if ( ! $as_posts ) {
            $ids = array_column( $rows, 'child_id' );
            wp_cache_set( $cache_key, $ids, 'vm_relations', 3600 );
            return $ids;
        }

        $posts = [];
        foreach ( $rows as $row ) {
            $post = get_post( (int) $row->child_id );
            if ( $post ) {
                $post->vm_relation_id = $row->relation_id;
                $post->vm_position    = $row->position;
                $post->vm_child_type  = $row->child_type;
                $posts[] = $post;
            }
        }

        wp_cache_set( $cache_key, $posts, 'vm_relations', 3600 );
        return $posts;
    }

    public static function get_objects( string $parent_type, int $parent_id ): array {
        return self::get_children( $parent_type, $parent_id, 'object' );
    }

    public static function get_galleries( string $parent_type, int $parent_id ): array {
        if ( $parent_type === 'gallery' ) {
            return [];
        }
        return self::get_children( $parent_type, $parent_id, 'gallery' );
    }

    public static function get_vitrines( int $room_id ): array {
        return self::get_children( 'room', $room_id, 'vitrine' );
    }

    public static function get_room_contents( int $room_id ): array {
        $cache_key = "vm_room_contents_{$room_id}";
        $cached    = wp_cache_get( $cache_key, 'vm_relations' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT child_id, child_type, id as relation_id, position
             FROM {$table}
             WHERE parent_type = 'room' AND parent_id = %d
             ORDER BY position ASC",
            $room_id
        ) );

        $contents = [];
        foreach ( $rows as $row ) {
            $post = get_post( (int) $row->child_id );
            if ( $post ) {
                $contents[] = [
                    'type'        => $row->child_type,
                    'post'        => $post,
                    'position'    => (int) $row->position,
                    'relation_id' => (int) $row->relation_id,
                ];
            }
        }

        $contents = apply_filters( 'vm_room_contents_ordered', $contents, $room_id );
        wp_cache_set( $cache_key, $contents, 'vm_relations', 3600 );

        return $contents;
    }

    // =========================================================
    // READ — Parents (reverse lookup)
    // =========================================================

    public static function get_parents( string $child_type, int $child_id, ?string $parent_type = null, bool $as_posts = true ): array {
        $cache_key = "vm_parents_{$child_type}_{$child_id}_{$parent_type}";
        $cached    = wp_cache_get( $cache_key, 'vm_relations' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';

        if ( $parent_type ) {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT parent_id, parent_type FROM {$table}
                 WHERE child_type = %s AND child_id = %d AND parent_type = %s",
                $child_type, $child_id, $parent_type
            ) );
        } else {
            $rows = $wpdb->get_results( $wpdb->prepare(
                "SELECT parent_id, parent_type FROM {$table}
                 WHERE child_type = %s AND child_id = %d",
                $child_type, $child_id
            ) );
        }

        if ( ! $as_posts ) {
            $ids = array_column( $rows, 'parent_id' );
            wp_cache_set( $cache_key, $ids, 'vm_relations', 3600 );
            return $ids;
        }

        $posts = [];
        foreach ( $rows as $row ) {
            $post = get_post( (int) $row->parent_id );
            if ( $post ) {
                $post->vm_parent_type = $row->parent_type;
                $posts[] = $post;
            }
        }

        wp_cache_set( $cache_key, $posts, 'vm_relations', 3600 );
        return $posts;
    }

    public static function get_all_rooms_for( string $child_type, int $child_id ): array {
        $rooms = [];

        // Direct room parents
        foreach ( self::get_parents( $child_type, $child_id, 'room' ) as $room ) {
            $rooms[ $room->ID ] = $room;
        }

        // Via vitrines
        if ( in_array( $child_type, [ 'object', 'gallery' ], true ) ) {
            foreach ( self::get_parents( $child_type, $child_id, 'vitrine' ) as $vitrine ) {
                foreach ( self::get_parents( 'vitrine', $vitrine->ID, 'room' ) as $room ) {
                    $rooms[ $room->ID ] = $room;
                }
            }
        }

        // Via galleries (objects only)
        if ( $child_type === 'object' ) {
            foreach ( self::get_parents( 'object', $child_id, 'gallery' ) as $gallery ) {
                foreach ( self::get_parents( 'gallery', $gallery->ID, 'room' ) as $room ) {
                    $rooms[ $room->ID ] = $room;
                }
                foreach ( self::get_parents( 'gallery', $gallery->ID, 'vitrine' ) as $vitrine ) {
                    foreach ( self::get_parents( 'vitrine', $vitrine->ID, 'room' ) as $room ) {
                        $rooms[ $room->ID ] = $room;
                    }
                }
            }
        }

        return array_values( $rooms );
    }

    // =========================================================
    // EXISTENCE CHECKS
    // =========================================================

    public static function exists( string $parent_type, int $parent_id, string $child_type, int $child_id ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE parent_type = %s AND parent_id = %d AND child_type = %s AND child_id = %d",
            $parent_type, $parent_id, $child_type, $child_id
        ) );
        return $count > 0;
    }

    public static function get_usage_count( string $child_type, int $child_id ): array {
        global $wpdb;
        $table = $wpdb->prefix . 'vm_relations';
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT parent_type, COUNT(*) as cnt
             FROM {$table}
             WHERE child_type = %s AND child_id = %d
             GROUP BY parent_type",
            $child_type, $child_id
        ) );

        $counts = [ 'rooms' => 0, 'galleries' => 0, 'vitrines' => 0, 'total' => 0 ];
        foreach ( $rows as $row ) {
            $key = match( $row->parent_type ) {
                'room'    => 'rooms',
                'gallery' => 'galleries',
                'vitrine' => 'vitrines',
                default   => null,
            };
            if ( $key ) {
                $counts[ $key ] = (int) $row->cnt;
            }
        }
        $counts['total'] = $counts['rooms'] + $counts['galleries'] + $counts['vitrines'];

        return $counts;
    }

    // =========================================================
    // VALIDATION
    // =========================================================

    public static function validate_relation( string $parent_type, string $child_type ): true|WP_Error {
        if ( ! isset( self::$allowed[ $parent_type ] ) ) {
            return new WP_Error(
                'vm_invalid_parent',
                sprintf( __( 'Ungültiger Parent-Typ: %s', 'vmuseum' ), $parent_type )
            );
        }
        if ( ! in_array( $child_type, self::$allowed[ $parent_type ], true ) ) {
            return new WP_Error(
                'vm_invalid_combination',
                sprintf(
                    __( 'Unerlaubte Kombination: %s kann kein Kind von %s sein.', 'vmuseum' ),
                    $child_type, $parent_type
                )
            );
        }
        return true;
    }

    public static function check_circular( string $parent_type, int $parent_id, string $child_type, int $child_id ): true|WP_Error {
        if ( $parent_type === $child_type && $parent_id === $child_id ) {
            return new WP_Error( 'vm_circular', __( 'Ein Element kann nicht mit sich selbst verknüpft werden.', 'vmuseum' ) );
        }
        return true;
    }

    // =========================================================
    // CACHE
    // =========================================================

    public static function flush_cache( int $post_id ): void {
        foreach ( [ 'room', 'vitrine', 'gallery' ] as $ptype ) {
            foreach ( [ 'object', 'gallery', 'vitrine', null ] as $ctype ) {
                wp_cache_delete( "vm_children_{$ptype}_{$post_id}_{$ctype}", 'vm_relations' );
            }
            wp_cache_delete( "vm_room_contents_{$post_id}", 'vm_relations' );
        }
        foreach ( [ 'object', 'gallery', 'vitrine' ] as $ctype ) {
            foreach ( [ 'room', 'vitrine', 'gallery', null ] as $ptype ) {
                wp_cache_delete( "vm_parents_{$ctype}_{$post_id}_{$ptype}", 'vm_relations' );
            }
        }
    }

    public static function cpt_to_type( string $post_type ): string {
        return match( $post_type ) {
            'museum_room'    => 'room',
            'museum_gallery' => 'gallery',
            'museum_vitrine' => 'vitrine',
            'museum_object'  => 'object',
            default          => $post_type,
        };
    }

    public static function type_to_cpt( string $type ): string {
        return match( $type ) {
            'room'    => 'museum_room',
            'gallery' => 'museum_gallery',
            'vitrine' => 'museum_vitrine',
            'object'  => 'museum_object',
            default   => $type,
        };
    }
}
