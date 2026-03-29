<?php if ( ! defined( 'ABSPATH' ) ) exit;
$rooms = get_posts( [ 'post_type' => 'museum_room', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Beziehungskarte', 'vmuseum' ); ?></h1>
    <div class="vm-relation-map-controls">
        <label for="vm-map-room-filter"><?php esc_html_e( 'Raum:', 'vmuseum' ); ?></label>
        <select id="vm-map-room-filter">
            <option value="all"><?php esc_html_e( 'Alle Räume', 'vmuseum' ); ?></option>
            <?php foreach ( $rooms as $room ) : ?>
                <option value="<?php echo esc_attr( $room->ID ); ?>"><?php echo esc_html( get_the_title( $room ) ); ?></option>
            <?php endforeach; ?>
        </select>
        &nbsp;
        <label for="vm-map-depth"><?php esc_html_e( 'Tiefe:', 'vmuseum' ); ?></label>
        <select id="vm-map-depth">
            <option value="1"><?php esc_html_e( '1 Ebene', 'vmuseum' ); ?></option>
            <option value="2" selected><?php esc_html_e( '2 Ebenen', 'vmuseum' ); ?></option>
            <option value="3"><?php esc_html_e( '3 Ebenen', 'vmuseum' ); ?></option>
        </select>
        &nbsp;
        <button type="button" class="button button-primary" id="vm-map-refresh"><?php esc_html_e( 'Aktualisieren', 'vmuseum' ); ?></button>
    </div>

    <div id="vm-relation-tree" class="vm-relation-tree">
        <?php
        foreach ( $rooms as $room ) {
            vm_render_room_tree( $room, 2 );
        }

        function vm_render_room_tree( WP_Post $room, int $max_depth, int $depth = 1 ): void {
            $room_id  = $room->ID;
            $contents = VM_Relations::get_room_contents( $room_id );
            echo '<details class="vm-tree-room" open>';
            echo '<summary><span class="vm-tree-icon">🚪</span> <a href="' . esc_url( get_edit_post_link( $room_id ) ) . '">' . esc_html( get_the_title( $room ) ) . '</a> <span class="vm-tree-count">(' . count( $contents ) . ')</span></summary>';
            echo '<ul class="vm-tree-list">';
            foreach ( $contents as $item ) {
                $post = $item['post'];
                $type = $item['type'];
                vm_render_tree_item( $post, $type, $room_id, $max_depth, $depth );
            }
            echo '</ul></details>';
        }

        function vm_render_tree_item( WP_Post $post, string $type, int $room_id, int $max_depth, int $depth ): void {
            $icons  = [ 'vitrine' => '🗄️', 'gallery' => '🖼️', 'object' => '🎨' ];
            $icon   = $icons[ $type ] ?? '📌';
            $has_children = in_array( $type, [ 'vitrine', 'gallery' ], true ) && $depth < $max_depth;

            if ( $has_children ) {
                echo '<li class="vm-tree-' . esc_attr( $type ) . '"><details>';
                echo '<summary>' . $icon . ' <a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></summary>';
                echo '<ul>';
                $children = VM_Relations::get_children( $type, $post->ID );
                foreach ( $children as $child ) {
                    vm_render_tree_item( $child, $child->vm_child_type, $room_id, $max_depth, $depth + 1 );
                }
                echo '</ul></details></li>';
            } else {
                $all_rooms = VM_Relations::get_all_rooms_for( $type, $post->ID );
                $also_in   = array_filter( $all_rooms, fn( $r ) => $r->ID !== $room_id );
                echo '<li class="vm-tree-' . esc_attr( $type ) . '">' . $icon . ' <a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>';
                if ( $also_in ) {
                    $names = implode( ', ', array_map( fn( $r ) => get_the_title( $r ), array_slice( array_values( $also_in ), 0, 3 ) ) );
                    echo ' <span class="vm-also-in">↗ ' . esc_html__( 'auch in:', 'vmuseum' ) . ' ' . esc_html( $names ) . '</span>';
                }
                echo '</li>';
            }
        }
        ?>
    </div>
</div>
