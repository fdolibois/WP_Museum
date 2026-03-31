<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$vitrine_id   = get_the_ID();
$vm_atts      = get_query_var( 'vm_atts', [] );
$layout       = $vm_atts['layout'] ?? get_post_meta( $vitrine_id, 'vm_vitrine_layout', true ) ?: 'showcase';
$theme        = $vm_atts['theme']  ?? get_post_meta( $vitrine_id, 'vm_vitrine_theme', true )  ?: 'light';
$galleries    = VM_Relations::get_galleries( 'vitrine', $vitrine_id );
$objects      = VM_Relations::get_objects( 'vitrine', $vitrine_id );
$parent_rooms = VM_Relations::get_parents( 'vitrine', $vitrine_id, 'room' );
$settings     = get_option( 'vm_settings', [] );
?>
<div class="vm-vitrine-page vm-page">

    <?php include VM_PLUGIN_DIR . 'public/templates/partials/breadcrumb-context.php'; ?>

    <!-- Parent room badges -->
    <?php if ( $parent_rooms ) : ?>
    <div class="vm-context-badges" style="margin-bottom:1rem">
        <span style="font-size:.85rem;color:var(--vm-color-text-muted)"><?php esc_html_e( 'Raum:', 'vmuseum' ); ?></span>
        <?php foreach ( $parent_rooms as $room ) : ?>
            <a href="<?php echo esc_url( get_permalink( $room->ID ) ); ?>" class="vm-badge">
                🚪 <?php echo esc_html( get_the_title( $room ) ); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="vm-room__layout">

        <!-- Sidebar -->
        <aside class="vm-room__sidebar">
            <div class="vm-room-sidebar">

                <?php foreach ( $parent_rooms as $room ) : ?>
                <a href="<?php echo esc_url( get_permalink( $room->ID ) ); ?>" class="vm-room-sidebar__back">
                    ← <?php echo esc_html( get_the_title( $room ) ); ?>
                </a>
                <?php endforeach; ?>

                <header class="vm-room__header" style="padding-top:1rem">
                    <h1 class="vm-room__title" style="font-size:1.4rem"><?php the_title(); ?></h1>
                </header>

                <?php if ( get_the_content() ) : ?>
                <div style="font-size:.875rem;line-height:1.6;margin-bottom:1rem"><?php the_content(); ?></div>
                <?php endif; ?>

                <?php if ( $galleries ) : ?>
                <nav class="vm-room-sidebar__section">
                    <h3 class="vm-room-sidebar__heading">🖼️ <?php esc_html_e( 'Galerien', 'vmuseum' ); ?></h3>
                    <ul class="vm-room-sidebar__list">
                        <?php foreach ( $galleries as $g ) :
                            $g_count = count( VM_Relations::get_objects( 'gallery', $g->ID ) );
                            $g_url   = add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id, get_permalink( $g->ID ) );
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

                <?php if ( $objects ) : ?>
                <div class="vm-room-sidebar__stat">
                    🎨 <?php printf( _n( '%d Objekt', '%d Objekte', count( $objects ), 'vmuseum' ), count( $objects ) ); ?>
                </div>
                <?php endif; ?>

            </div>
        </aside>

        <!-- Main Content -->
        <main class="vm-room__main">

            <div class="vm-vitrine vm-vitrine--<?php echo esc_attr( $layout ); ?> vm-vitrine--<?php echo esc_attr( $theme ); ?>">

                <?php if ( $objects ) : ?>
                <section class="vm-room__section">
                    <h2 class="vm-section-title">
                        <button class="vm-section-toggle" aria-expanded="true">
                            🎨 <?php esc_html_e( 'Objekte', 'vmuseum' ); ?>
                            <span class="vm-section-count">(<?php echo count( $objects ); ?>)</span>
                        </button>
                    </h2>
                    <div class="vm-section-body">
                        <div class="vm-vitrine__objects">
                            <?php foreach ( $objects as $post ) :
                                setup_postdata( $post );
                                $context_url = add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id, get_permalink( $post->ID ) );
                                ?>
                                <div class="vm-vitrine__item">
                                    <a href="<?php echo esc_url( $context_url ); ?>" class="vm-vitrine__item-link">
                                        <?php if ( has_post_thumbnail( $post->ID ) ) :
                                            echo get_the_post_thumbnail( $post->ID, 'medium', [ 'loading' => 'lazy' ] );
                                        endif; ?>
                                        <div class="vm-vitrine__item-caption">
                                            <h3><?php the_title(); ?></h3>
                                            <?php $year = get_post_meta( $post->ID, 'vm_year', true );
                                            if ( $year ) echo '<span class="vm-year">' . esc_html( $year ) . '</span>'; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; wp_reset_postdata(); ?>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ( $galleries ) : ?>
                <section class="vm-room__section">
                    <h2 class="vm-section-title">
                        <button class="vm-section-toggle" aria-expanded="false">
                            🖼️ <?php esc_html_e( 'Galerien', 'vmuseum' ); ?>
                            <span class="vm-section-count">(<?php echo count( $galleries ); ?>)</span>
                        </button>
                    </h2>
                    <div class="vm-section-body" hidden>
                        <?php foreach ( $galleries as $gallery ) : ?>
                            <div class="vm-vitrine__gallery-block vm-gallery--embedded">
                                <h3 class="vm-gallery-title">
                                    <a href="<?php echo esc_url( add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id, get_permalink( $gallery->ID ) ) ); ?>">
                                        🖼️ <?php echo esc_html( get_the_title( $gallery ) ); ?>
                                    </a>
                                </h3>
                                <?php $gal_objects = VM_Relations::get_objects( 'gallery', $gallery->ID );
                                if ( $gal_objects ) : ?>
                                <div class="vm-filmstrip">
                                    <?php foreach ( array_slice( $gal_objects, 0, 6 ) as $obj ) :
                                        $thumb = get_the_post_thumbnail_url( $obj->ID, [ 80, 80 ] );
                                        if ( $thumb ) : ?>
                                            <a href="<?php echo esc_url( add_query_arg( 'vm_context', 'vitrine_' . $vitrine_id . ',gallery_' . $gallery->ID, get_permalink( $obj->ID ) ) ); ?>">
                                                <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $obj ) ); ?>" loading="lazy">
                                            </a>
                                        <?php endif;
                                    endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

            </div><!-- .vm-vitrine -->

        </main>
    </div><!-- .vm-room__layout -->

</div>

<?php get_footer(); ?>
