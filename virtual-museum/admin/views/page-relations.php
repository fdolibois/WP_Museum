<?php if ( ! defined( 'ABSPATH' ) ) exit;
$rooms       = get_posts( [ 'post_type' => 'museum_room', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
$filter_room = isset( $_GET['room_id'] ) ? (int) $_GET['room_id'] : 0;
?>
<div class="wrap vm-admin-page">
    <h1><?php esc_html_e( 'Beziehungen verwalten', 'vmuseum' ); ?></h1>
    <div class="vm-relations-filter">
        <form method="get">
            <input type="hidden" name="page" value="vm-relations">
            <label for="room_filter"><?php esc_html_e( 'Raum:', 'vmuseum' ); ?></label>
            <select name="room_id" id="room_filter">
                <option value=""><?php esc_html_e( 'Alle Räume', 'vmuseum' ); ?></option>
                <?php foreach ( $rooms as $room ) : ?>
                    <option value="<?php echo esc_attr( $room->ID ); ?>" <?php selected( $filter_room, $room->ID ); ?>><?php echo esc_html( get_the_title( $room ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button( __( 'Filtern', 'vmuseum' ), 'secondary', 'submit', false ); ?>
        </form>
    </div>
    <?php
    $display = $filter_room ? [ get_post( $filter_room ) ] : $rooms;
    foreach ( $display as $room ) :
        if ( ! $room ) continue;
        $vitrines  = VM_Relations::get_vitrines( $room->ID );
        $galleries = VM_Relations::get_galleries( 'room', $room->ID );
        $objects   = VM_Relations::get_objects( 'room', $room->ID );
    ?>
    <div class="vm-relation-block">
        <h2>🚪 <?php echo esc_html( get_the_title( $room ) ); ?>
            <a href="<?php echo esc_url( get_edit_post_link( $room->ID ) ); ?>" class="button button-small"><?php esc_html_e( 'Bearbeiten', 'vmuseum' ); ?></a>
        </h2>
        <div class="vm-relation-columns">
            <div class="vm-rel-col">
                <h3><?php esc_html_e( 'Vitrinen', 'vmuseum' ); ?> (<?php echo count( $vitrines ); ?>)</h3>
                <ul><?php foreach ( $vitrines as $v ) : ?><li><a href="<?php echo esc_url( get_edit_post_link( $v->ID ) ); ?>">🗄️ <?php echo esc_html( get_the_title( $v ) ); ?></a></li><?php endforeach; ?></ul>
            </div>
            <div class="vm-rel-col">
                <h3><?php esc_html_e( 'Galerien', 'vmuseum' ); ?> (<?php echo count( $galleries ); ?>)</h3>
                <ul><?php foreach ( $galleries as $g ) : ?><li><a href="<?php echo esc_url( get_edit_post_link( $g->ID ) ); ?>">🖼️ <?php echo esc_html( get_the_title( $g ) ); ?></a></li><?php endforeach; ?></ul>
            </div>
            <div class="vm-rel-col">
                <h3><?php esc_html_e( 'Direkte Objekte', 'vmuseum' ); ?> (<?php echo count( $objects ); ?>)</h3>
                <ul><?php foreach ( $objects as $o ) : ?><li><a href="<?php echo esc_url( get_edit_post_link( $o->ID ) ); ?>">🎨 <?php echo esc_html( get_the_title( $o ) ); ?></a></li><?php endforeach; ?></ul>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
