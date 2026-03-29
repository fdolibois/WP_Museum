<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$object_id   = get_the_ID();
$media_type  = get_post_meta( $object_id, 'vm_media_type', true ) ?: 'image';
$media_url   = get_post_meta( $object_id, 'vm_media_url', true );
$year        = get_post_meta( $object_id, 'vm_year', true );
$year_end    = get_post_meta( $object_id, 'vm_year_end', true );
$copyright   = get_post_meta( $object_id, 'vm_copyright', true );
$settings    = get_option( 'vm_settings', [] );
$context_param = sanitize_text_field( wp_unslash( $_GET['vm_context'] ?? '' ) );
?>
<div class="vm-object-page vm-page" data-object-id="<?php echo esc_attr( $object_id ); ?>">

    <?php include VM_PLUGIN_DIR . 'public/templates/partials/breadcrumb-context.php'; ?>

    <article class="vm-object">

        <div class="vm-object__media vm-object__media--<?php echo esc_attr( $media_type ); ?>">
            <?php
            switch ( $media_type ) {
                case 'image':
                    if ( has_post_thumbnail() ) {
                        echo '<a href="' . esc_url( get_the_post_thumbnail_url( $object_id, 'full' ) ) . '" class="vm-lightbox-trigger" data-type="image">';
                        the_post_thumbnail( 'large' );
                        echo '</a>';
                    }
                    break;

                case 'audio':
                    if ( $media_url ) {
                        echo '<div class="vm-audio-player">';
                        echo '<audio controls preload="metadata">';
                        echo '<source src="' . esc_url( $media_url ) . '" type="audio/mpeg">';
                        echo esc_html__( 'Ihr Browser unterstützt kein Audio.', 'vmuseum' );
                        echo '</audio>';
                        echo '</div>';
                    }
                    break;

                case 'video':
                    if ( $media_url ) {
                        if ( preg_match( '/(?:youtube|youtu\.be|vimeo)/', $media_url ) ) {
                            echo '<div class="vm-video-embed">';
                            echo wp_oembed_get( $media_url );
                            echo '</div>';
                        } else {
                            echo '<video controls class="vm-video">';
                            echo '<source src="' . esc_url( $media_url ) . '">';
                            echo '</video>';
                        }
                    }
                    break;

                case '360':
                    if ( $media_url ) {
                        echo '<div id="vm-panorama-' . esc_attr( $object_id ) . '" class="vm-panorama" data-src="' . esc_url( $media_url ) . '" style="height:400px"></div>';
                        echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof pannellum!=="undefined"){pannellum.viewer("vm-panorama-' . esc_attr( $object_id ) . '",{type:"equirectangular",panorama:"' . esc_url( $media_url ) . '",autoLoad:true});}});</script>';
                    }
                    break;

                case 'document':
                    if ( $media_url ) {
                        echo '<div class="vm-document">';
                        echo '<a href="' . esc_url( $media_url ) . '" class="button vm-document__link" target="_blank" rel="noopener">';
                        echo '<span class="dashicons dashicons-media-document"></span> ';
                        echo esc_html__( 'Dokument öffnen', 'vmuseum' );
                        echo '</a>';
                        echo '</div>';
                    }
                    break;

                case 'nopics':
                default:
                    if ( has_post_thumbnail() ) the_post_thumbnail( 'large' );
                    break;
            }
            ?>
        </div>

        <div class="vm-object__content">
            <div class="vm-object__main">
                <h1 class="vm-object__title"><?php the_title(); ?></h1>
                <div class="vm-object__description"><?php the_content(); ?></div>
            </div>
            <aside class="vm-object__meta">
                <?php if ( $year ) : ?>
                    <div class="vm-meta-item">
                        <span class="vm-meta-label"><?php esc_html_e( 'Jahr', 'vmuseum' ); ?></span>
                        <span class="vm-meta-value"><?php echo esc_html( $year ); ?><?php if ( $year_end && $year_end !== $year ) echo '–' . esc_html( $year_end ); ?></span>
                    </div>
                <?php endif; ?>
                <?php
                $era_terms = get_the_terms( $object_id, 'museum_era' );
                if ( $era_terms && ! is_wp_error( $era_terms ) ) : ?>
                    <div class="vm-meta-item">
                        <span class="vm-meta-label"><?php esc_html_e( 'Epoche', 'vmuseum' ); ?></span>
                        <span class="vm-meta-value"><?php echo esc_html( implode( ', ', wp_list_pluck( $era_terms, 'name' ) ) ); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ( $copyright ) : ?>
                    <div class="vm-meta-item">
                        <span class="vm-meta-label"><?php esc_html_e( 'Quelle', 'vmuseum' ); ?></span>
                        <span class="vm-meta-value"><?php echo esc_html( $copyright ); ?></span>
                    </div>
                <?php endif; ?>
            </aside>
        </div>

        <?php if ( ! empty( $settings['show_relation_badge'] ) ) : ?>
        <?php include VM_PLUGIN_DIR . 'public/templates/partials/relation-badge.php'; ?>
        <?php endif; ?>

    </article>
</div>

<?php get_footer(); ?>
