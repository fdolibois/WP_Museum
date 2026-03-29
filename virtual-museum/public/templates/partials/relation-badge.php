<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$object_id = get_query_var( 'vm_context_object_id' ) ?: get_the_ID();
if ( ! $object_id ) return;

$post_type = get_post_type( $object_id );
if ( ! $post_type ) return;

$rel_type = VM_Relations::cpt_to_type( $post_type );

$rooms    = VM_Relations::get_all_rooms_for( $rel_type, $object_id );
$galleries= VM_Relations::get_parents( $rel_type, $object_id, 'gallery' );
$vitrines = VM_Relations::get_parents( $rel_type, $object_id, 'vitrine' );

$total = count( $rooms ) + count( $galleries ) + count( $vitrines );

if ( $total <= 1 && count( $rooms ) <= 1 ) return; // Nothing interesting to show
?>
<div class="vm-relation-badge">
    <span class="vm-relation-badge__icon">📍</span>
    <span class="vm-relation-badge__text"><?php esc_html_e( 'Auch zu finden in:', 'vmuseum' ); ?></span>
    <ul class="vm-relation-badge__list">
        <?php foreach ( $rooms as $room ) :
            $url = add_query_arg( 'vm_context', 'room_' . $room->ID, get_permalink( $room->ID ) );
            ?>
            <li><a href="<?php echo esc_url( $url ); ?>">🚪 <?php echo esc_html( get_the_title( $room ) ); ?></a></li>
        <?php endforeach; ?>
        <?php foreach ( $galleries as $gallery ) :
            $url = add_query_arg( 'vm_context', 'gallery_' . $gallery->ID, get_permalink( $gallery->ID ) );
            ?>
            <li><a href="<?php echo esc_url( $url ); ?>">🖼️ <?php echo esc_html( get_the_title( $gallery ) ); ?></a></li>
        <?php endforeach; ?>
        <?php foreach ( $vitrines as $vitrine ) :
            $url = add_query_arg( 'vm_context', 'vitrine_' . $vitrine->ID, get_permalink( $vitrine->ID ) );
            ?>
            <li><a href="<?php echo esc_url( $url ); ?>">🗄️ <?php echo esc_html( get_the_title( $vitrine ) ); ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>
