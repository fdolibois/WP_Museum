<?php if ( ! defined( 'ABSPATH' ) ) exit;
$gal_id  = $post->ID ?? get_the_ID();
$context = isset( $room_id ) ? 'room_' . $room_id : '';
$url     = $context ? add_query_arg( 'vm_context', $context, get_permalink( $gal_id ) ) : get_permalink( $gal_id );
$objects = VM_Relations::get_objects( 'gallery', $gal_id );
?>
<article class="vm-card vm-card--gallery">
    <a href="<?php echo esc_url( $url ); ?>" class="vm-card__link">
        <?php if ( has_post_thumbnail( $gal_id ) ) : ?>
            <div class="vm-card__thumb"><?php echo get_the_post_thumbnail( $gal_id, 'medium' ); ?></div>
        <?php elseif ( $objects ) : ?>
            <div class="vm-card__thumb">
                <?php $first_thumb = get_the_post_thumbnail_url( $objects[0]->ID, 'medium' );
                if ( $first_thumb ) echo '<img src="' . esc_url( $first_thumb ) . '" alt="">'; ?>
            </div>
        <?php endif; ?>
        <div class="vm-card__body">
            <h3 class="vm-card__title">🖼️ <?php echo esc_html( get_the_title( $gal_id ) ); ?></h3>
            <?php if ( $objects ) : ?>
            <div class="vm-card__filmstrip-preview">
                <?php foreach ( array_slice( $objects, 0, 4 ) as $obj ) :
                    $t = get_the_post_thumbnail_url( $obj->ID, [ 50, 50 ] );
                    if ( $t ) echo '<img src="' . esc_url( $t ) . '" alt="">';
                endforeach; ?>
                <span class="vm-count"><?php echo esc_html( count( $objects ) ); ?> <?php esc_html_e( 'Bilder', 'vmuseum' ); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </a>
</article>
