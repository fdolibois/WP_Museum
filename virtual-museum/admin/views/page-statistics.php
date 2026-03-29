<?php if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$rooms    = (int) wp_count_posts( 'museum_room' )->publish;
$vitrines = (int) wp_count_posts( 'museum_vitrine' )->publish;
$galleries= (int) wp_count_posts( 'museum_gallery' )->publish;
$objects  = (int) wp_count_posts( 'museum_object' )->publish;
$relations= (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vm_relations" );
$top_objects = $wpdb->get_results( "SELECT child_id, COUNT(*) as usage_count FROM {$wpdb->prefix}vm_relations WHERE child_type='object' GROUP BY child_id ORDER BY usage_count DESC LIMIT 10" );
$top_rooms   = $wpdb->get_results( "SELECT parent_id, COUNT(*) as child_count FROM {$wpdb->prefix}vm_relations WHERE parent_type='room' GROUP BY parent_id ORDER BY child_count DESC LIMIT 10" );
?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Museum Statistiken', 'vmuseum' ); ?></h1>
    <div class="vm-stats-grid">
        <div class="vm-stats-section">
            <h2><?php esc_html_e( 'Übersicht', 'vmuseum' ); ?></h2>
            <table class="widefat striped">
                <tr><th><?php esc_html_e( 'Räume', 'vmuseum' ); ?></th><td><?php echo esc_html( $rooms ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Vitrinen', 'vmuseum' ); ?></th><td><?php echo esc_html( $vitrines ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Galerien', 'vmuseum' ); ?></th><td><?php echo esc_html( $galleries ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Objekte', 'vmuseum' ); ?></th><td><?php echo esc_html( $objects ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Verknüpfungen', 'vmuseum' ); ?></th><td><?php echo esc_html( $relations ); ?></td></tr>
            </table>
        </div>
        <div class="vm-stats-section">
            <h2><?php esc_html_e( 'Meistverwendete Objekte', 'vmuseum' ); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'Objekt', 'vmuseum' ); ?></th><th><?php esc_html_e( 'Verwendungen', 'vmuseum' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( $top_objects as $row ) :
                    $title = get_the_title( (int) $row->child_id );
                ?><tr>
                    <td><a href="<?php echo esc_url( get_edit_post_link( (int) $row->child_id ) ); ?>"><?php echo esc_html( $title ?: '#' . $row->child_id ); ?></a></td>
                    <td><?php echo esc_html( $row->usage_count ); ?></td>
                </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="vm-stats-section">
            <h2><?php esc_html_e( 'Räume mit den meisten Inhalten', 'vmuseum' ); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e( 'Raum', 'vmuseum' ); ?></th><th><?php esc_html_e( 'Inhalte', 'vmuseum' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( $top_rooms as $row ) :
                    $title = get_the_title( (int) $row->parent_id );
                ?><tr>
                    <td><a href="<?php echo esc_url( get_edit_post_link( (int) $row->parent_id ) ); ?>"><?php echo esc_html( $title ?: '#' . $row->parent_id ); ?></a></td>
                    <td><?php echo esc_html( $row->child_count ); ?></td>
                </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
