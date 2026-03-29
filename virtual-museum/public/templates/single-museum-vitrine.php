<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$vitrine_id  = get_the_ID();
$vm_atts     = get_query_var( 'vm_atts', [] );
$layout      = $vm_atts['layout']  ?? get_post_meta( $vitrine_id, 'vm_vitrine_layout', true ) ?: 'showcase';
$theme       = $vm_atts['theme']   ?? get_post_meta( $vitrine_id, 'vm_vitrine_theme', true )  ?: 'light';
$description = get_post_meta( $vitrine_id, 'vm_vitrine_description', true );
$galleries   = VM_Relations::get_galleries( 'vitrine', $vitrine_id );
$objects     = VM_Relations::get_objects( 'vitrine', $vitrine_id );
$parent_rooms= VM_Relations::get_parents( 'vitrine', $vitrine_id, 'room' );
$settings    = get_option( 'vm_settings', [] );
?>
<div class="vm-vitrine-page vm-page">

    <?php include VM_PLUGIN_DIR . 'public/templates/partials/breadcrumb-context.php'; ?>

    <header class="vm-vitrine-page__header">
        <h1 class="vm-vitrine-page__title"><?php the_title(); ?></h1>
        <?php if ( $description ) : ?>
            <div class="vm-vitrine-page__description"><?php echo wp_kses_post( $description ); ?></div>
        <?php endif; ?>
    </header>

    <div class="vm-vitrine vm-vitrine--<?php echo esc_attr( $layout ); ?> vm-vitrine--<?php echo esc_attr( $theme ); ?>">

        <?php if ( $objects ) : ?>
        <div class="vm-vitrine__objects">
            <?php foreach ( $objects as $post ) :
                setup_postdata( $post );
                $context_url = add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id, get_permalink( $post->ID ) );
                ?>
                <div class="vm-vitrine__item">
                    <a href="<?php echo esc_url( $context_url ); ?>" class="vm-vitrine__item-link">
                        <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
                            <?php echo get_the_post_thumbnail( $post->ID, 'medium' ); ?>
                        <?php endif; ?>
                        <div class="vm-vitrine__item-caption">
                            <h3><?php the_title(); ?></h3>
                            <?php $year = get_post_meta( $post->ID, 'vm_year', true ); if ( $year ) echo '<span class="vm-year">' . esc_html( $year ) . '</span>'; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>
        <?php endif; ?>

        <?php if ( $galleries ) : ?>
        <div class="vm-vitrine__galleries">
            <?php foreach ( $galleries as $gallery ) : ?>
                <div class="vm-vitrine__gallery-block vm-gallery--embedded">
                    <h3 class="vm-gallery-title">
                        <a href="<?php echo esc_url( add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id, get_permalink( $gallery->ID ) ) ); ?>">
                            🖼️ <?php echo esc_html( get_the_title( $gallery ) ); ?>
                        </a>
                    </h3>
                    <?php
                    $gal_objects = VM_Relations::get_objects( 'gallery', $gallery->ID );
                    if ( $gal_objects ) : ?>
                    <div class="vm-filmstrip">
                        <?php foreach ( array_slice( $gal_objects, 0, 6 ) as $obj ) :
                            $thumb = get_the_post_thumbnail_url( $obj->ID, [ 80, 80 ] );
                            if ( $thumb ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id . ',gallery_' . $gallery->ID, get_permalink( $obj->ID ) ) ); ?>">
                                    <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $obj ) ); ?>">
                                </a>
                            <?php endif;
                        endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <?php if ( ! empty( $settings['show_relation_badge'] ) && $parent_rooms ) : ?>
    <div class="vm-also-in-rooms">
        <p><strong><?php esc_html_e( 'Auch zu finden in:', 'vmuseum' ); ?></strong></p>
        <ul class="vm-room-links">
            <?php foreach ( $parent_rooms as $room ) : ?>
                <li><a href="<?php echo esc_url( get_permalink( $room->ID ) ); ?>">🚪 <?php echo esc_html( get_the_title( $room ) ); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
