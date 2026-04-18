<?php
/**
 * Plugin Name: Elegance Links Redirect
 * Plugin URI:  https://github.com/didoivanov/elegance-links-redirect
 * Description: Create cloaked pretty links (e.g. /go, /play), redirect with configurable 301/302/307 codes, apply dynamic rules based on visitor country and device, and track every click.
 * Version:     1.0.0
 * Author:      didoivanov
 * License:     GPL-2.0-or-later
 * Text Domain: elegance-links-redirect
 * Network:     true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELR_VERSION', '1.0.0' );
define( 'ELR_PLUGIN_FILE', __FILE__ );
define( 'ELR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ELR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ELR_TABLE_LINKS', 'elr_links' );
define( 'ELR_TABLE_RULES', 'elr_rules' );
define( 'ELR_TABLE_CLICKS', 'elr_clicks' );

require_once ELR_PLUGIN_DIR . 'includes/class-elr-database.php';
require_once ELR_PLUGIN_DIR . 'includes/class-elr-device-detector.php';
require_once ELR_PLUGIN_DIR . 'includes/class-elr-geolocation.php';
require_once ELR_PLUGIN_DIR . 'includes/class-elr-tracker.php';
require_once ELR_PLUGIN_DIR . 'includes/class-elr-redirect.php';
require_once ELR_PLUGIN_DIR . 'includes/class-elr-admin.php';
require_once ELR_PLUGIN_DIR . 'includes/class-elr-plugin.php';

register_activation_hook( __FILE__, array( 'ELR_Plugin', 'on_activate' ) );
register_deactivation_hook( __FILE__, array( 'ELR_Plugin', 'on_deactivate' ) );

add_action( 'plugins_loaded', array( 'ELR_Plugin', 'boot' ) );
