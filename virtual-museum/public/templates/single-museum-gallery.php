<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$gallery_id    = get_the_ID();
$settings      = get_option( 'vm_settings', [] );
$per_page      = (int) ( $settings['archive_per_page'] ?? 12 );
$room_parents  = VM_Relations::get_parents( 'gallery', $gallery_id, 'room' );
$vit_parents   = VM_Relations::get_parents( 'gallery', $gallery_id, 'vitrine' );
$context_param = sanitize_text_field( wp_unslash( $_GET['vm_context'] ?? '' ) );

// Total object count for this gallery
$total_objects = count( VM_Relations::get_objects( 'gallery', $gallery_id ) );
$total_pages   = (int) ceil( $total_objects / $per_page );

// First batch — server-rendered
$first_batch = VM_Relations::get_children( 'gallery', $gallery_id, 'object', true );
$first_batch = array_slice( $first_batch, 0, $per_page );
?>
<div class="vm-gallery-page vm-page">

    <?php include VM_PLUGIN_DIR . 'public/templates/partials/breadcrumb-context.php'; ?>

    <!-- Parent badges -->
    <?php if ( $room_parents || $vit_parents ) : ?>
    <div class="vm-context-badges" style="margin-bottom:1rem">
        <?php foreach ( $room_parents as $room ) : ?>
            <a href="<?php echo esc_url( get_permalink( $room->ID ) ); ?>" class="vm-badge">🚪 <?php echo esc_html( get_the_title( $room ) ); ?></a>
        <?php endforeach; ?>
        <?php foreach ( $vit_parents as $vit ) : ?>
            <a href="<?php echo esc_url( get_permalink( $vit->ID ) ); ?>" class="vm-badge">🗄️ <?php echo esc_html( get_the_title( $vit ) ); ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="vm-room__layout">

        <!-- ================================================
             SIDEBAR
        ================================================ -->
        <aside class="vm-room__sidebar">
            <div class="vm-room-sidebar">

                <!-- Back links -->
                <?php foreach ( $room_parents as $room ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'vm_context', $context_param ?: 'gallery_' . $gallery_id, get_permalink( $room->ID ) ) ); ?>"
                   class="vm-room-sidebar__back">
                    ← <?php echo esc_html( get_the_title( $room ) ); ?>
                </a>
                <?php endforeach; ?>
                <?php foreach ( $vit_parents as $vit ) : ?>
                <a href="<?php echo esc_url( get_permalink( $vit->ID ) ); ?>"
                   class="vm-room-sidebar__back" style="margin-top:.25rem">
                    ← <?php echo esc_html( get_the_title( $vit ) ); ?>
                </a>
                <?php endforeach; ?>

                <!-- Gallery title + description -->
                <div class="vm-room-sidebar__section">
                    <h2 style="font-family:var(--vm-font-serif);font-size:1.2rem;margin:0 0 .5rem"><?php the_title(); ?></h2>
                    <?php if ( get_the_content() ) : ?>
                        <div style="font-size:.875rem;color:var(--vm-color-text-muted)"><?php the_content(); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Object count -->
                <?php if ( $total_objects ) : ?>
                <div class="vm-room-sidebar__stats">
                    <span class="vm-room-sidebar__stat">
                        🎨 <?php printf( _n( '%d Objekt', '%d Objekte', $total_objects, 'vmuseum' ), $total_objects ); ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Other galleries in same rooms -->
                <?php if ( $room_parents ) :
                    $sibling_galleries = [];
                    foreach ( $room_parents as $room ) {
                        foreach ( VM_Relations::get_galleries( 'room', $room->ID ) as $g ) {
                            if ( $g->ID !== $gallery_id ) $sibling_galleries[ $g->ID ] = $g;
                        }
                    }
                    if ( $sibling_galleries ) : ?>
                <div class="vm-room-sidebar__section">
                    <h3 class="vm-room-sidebar__heading"><?php esc_html_e( 'Weitere Galerien', 'vmuseum' ); ?></h3>
                    <ul class="vm-room-sidebar__list">
                        <?php foreach ( $sibling_galleries as $sg ) : ?>
                        <li>
                            <a href="<?php echo esc_url( get_permalink( $sg->ID ) ); ?>" class="vm-room-sidebar__link">
                                <span class="vm-room-sidebar__link-title"><?php echo esc_html( get_the_title( $sg ) ); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; endif; ?>

            </div>
        </aside>

        <!-- ================================================
             MAIN — Endless Scroll Object Grid
        ================================================ -->
        <main class="vm-room__main">

            <header class="vm-gallery-page__header">
                <h1><?php the_title(); ?></h1>
            </header>

            <?php if ( $first_batch ) : ?>
            <div class="vm-gallery-scroll"
                 data-vm-lazy-section
                 data-parent-type="gallery"
                 data-parent-id="<?php echo esc_attr( $gallery_id ); ?>"
                 data-child-type="object"
                 data-per-page="<?php echo esc_attr( $per_page ); ?>">

                <div class="vm-section-body" style="padding:0">
                    <div class="vm-grid vm-grid--objects"
                         data-vm-lazy-grid
                         data-current-page="1"
                         data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
                         data-per-page="<?php echo esc_attr( $per_page ); ?>"
                         data-parent-type="gallery"
                         data-parent-id="<?php echo esc_attr( $gallery_id ); ?>"
                         data-child-type="object">

                        <?php
                        global $post;
                        foreach ( $first_batch as $post ) :
                            setup_postdata( $post );
                            include VM_PLUGIN_DIR . 'public/templates/partials/card-object.php';
                        endforeach;
                        wp_reset_postdata();
                        ?>
                    </div>

                    <?php if ( $total_pages > 1 ) : ?>
                    <div class="vm-load-more-bar">
                        <div id="vm-load-more-sentinel"></div>
                        <button id="vm-load-more-btn" class="vm-btn--load-more">
                            <?php esc_html_e( 'Mehr laden', 'vmuseum' ); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else : ?>
            <p class="vm-empty"><?php esc_html_e( 'Diese Galerie enthält noch keine Objekte.', 'vmuseum' ); ?></p>
            <?php endif; ?>

        </main>

    </div><!-- .vm-room__layout -->

</div>

<?php get_footer(); ?>
