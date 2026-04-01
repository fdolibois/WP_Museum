<?php
/**
 * Plugin Name:       Virtuelles Museum
 * Plugin URI:        https://yourinsight.digital/
 * Description:       Vollständige Verwaltung eines virtuellen Heimatmuseums mit relationalem Inhaltsmodell (Räume · Galerien · Vitrinen · Objekte).
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Virtuelles Museum Team
 * License:           GPL v2 or later
 * Text Domain:       vmuseum
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VM_PLUGIN_VERSION', '2.0.0' );
define( 'VM_PLUGIN_FILE',    __FILE__ );
define( 'VM_PLUGIN_DIR',     plugin_dir_path( __FILE__ ) );
define( 'VM_PLUGIN_URL',     plugin_dir_url( __FILE__ ) );
define( 'VM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoloader
spl_autoload_register( function( $class ) {
    $prefix = 'VM_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }
    $class_name = strtolower( str_replace( '_', '-', $class ) );
    $class_name = 'class-' . $class_name . '.php';
    $directories = [
        VM_PLUGIN_DIR . 'includes/',
        VM_PLUGIN_DIR . 'admin/',
        VM_PLUGIN_DIR . 'public/',
    ];
    foreach ( $directories as $dir ) {
        $file = $dir . $class_name;
        if ( file_exists( $file ) ) {
            require_once $file;
            return;
        }
    }
} );

// Activation / Deactivation
register_activation_hook(   __FILE__, [ 'VM_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'VM_Activator', 'deactivate' ] );

// Boot
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'vmuseum', false, dirname( VM_PLUGIN_BASENAME ) . '/languages' );
    VM_Plugin::get_instance();
} );
