<?php if ( ! defined( 'ABSPATH' ) ) exit;
$rooms    = wp_count_posts( 'museum_room' );
$vitrines = wp_count_posts( 'museum_vitrine' );
$galleries= wp_count_posts( 'museum_gallery' );
$objects  = wp_count_posts( 'museum_object' );
global $wpdb;
$rel_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vm_relations" );
?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Virtuelles Museum — Dashboard', 'vmuseum' ); ?></h1>
    <div class="vm-dashboard-stats">
        <?php
        $cards = [
            [ 'icon' => '🚪', 'count' => $rooms->publish ?? 0,     'label' => __( 'Räume', 'vmuseum' ),       'url' => 'edit.php?post_type=museum_room' ],
            [ 'icon' => '🗄️', 'count' => $vitrines->publish ?? 0,  'label' => __( 'Vitrinen', 'vmuseum' ),    'url' => 'edit.php?post_type=museum_vitrine' ],
            [ 'icon' => '🖼️', 'count' => $galleries->publish ?? 0, 'label' => __( 'Galerien', 'vmuseum' ),   'url' => 'edit.php?post_type=museum_gallery' ],
            [ 'icon' => '🎨', 'count' => $objects->publish ?? 0,   'label' => __( 'Objekte', 'vmuseum' ),    'url' => 'edit.php?post_type=museum_object' ],
            [ 'icon' => '🔗', 'count' => $rel_count,               'label' => __( 'Verknüpfungen', 'vmuseum' ), 'url' => 'admin.php?page=vm-relations' ],
        ];
        foreach ( $cards as $card ) : ?>
        <div class="vm-stat-card">
            <div class="vm-stat-icon"><?php echo $card['icon']; ?></div>
            <div class="vm-stat-number"><?php echo esc_html( $card['count'] ); ?></div>
            <div class="vm-stat-label"><?php echo esc_html( $card['label'] ); ?></div>
            <a href="<?php echo esc_url( admin_url( $card['url'] ) ); ?>" class="vm-stat-link"><?php esc_html_e( 'Verwalten', 'vmuseum' ); ?></a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="vm-dashboard-quicklinks">
        <h2><?php esc_html_e( 'Schnellzugriff', 'vmuseum' ); ?></h2>
        <div class="vm-quicklink-grid">
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=museum_room' ) ); ?>" class="vm-quicklink"><span class="dashicons dashicons-admin-home"></span><?php esc_html_e( 'Neuer Raum', 'vmuseum' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=museum_vitrine' ) ); ?>" class="vm-quicklink"><span class="dashicons dashicons-screenoptions"></span><?php esc_html_e( 'Neue Vitrine', 'vmuseum' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=museum_gallery' ) ); ?>" class="vm-quicklink"><span class="dashicons dashicons-images-alt2"></span><?php esc_html_e( 'Neue Galerie', 'vmuseum' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=museum_object' ) ); ?>" class="vm-quicklink"><span class="dashicons dashicons-format-image"></span><?php esc_html_e( 'Neues Objekt', 'vmuseum' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vm-relation-map' ) ); ?>" class="vm-quicklink"><span class="dashicons dashicons-networking"></span><?php esc_html_e( 'Beziehungskarte', 'vmuseum' ); ?></a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vm-import' ) ); ?>" class="vm-quicklink"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Massenimport', 'vmuseum' ); ?></a>
        </div>
    </div>
</div>
