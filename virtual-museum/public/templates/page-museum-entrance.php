<?php
/**
 * Museum Entrance Page Template
 * Loaded for the auto-created museum overview page.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$settings   = get_option( 'vm_settings', [] );
$page_title = get_the_title() ?: __( 'Virtuelles Museum', 'vmuseum' );
$page_desc  = get_the_content() ?: '';

// Stats
$room_count    = wp_count_posts( 'museum_room' )->publish    ?? 0;
$gallery_count = wp_count_posts( 'museum_gallery' )->publish ?? 0;
$vitrine_count = wp_count_posts( 'museum_vitrine' )->publish ?? 0;
$object_count  = wp_count_posts( 'museum_object' )->publish  ?? 0;

// All rooms
$rooms = get_posts( [
    'post_type'      => 'museum_room',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
] );
?>
<div class="vm-entrance vm-page">

    <!-- ====================================================
         HERO
    ==================================================== -->
    <header class="vm-entrance__hero">
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="vm-entrance__hero-bg" style="background-image: url('<?php echo esc_url( get_the_post_thumbnail_url( null, 'full' ) ); ?>');" aria-hidden="true"></div>
        <div class="vm-entrance__hero-overlay" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="vm-entrance__hero-content">
            <h1 class="vm-entrance__title"><?php echo esc_html( $page_title ); ?></h1>
            <?php if ( $page_desc ) : ?>
            <div class="vm-entrance__desc vm-content"><?php echo wp_kses_post( wpautop( $page_desc ) ); ?></div>
            <?php endif; ?>
        </div>
    </header>

    <!-- ====================================================
         STATISTICS BAR
    ==================================================== -->
    <nav class="vm-entrance__stats" aria-label="<?php esc_attr_e( 'Sammlungsübersicht', 'vmuseum' ); ?>">
        <div class="vm-entrance__stat">
            <span class="vm-entrance__stat-icon">🚪</span>
            <strong class="vm-entrance__stat-num"><?php echo esc_html( $room_count ); ?></strong>
            <span class="vm-entrance__stat-label"><?php echo esc_html( _n( 'Raum', 'Räume', $room_count, 'vmuseum' ) ); ?></span>
        </div>
        <div class="vm-entrance__stat">
            <span class="vm-entrance__stat-icon">🗄️</span>
            <strong class="vm-entrance__stat-num"><?php echo esc_html( $vitrine_count ); ?></strong>
            <span class="vm-entrance__stat-label"><?php echo esc_html( _n( 'Vitrine', 'Vitrinen', $vitrine_count, 'vmuseum' ) ); ?></span>
        </div>
        <div class="vm-entrance__stat">
            <span class="vm-entrance__stat-icon">🖼️</span>
            <strong class="vm-entrance__stat-num"><?php echo esc_html( $gallery_count ); ?></strong>
            <span class="vm-entrance__stat-label"><?php echo esc_html( _n( 'Galerie', 'Galerien', $gallery_count, 'vmuseum' ) ); ?></span>
        </div>
        <div class="vm-entrance__stat">
            <span class="vm-entrance__stat-icon">🎨</span>
            <strong class="vm-entrance__stat-num"><?php echo esc_html( $object_count ); ?></strong>
            <span class="vm-entrance__stat-label"><?php echo esc_html( _n( 'Objekt', 'Objekte', $object_count, 'vmuseum' ) ); ?></span>
        </div>
    </nav>

    <!-- ====================================================
         SEARCH
    ==================================================== -->
    <?php if ( true ) : // B025: Suche immer auf der Eingangsseite anzeigen ?>
    <section class="vm-entrance__search-wrap">
        <div class="vm-entrance__search-inner">
            <label for="vm-live-search" class="screen-reader-text"><?php esc_html_e( 'Museum durchsuchen', 'vmuseum' ); ?></label>
            <input
                type="search"
                id="vm-live-search"
                class="vm-entrance__search-input"
                placeholder="<?php esc_attr_e( 'Museum durchsuchen …', 'vmuseum' ); ?>"
                autocomplete="off"
            >
            <span class="vm-entrance__search-icon" aria-hidden="true">🔍</span>
            <div id="vm-search-results" class="vm-entrance__search-results" hidden></div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ====================================================
         ROOM GRID
    ==================================================== -->
    <?php if ( $rooms ) : ?>
    <section class="vm-entrance__rooms">
        <h2 class="vm-entrance__section-title">
            <?php esc_html_e( 'Alle Ausstellungsräume', 'vmuseum' ); ?>
        </h2>
        <div class="vm-grid vm-grid--rooms vm-entrance__room-grid">
            <?php foreach ( $rooms as $post ) :
                setup_postdata( $post );
                include VM_PLUGIN_DIR . 'public/templates/partials/card-room.php';
            endforeach;
            wp_reset_postdata(); ?>
        </div>
    </section>
    <?php else : ?>
    <p class="vm-empty"><?php esc_html_e( 'Noch keine Räume vorhanden. Bitte im Admin-Bereich Räume anlegen.', 'vmuseum' ); ?></p>
    <?php endif; ?>

    <!-- ====================================================
         RECENT OBJECTS (latest 8)
    ==================================================== -->
    <?php
    $recent_objects = get_posts( [
        'post_type'      => 'museum_object',
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );
    if ( $recent_objects ) : ?>
    <section class="vm-entrance__recent">
        <h2 class="vm-entrance__section-title">
            <?php esc_html_e( 'Neueste Objekte', 'vmuseum' ); ?>
        </h2>
        <div class="vm-grid vm-grid--objects">
            <?php foreach ( $recent_objects as $post ) :
                setup_postdata( $post );
                include VM_PLUGIN_DIR . 'public/templates/partials/card-object.php';
            endforeach;
            wp_reset_postdata(); ?>
        </div>
        <?php if ( $object_count > 8 ) :
            $archive_url = get_post_type_archive_link( 'museum_object' );
            if ( $archive_url ) : ?>
        <div class="vm-entrance__all-link">
            <a href="<?php echo esc_url( $archive_url ); ?>" class="vm-btn vm-btn--outline">
                <?php printf( esc_html__( 'Alle %d Objekte ansehen', 'vmuseum' ), $object_count ); ?> →
            </a>
        </div>
        <?php endif; endif; ?>
    </section>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
