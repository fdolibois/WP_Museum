<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$vm_atts     = get_query_var( 'vm_atts', [] );
$type        = $vm_atts['type']     ?? 'all';
$layout      = $vm_atts['layout']   ?? 'grid';
$per_page    = (int) ( $vm_atts['per_page'] ?? get_option( 'vm_settings', [] )['archive_per_page'] ?? 24 );
$show_filter = ( $vm_atts['show_filter'] ?? 'yes' ) === 'yes';
$show_search = ( $vm_atts['show_search'] ?? 'yes' ) === 'yes';

$post_types = match( $type ) {
    'rooms'    => [ 'museum_room' ],
    'galleries'=> [ 'museum_gallery' ],
    'vitrines' => [ 'museum_vitrine' ],
    'objects'  => [ 'museum_object' ],
    default    => [ 'museum_object' ],
};

$child_type = match( $type ) {
    'rooms'    => 'room',
    'galleries'=> 'gallery',
    'vitrines' => 'vitrine',
    default    => 'object',
};

$paged    = max( 1, get_query_var( 'paged', 1 ) );
$wp_query = new WP_Query( [
    'post_type'      => $post_types,
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => 1,        // Always load page 1 server-side
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
] );

$total_pages = (int) $wp_query->max_num_pages;
?>
<div class="vm-archive vm-page vm-archive--<?php echo esc_attr( $layout ); ?>">

    <header class="vm-archive__header">
        <h1><?php
        if ( is_post_type_archive() ) {
            post_type_archive_title();
        } else {
            esc_html_e( 'Museum — Archiv', 'vmuseum' );
        }
        ?></h1>
    </header>

    <?php if ( $show_filter ) : ?>
    <?php include VM_PLUGIN_DIR . 'public/templates/partials/filter-bar.php'; ?>
    <?php endif; ?>

    <?php if ( $wp_query->have_posts() ) : ?>
    <div class="vm-grid vm-grid--<?php echo esc_attr( $layout ); ?>"
         data-vm-lazy-grid
         data-current-page="1"
         data-total-pages="<?php echo esc_attr( $total_pages ); ?>"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-child-type="<?php echo esc_attr( $child_type ); ?>">
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

    <?php else : ?>
    <p class="vm-empty"><?php esc_html_e( 'Keine Objekte gefunden.', 'vmuseum' ); ?></p>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
