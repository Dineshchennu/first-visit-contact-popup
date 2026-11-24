<?php
/**
 * Plugin Name:       First Visit Contact Popup
 * Description:       Show a contact popup to first-time visitors, save every lead in your dashboard and get an email for each one.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Dinesh
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       first-visit-contact-popup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FVCP_VERSION', '1.0.0' );
define( 'FVCP_FILE', __FILE__ );
define( 'FVCP_DIR', plugin_dir_path( __FILE__ ) );
define( 'FVCP_URL', plugin_dir_url( __FILE__ ) );

/* ---------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

function fvcp_table() {
	global $wpdb;
	return $wpdb->prefix . 'fvcp_leads';
}

function fvcp_default_options() {
	return array(
		'enabled'      => 1,
		'heading'      => 'Welcome! Get in touch',
		'subtext'      => 'Leave your details and we will get back to you shortly.',
		'button_text'  => 'Contact Me',
		'success_text' => 'Thank you! We will contact you soon.',
		'delay'        => 3,
		'cookie_days'  => 30,
		'notify_email' => get_option( 'admin_email' ),
		'accent_color' => '#2563eb',
	);
}

function fvcp_get_options() {
	return wp_parse_args( get_option( 'fvcp_options', array() ), fvcp_default_options() );
}

/* ---------------------------------------------------------------------
 * Activation
 * ------------------------------------------------------------------ */

function fvcp_activate() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = fvcp_table();
	$charset = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(100) NOT NULL DEFAULT '',
		email varchar(190) NOT NULL DEFAULT '',
		message text NOT NULL,
		page_url varchar(255) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY created_at (created_at)
	) {$charset};";

	dbDelta( $sql );

	if ( false === get_option( 'fvcp_options', false ) ) {
		add_option( 'fvcp_options', fvcp_default_options() );
	}
}
register_activation_hook( __FILE__, 'fvcp_activate' );

/* ---------------------------------------------------------------------
 * Load
 * ------------------------------------------------------------------ */

require_once FVCP_DIR . 'includes/class-fvcp-frontend.php';
require_once FVCP_DIR . 'includes/class-fvcp-admin.php';

add_action(
	'plugins_loaded',
	function () {
		new FVCP_Frontend();
		if ( is_admin() ) {
			new FVCP_Admin();
		}
	}
);
