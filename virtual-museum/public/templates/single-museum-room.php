<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

global $post;
$room_id = get_the_ID();
$color   = get_post_meta( $room_id, 'vm_room_color', true ) ?: '#8B4513';
$era     = get_post_meta( $room_id, 'vm_room_era', true );

$vitrines        = VM_Relations::get_vitrines( $room_id );
$galleries       = VM_Relations::get_galleries( 'room', $room_id );
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

    <!-- ====================================================
         Two-column layout: sidebar nav + main objects
    ==================================================== -->
    <div class="vm-room__layout">

        <!-- Sidebar Navigation -->
        <aside class="vm-room__sidebar" aria-label="<?php esc_attr_e( 'Raum-Navigation', 'vmuseum' ); ?>">

            <div class="vm-room-sidebar">

                <!-- Back link -->
                <?php
                $museum_page_id = (int) ( get_option( 'vm_settings', [] )['museum_page_id'] ?? 0 );
                if ( $museum_page_id ) : ?>
                <a href="<?php echo esc_url( get_permalink( $museum_page_id ) ); ?>" class="vm-room-sidebar__back">
                    ← <?php esc_html_e( 'Zur Museumsübersicht', 'vmuseum' ); ?>
                </a>
                <?php endif; ?>

                <!-- Stats -->
                <div class="vm-room-sidebar__stats">
                    <?php if ( $object_total ) : ?>
                    <span class="vm-room-sidebar__stat">🎨 <?php printf( _n( '%d Objekt', '%d Objekte', $object_total, 'vmuseum' ), $object_total ); ?></span>
                    <?php endif; ?>
                    <?php if ( count( $vitrines ) ) : ?>
                    <span class="vm-room-sidebar__stat">🗄️ <?php printf( _n( '%d Vitrine', '%d Vitrinen', count( $vitrines ), 'vmuseum' ), count( $vitrines ) ); ?></span>
                    <?php endif; ?>
                    <?php if ( count( $galleries ) ) : ?>
                    <span class="vm-room-sidebar__stat">🖼️ <?php printf( _n( '%d Galerie', '%d Galerien', count( $galleries ), 'vmuseum' ), count( $galleries ) ); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Vitrinen Navigation -->
                <?php if ( $vitrines ) : ?>
                <nav class="vm-room-sidebar__section" aria-label="<?php esc_attr_e( 'Vitrinen', 'vmuseum' ); ?>">
                    <h3 class="vm-room-sidebar__heading">🗄️ <?php esc_html_e( 'Vitrinen', 'vmuseum' ); ?></h3>
                    <ul class="vm-room-sidebar__list">
                        <?php foreach ( $vitrines as $v ) :
                            $v_count = count( VM_Relations::get_objects( 'vitrine', $v->ID ) );
                            $v_gal   = count( VM_Relations::get_galleries( 'vitrine', $v->ID ) );
                            $v_url   = add_query_arg( 'vm_context', 'room_' . $room_id, get_permalink( $v->ID ) );
                        ?>
                        <li>
                            <a href="<?php echo esc_url( $v_url ); ?>" class="vm-room-sidebar__link">
                                <span class="vm-room-sidebar__link-title"><?php echo esc_html( get_the_title( $v ) ); ?></span>
                                <span class="vm-room-sidebar__link-meta">
                                    <?php if ( $v_count ) echo esc_html( $v_count ) . ' 🎨'; ?>
                                    <?php if ( $v_gal ) echo ' · ' . esc_html( $v_gal ) . ' 🖼️'; ?>
                                </span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <!-- Galerien Navigation -->
                <?php if ( $galleries ) : ?>
                <nav class="vm-room-sidebar__section" aria-label="<?php esc_attr_e( 'Galerien', 'vmuseum' ); ?>">
                    <h3 class="vm-room-sidebar__heading">🖼️ <?php esc_html_e( 'Galerien', 'vmuseum' ); ?></h3>
                    <ul class="vm-room-sidebar__list">
                        <?php foreach ( $galleries as $g ) :
                            $g_count = count( VM_Relations::get_objects( 'gallery', $g->ID ) );
                            $g_url   = add_query_arg( 'vm_context', 'room_' . $room_id, get_permalink( $g->ID ) );
                        ?>
                        <li>
                            <a href="<?php echo esc_url( $g_url ); ?>" class="vm-room-sidebar__link">
                                <span class="vm-room-sidebar__link-title"><?php echo esc_html( get_the_title( $g ) ); ?></span>
                                <?php if ( $g_count ) : ?>
                                <span class="vm-room-sidebar__link-meta"><?php echo esc_html( $g_count ); ?> 🎨</span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            </div><!-- .vm-room-sidebar -->
        </aside>

        <!-- Main Content: Objects -->
        <main class="vm-room__main">

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
                        🎨 <?php esc_html_e( 'Objekte', 'vmuseum' ); ?>
                        <span class="vm-section-count">(<?php echo esc_html( $object_total ); ?>)</span>
                    </button>
                </h2>
                <div class="vm-section-body">
                    <div class="vm-grid vm-grid--objects vm-masonry">
                        <?php
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

            <!-- Vitrine Preview Cards (below objects) -->
            <?php if ( $vitrines ) : ?>
            <section class="vm-room__section vm-room__section--vitrines">
                <h2 class="vm-section-title">
                    <button class="vm-section-toggle" aria-expanded="false">
                        🗄️ <?php esc_html_e( 'Vitrinen', 'vmuseum' ); ?>
                        <span class="vm-section-count">(<?php echo count( $vitrines ); ?>)</span>
                    </button>
                </h2>
                <div class="vm-section-body" hidden>
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

            <!-- Gallery Preview Cards (below vitrines) -->
            <?php if ( $galleries ) : ?>
            <section class="vm-room__section vm-room__section--galleries">
                <h2 class="vm-section-title">
                    <button class="vm-section-toggle" aria-expanded="false">
                        🖼️ <?php esc_html_e( 'Galerien', 'vmuseum' ); ?>
                        <span class="vm-section-count">(<?php echo count( $galleries ); ?>)</span>
                    </button>
                </h2>
                <div class="vm-section-body" hidden>
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

            <?php if ( ! $object_total && ! $vitrines && ! $galleries ) : ?>
            <p class="vm-empty"><?php esc_html_e( 'Dieser Raum enthält noch keine Inhalte.', 'vmuseum' ); ?></p>
            <?php endif; ?>

        </main><!-- .vm-room__main -->

    </div><!-- .vm-room__layout -->
</div>

<?php get_footer(); ?>
