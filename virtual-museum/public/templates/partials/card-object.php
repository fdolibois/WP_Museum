<?php if ( ! defined( 'ABSPATH' ) ) exit;
$obj_id     = $post->ID ?? get_the_ID();
$media_type = get_post_meta( $obj_id, 'vm_media_type', true ) ?: 'image';
$year       = get_post_meta( $obj_id, 'vm_year', true );
$context    = isset( $room_id ) ? 'room_' . $room_id : '';
$url        = $context ? add_query_arg( 'vm_context', $context, get_permalink( $obj_id ) ) : get_permalink( $obj_id );
$icons      = [ 'image' => '🖼️', 'audio' => '🔊', 'video' => '🎬', '360' => '🌐', 'document' => '📄', 'nopics' => '📝' ];
$icon       = $icons[ $media_type ] ?? '🎨';
?>
<article class="vm-card vm-card--object vm-card--<?php echo esc_attr( $media_type ); ?>">
    <a href="<?php echo esc_url( $url ); ?>" class="vm-card__link">
        <div class="vm-card__media-badge"><?php echo $icon; ?></div>
        <?php if ( has_post_thumbnail( $obj_id ) ) : ?>
            <div class="vm-card__thumb"><?php echo get_the_post_thumbnail( $obj_id, 'medium', [ 'loading' => 'lazy' ] ); ?></div>
        <?php else : ?>
            <div class="vm-card__thumb vm-card__thumb--placeholder">
                <span class="vm-media-icon"><?php echo $icon; ?></span>
            </div>
        <?php endif; ?>
        <div class="vm-card__body">
            <h3 class="vm-card__title"><?php echo esc_html( get_the_title( $obj_id ) ); ?></h3>
            <?php if ( $year ) : ?><span class="vm-card__year"><?php echo esc_html( $year ); ?></span><?php endif; ?>
        </div>
    </a>
</article>
