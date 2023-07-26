<?php
/**
	Plugin Name: Ultimate Member - Translatepress
	Plugin URI:  https://github.com/umdevelopera/um-translatepress
	Description: Integrates Ultimate Member with Translatepress.
	Version:     1.0.0
	Author:      umdevelopera
	Author URI:  https://github.com/umdevelopera
	Text Domain: um-translatepress
	Domain Path: /languages
	UM version:  2.6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_data = get_plugin_data( __FILE__ );

define( 'um_translatepress_url', plugin_dir_url( __FILE__ ) );
define( 'um_translatepress_path', plugin_dir_path( __FILE__ ) );
define( 'um_translatepress_plugin', plugin_basename( __FILE__ ) );
define( 'um_translatepress_extension', $plugin_data['Name'] );
define( 'um_translatepress_version', $plugin_data['Version'] );
define( 'um_translatepress_textdomain', 'um-translatepress' );
define( 'um_translatepress_requires', '2.6.2' );

// Activation script.
if ( ! function_exists( 'um_translatepress_activation_hook' ) ) {
	function um_translatepress_activation_hook() {
		$version = get_option( 'um_translatepress_version' );
		if ( ! $version ) {
			update_option( 'um_translatepress_last_version_upgrade', um_translatepress_version );
		}
		if ( um_translatepress_version !== $version ) {
			update_option( 'um_translatepress_version', um_translatepress_version );
		}
	}
}
register_activation_hook( um_translatepress_plugin, 'um_translatepress_activation_hook' );

// Check dependencies.
if ( ! function_exists( 'um_translatepress_check_dependencies' ) ) {
	function um_translatepress_check_dependencies() {
		if ( ! defined( 'um_path' ) || ! function_exists( 'UM' ) || ! UM()->dependencies()->ultimatemember_active_check() ) {
			// Ultimate Member is not active.
			add_action(
				'admin_notices',
				function () {
					// translators: %s - plugin name.
					echo '<div class="error"><p>' . wp_kses_post( sprintf( __( 'The <strong>%s</strong> extension requires the Ultimate Member plugin to be activated to work properly. You can download it <a href="https://wordpress.org/plugins/ultimate-member">here</a>', 'um-translatepress' ), um_translatepress_extension ) ) . '</p></div>';
				}
			);
		} elseif ( ! defined( 'TRP_PLUGIN_VERSION' ) ) {
			// Translatepress is not active.
			add_action(
				'admin_notices',
				function () {
					// translators: %s - plugin name.
					echo '<div class="error"><p>' . wp_kses_post( sprintf( __( 'The <strong>%s</strong> extension requires the Translatepress plugin to be activated to work properly. You can download it <a href="https://wordpress.org/plugins/translatepress-multilingual/">here</a>', 'um-translatepress' ), um_translatepress_extension ) ) . '</p></div>';
				}
			);
		} else {
			require_once 'includes/core/class-um-translatepress.php';
			function um_translatepress_init() {
				if ( function_exists( 'UM' ) ) {
					UM()->set_class( 'Translatepress', true );
				}
			}
			add_action( 'plugins_loaded', 'um_translatepress_init', 4, 1 );
		}
	}
}
add_action( 'plugins_loaded', 'um_translatepress_check_dependencies', 2 );
