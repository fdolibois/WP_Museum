<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

global $post;
$room_id = get_the_ID();
$color   = get_post_meta( $room_id, 'vm_room_color', true ) ?: '#8B4513';
$era     = get_post_meta( $room_id, 'vm_room_era', true );

$vitrines      = VM_Relations::get_vitrines( $room_id );
$galleries     = VM_Relations::get_galleries( 'room', $room_id );
$object_count  = VM_Relations::get_usage_count( 'object', $room_id )['rooms'] ?? 0;
// Objects are lazy-loaded via JS; only fetch count for display in heading.
// Retrieve a small preview for server-side render (avoids blank section on slow connections).
$objects_preview = VM_Relations::get_children( 'room', $room_id, 'object', true );
$object_total    = count( $objects_preview );
$lazy_per_page   = (int) ( get_option( 'vm_settings', [] )['lazy_per_page'] ?? 12 );

$context_param = sanitize_text_field( wp_unslash( $_GET['vm_context'] ?? '' ) );
?>
<div class="vm-room vm-page" style="--vm-room-color: <?php echo esc_attr( $color ); ?>">

    <?php include VM_PLUGIN_DIR . 'public/templates/partials/breadcrumb-context.php'; ?>

    <header class="vm-room__header">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="vm-room__cover"><?php the_post_thumbnail( 'full' ); ?></div>
        <?php endif; ?>
        <div class="vm-room__hero-content">
            <h1 class="vm-room__title"><?php the_title(); ?></h1>
            <?php if ( $era ) : ?><p class="vm-room__era"><?php echo esc_html( $era ); ?></p><?php endif; ?>
        </div>
    </header>

    <?php if ( get_the_content() ) : ?>
    <div class="vm-room__description vm-content">
        <?php the_content(); ?>
    </div>
    <?php endif; ?>

    <div class="vm-room__contents">

        <?php if ( $vitrines ) : ?>
        <section class="vm-room__section vm-room__section--vitrines">
            <h2 class="vm-section-title">
                <button class="vm-section-toggle" aria-expanded="true">
                    🗄️ <?php esc_html_e( 'Vitrinen', 'vmuseum' ); ?>
                    <span class="vm-section-count">(<?php echo count( $vitrines ); ?>)</span>
                </button>
            </h2>
            <div class="vm-section-body">
                <div class="vm-grid vm-grid--vitrines">
                    <?php foreach ( $vitrines as $post ) :
                        setup_postdata( $post );
                        include VM_PLUGIN_DIR . 'public/templates/partials/card-vitrine.php';
                    endforeach;
                    wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ( $galleries ) : ?>
        <section class="vm-room__section vm-room__section--galleries">
            <h2 class="vm-section-title">
                <button class="vm-section-toggle" aria-expanded="true">
                    🖼️ <?php esc_html_e( 'Galerien', 'vmuseum' ); ?>
                    <span class="vm-section-count">(<?php echo count( $galleries ); ?>)</span>
                </button>
            </h2>
            <div class="vm-section-body">
                <div class="vm-grid vm-grid--galleries">
                    <?php foreach ( $galleries as $post ) :
                        setup_postdata( $post );
                        include VM_PLUGIN_DIR . 'public/templates/partials/card-gallery.php';
                    endforeach;
                    wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ( $object_total > 0 ) : ?>
        <section class="vm-room__section vm-room__section--objects"
                 data-vm-lazy-section
                 data-parent-type="room"
                 data-parent-id="<?php echo esc_attr( $room_id ); ?>"
                 data-child-type="object"
                 data-per-page="<?php echo esc_attr( $lazy_per_page ); ?>"
                 data-skeleton-count="<?php echo min( $object_total, 4 ); ?>">
            <h2 class="vm-section-title">
                <button class="vm-section-toggle" aria-expanded="true">
                    🎨 <?php esc_html_e( 'Einzelne Objekte', 'vmuseum' ); ?>
                    <span class="vm-section-count">(<?php echo esc_html( $object_total ); ?>)</span>
                </button>
            </h2>
            <div class="vm-section-body">
                <div class="vm-grid vm-grid--objects vm-masonry">
                    <?php
                    // Render first batch server-side for instant display
                    $first_batch = array_slice( $objects_preview, 0, $lazy_per_page );
                    foreach ( $first_batch as $post ) :
                        setup_postdata( $post );
                        include VM_PLUGIN_DIR . 'public/templates/partials/card-object.php';
                    endforeach;
                    wp_reset_postdata(); ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ( ! $vitrines && ! $galleries && ! $object_total ) : ?>
        <p class="vm-empty"><?php esc_html_e( 'Dieser Raum enthält noch keine Inhalte.', 'vmuseum' ); ?></p>
        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>
