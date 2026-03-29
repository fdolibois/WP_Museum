<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$gallery_id  = get_the_ID();
$vm_atts     = get_query_var( 'vm_atts', [] );
$mode        = $vm_atts['mode'] ?? get_post_meta( $gallery_id, 'vm_gallery_display_mode', true ) ?: 'slider';
$caption_pos = get_post_meta( $gallery_id, 'vm_gallery_caption_pos', true ) ?: 'below';
$lightbox    = get_post_meta( $gallery_id, 'vm_gallery_lightbox', true );
$objects     = VM_Relations::get_objects( 'gallery', $gallery_id );
$room_parents = VM_Relations::get_parents( 'gallery', $gallery_id, 'room' );
$vit_parents  = VM_Relations::get_parents( 'gallery', $gallery_id, 'vitrine' );
$settings     = get_option( 'vm_settings', [] );
$context_param = sanitize_text_field( wp_unslash( $_GET['vm_context'] ?? '' ) );
?>
<div class="vm-gallery-page vm-page">

    <?php include VM_PLUGIN_DIR . 'public/templates/partials/breadcrumb-context.php'; ?>

    <?php if ( ! empty( $settings['show_relation_badge'] ) && ( $room_parents || $vit_parents ) ) : ?>
    <div class="vm-relation-badge vm-context-badges">
        <?php foreach ( $room_parents as $room ) : ?>
            <a href="<?php echo esc_url( get_permalink( $room->ID ) ); ?>" class="vm-badge">🚪 <?php echo esc_html( get_the_title( $room ) ); ?></a>
        <?php endforeach; ?>
        <?php foreach ( $vit_parents as $vit ) : ?>
            <a href="<?php echo esc_url( get_permalink( $vit->ID ) ); ?>" class="vm-badge">🗄️ <?php echo esc_html( get_the_title( $vit ) ); ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <header class="vm-gallery-page__header">
        <h1><?php the_title(); ?></h1>
        <?php if ( get_the_content() ) the_content(); ?>
    </header>

    <?php if ( $objects ) : ?>
    <div class="vm-gallery vm-gallery--<?php echo esc_attr( $mode ); ?> vm-gallery--caption-<?php echo esc_attr( $caption_pos ); ?>"
         data-mode="<?php echo esc_attr( $mode ); ?>"
         data-lightbox="<?php echo $lightbox ? 'true' : 'false'; ?>">

        <div class="vm-gallery__main-view">
            <?php $first = $objects[0]; ?>
            <div class="vm-gallery__active-item" id="vm-gallery-active">
                <?php if ( has_post_thumbnail( $first->ID ) ) : ?>
                    <img src="<?php echo esc_url( get_the_post_thumbnail_url( $first->ID, 'large' ) ); ?>" alt="<?php echo esc_attr( get_the_title( $first ) ); ?>">
                <?php endif; ?>
                <div class="vm-gallery__active-caption">
                    <h2><?php echo esc_html( get_the_title( $first ) ); ?></h2>
                    <?php $year = get_post_meta( $first->ID, 'vm_year', true ); if ( $year ) echo '<span class="vm-year">' . esc_html( $year ) . '</span>'; ?>
                </div>
            </div>
            <div class="vm-gallery__nav">
                <button class="vm-gallery__prev" aria-label="<?php esc_attr_e( 'Vorheriges', 'vmuseum' ); ?>">&#8592;</button>
                <button class="vm-gallery__next" aria-label="<?php esc_attr_e( 'Nächstes', 'vmuseum' ); ?>">&#8594;</button>
            </div>
        </div>

        <div class="vm-gallery__filmstrip">
            <?php foreach ( $objects as $index => $obj ) :
                $thumb = get_the_post_thumbnail_url( $obj->ID, [ 100, 75 ] );
                $ctx   = add_query_arg( 'vm_context', ( $context_param ?: 'gallery_' . $gallery_id ), get_permalink( $obj->ID ) );
                ?>
                <div class="vm-gallery__thumb<?php echo $index === 0 ? ' vm-gallery__thumb--active' : ''; ?>"
                     data-index="<?php echo esc_attr( $index ); ?>"
                     data-src="<?php echo esc_url( get_the_post_thumbnail_url( $obj->ID, 'large' ) ?: '' ); ?>"
                     data-title="<?php echo esc_attr( get_the_title( $obj ) ); ?>"
                     data-url="<?php echo esc_url( $ctx ); ?>">
                    <?php if ( $thumb ) : ?><img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $obj ) ); ?>"><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
