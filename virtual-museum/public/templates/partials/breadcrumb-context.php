<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings      = get_option( 'vm_settings', [] );
$context_param = sanitize_text_field( wp_unslash( $_GET['vm_context'] ?? '' ) );
$post_id       = get_the_ID();

if ( empty( $settings['enable_breadcrumb'] ) ) return;

$crumbs = [];
$crumbs[] = [ 'title' => __( 'Museum', 'vmuseum' ), 'url' => get_post_type_archive_link( 'museum_object' ) ];

if ( $context_param ) {
    // Parse context chain, e.g. "room_42,vitrine_7,gallery_15"
    $parts = explode( ',', $context_param );
    foreach ( $parts as $part ) {
        $part = trim( $part );
        if ( preg_match( '/^(room|vitrine|gallery|object)_(\d+)$/', $part, $m ) ) {
            $ctx_post = get_post( (int) $m[2] );
            if ( $ctx_post ) {
                $ctx_url = add_query_arg( 'vm_context', $part, get_permalink( $ctx_post->ID ) );
                $crumbs[] = [ 'title' => get_the_title( $ctx_post ), 'url' => $ctx_url ];
            }
        }
    }
}

// Current page (no link on last item)
if ( $post_id ) {
    $crumbs[] = [ 'title' => get_the_title( $post_id ), 'url' => '' ];
}

if ( count( $crumbs ) <= 1 ) return;
?>
<nav class="vm-breadcrumb" aria-label="<?php esc_attr_e( 'Navigationspfad', 'vmuseum' ); ?>">
    <ol class="vm-breadcrumb__list">
        <?php foreach ( $crumbs as $i => $crumb ) :
            $is_last = $i === count( $crumbs ) - 1;
            ?>
            <li class="vm-breadcrumb__item<?php echo $is_last ? ' vm-breadcrumb__item--current' : ''; ?>">
                <?php if ( ! $is_last && $crumb['url'] ) : ?>
                    <a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['title'] ); ?></a>
                    <span class="vm-breadcrumb__sep" aria-hidden="true">›</span>
                <?php else : ?>
                    <span aria-current="page"><?php echo esc_html( $crumb['title'] ); ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
