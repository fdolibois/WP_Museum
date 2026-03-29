<?php if ( ! defined( 'ABSPATH' ) ) exit;
$room_id = $post->ID ?? get_the_ID();
$color   = get_post_meta( $room_id, 'vm_room_color', true ) ?: '#8B4513';
$vm_atts = get_query_var( 'vm_atts', [] );

// Count contents
$obj_count  = count( VM_Relations::get_objects( 'room', $room_id ) );
$gal_count  = count( VM_Relations::get_galleries( 'room', $room_id ) );
$vit_count  = count( VM_Relations::get_vitrines( $room_id ) );
?>
<article class="vm-card vm-card--room" style="--vm-room-color: <?php echo esc_attr( $color ); ?>">
    <a href="<?php echo esc_url( get_permalink( $room_id ) ); ?>" class="vm-card__link">
        <?php if ( has_post_thumbnail( $room_id ) ) : ?>
            <div class="vm-card__thumb"><?php echo get_the_post_thumbnail( $room_id, 'medium' ); ?></div>
        <?php else : ?>
            <div class="vm-card__thumb vm-card__thumb--placeholder" style="background:<?php echo esc_attr( $color ); ?>">
                <span class="vm-room-icon">🚪</span>
            </div>
        <?php endif; ?>
        <div class="vm-card__body">
            <h3 class="vm-card__title"><?php echo esc_html( get_the_title( $room_id ) ); ?></h3>
            <?php if ( get_the_excerpt( $room_id ) ) : ?>
                <p class="vm-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $room_id ), 15 ) ); ?></p>
            <?php endif; ?>
            <?php if ( ( $vm_atts['show_count'] ?? 'yes' ) === 'yes' ) : ?>
            <div class="vm-card__counts">
                <?php if ( $vit_count ) : ?><span class="vm-count">🗄️ <?php echo esc_html( $vit_count ); ?></span><?php endif; ?>
                <?php if ( $gal_count ) : ?><span class="vm-count">🖼️ <?php echo esc_html( $gal_count ); ?></span><?php endif; ?>
                <?php if ( $obj_count ) : ?><span class="vm-count">🎨 <?php echo esc_html( $obj_count ); ?></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </a>
</article>
