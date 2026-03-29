<?php if ( ! defined( 'ABSPATH' ) ) exit;
$vit_id  = $post->ID ?? get_the_ID();
$theme   = get_post_meta( $vit_id, 'vm_vitrine_theme', true ) ?: 'light';
$context = isset( $room_id ) ? 'room_' . $room_id : '';
$url     = $context ? add_query_arg( 'vm_context', $context, get_permalink( $vit_id ) ) : get_permalink( $vit_id );
$obj_count = count( VM_Relations::get_objects( 'vitrine', $vit_id ) );
$gal_count = count( VM_Relations::get_galleries( 'vitrine', $vit_id ) );
?>
<article class="vm-card vm-card--vitrine vm-vitrine--<?php echo esc_attr( $theme ); ?>">
    <a href="<?php echo esc_url( $url ); ?>" class="vm-card__link">
        <?php if ( has_post_thumbnail( $vit_id ) ) : ?>
            <div class="vm-card__thumb"><?php echo get_the_post_thumbnail( $vit_id, 'medium' ); ?></div>
        <?php endif; ?>
        <div class="vm-card__body">
            <h3 class="vm-card__title">🗄️ <?php echo esc_html( get_the_title( $vit_id ) ); ?></h3>
            <div class="vm-card__counts">
                <?php if ( $obj_count ) : ?><span class="vm-count"><?php echo esc_html( $obj_count ); ?> <?php esc_html_e( 'Obj.', 'vmuseum' ); ?></span><?php endif; ?>
                <?php if ( $gal_count ) : ?><span class="vm-count"><?php echo esc_html( $gal_count ); ?> <?php esc_html_e( 'Gal.', 'vmuseum' ); ?></span><?php endif; ?>
            </div>
        </div>
    </a>
</article>
