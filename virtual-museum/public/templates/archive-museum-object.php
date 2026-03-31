<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$settings    = get_option( 'vm_settings', [] );
$per_page    = (int) ( $settings['archive_per_page'] ?? 24 );
$vm_atts     = get_query_var( 'vm_atts', [] );
$show_filter = ( $vm_atts['show_filter'] ?? 'yes' ) === 'yes';

// Museum-Startseite für Rücknavigation
$museum_page_id  = (int) ( $settings['museum_page_id'] ?? 0 );
$museum_page_url = $museum_page_id ? get_permalink( $museum_page_id ) : '';

// Erste Seite server-seitig laden (neueste zuerst)
$wp_query = new WP_Query( [
    'post_type'      => 'museum_object',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => 1,
    'orderby'        => 'date',
    'order'          => 'DESC',
] );

$total_objects = (int) $wp_query->found_posts;
$total_pages   = (int) $wp_query->max_num_pages;
?>
<div class="vm-archive vm-page">

    <!-- ================================================
         HEADER mit Rücknavigation
    ================================================ -->
    <header class="vm-archive__header">
        <?php if ( $museum_page_url ) : ?>
        <a href="<?php echo esc_url( $museum_page_url ); ?>" class="vm-archive__back">
            ← <?php esc_html_e( 'Zum Museum', 'vmuseum' ); ?>
        </a>
        <?php endif; ?>

        <div class="vm-archive__header-content">
            <h1 class="vm-archive__title">
                <?php esc_html_e( 'Alle Objekte', 'vmuseum' ); ?>
                <?php if ( $total_objects ) : ?>
                <span class="vm-archive__count"><?php echo esc_html( $total_objects ); ?></span>
                <?php endif; ?>
            </h1>
            <p class="vm-archive__subtitle">
                <?php esc_html_e( 'Neueste Objekte zuerst', 'vmuseum' ); ?>
            </p>
        </div>
    </header>

    <?php if ( $show_filter ) : ?>
    <?php include VM_PLUGIN_DIR . 'public/templates/partials/filter-bar.php'; ?>
    <?php endif; ?>

    <?php if ( $wp_query->have_posts() ) : ?>

    <!-- Endless Scroll Grid -->
    <div class="vm-grid vm-grid--objects"
         data-vm-lazy-grid
         data-current-page="1"
         data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-child-type="object">

        <?php while ( $wp_query->have_posts() ) :
            $wp_query->the_post();
            include VM_PLUGIN_DIR . 'public/templates/partials/card-object.php';
        endwhile;
        wp_reset_postdata(); ?>
    </div>

    <?php if ( $total_pages > 1 ) : ?>
    <div class="vm-load-more-bar">
        <div id="vm-load-more-sentinel" aria-hidden="true"></div>
        <button id="vm-load-more-btn" class="vm-btn vm-btn--load-more" type="button">
            <?php esc_html_e( 'Mehr laden', 'vmuseum' ); ?>
        </button>
    </div>
    <?php endif; ?>

    <!-- Rücknavigation (unten, nach dem Scrollen) -->
    <?php if ( $museum_page_url ) : ?>
    <div class="vm-archive__back-footer">
        <a href="<?php echo esc_url( $museum_page_url ); ?>" class="vm-btn vm-btn--outline">
            ← <?php esc_html_e( 'Zurück zur Museumsübersicht', 'vmuseum' ); ?>
        </a>
    </div>
    <?php endif; ?>

    <?php else : ?>
    <p class="vm-empty"><?php esc_html_e( 'Keine Objekte gefunden.', 'vmuseum' ); ?></p>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
