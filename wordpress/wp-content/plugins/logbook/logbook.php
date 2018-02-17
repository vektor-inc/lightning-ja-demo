<?php
/**
 * Plugin Name:     LogBook
 * Plugin URI:      https://github.com/tarosky/logbook
 * Description:     A logging plugin.
 * Author:          Takayuki Miyauchi
 * Author URI:      https://tarosky.co.jp/
 * Text Domain:     logbook
 * Domain Path:     /languages
 * Version:         1.0.5
 *
 * @package         LogBook
 */

namespace LogBook;

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'log', 'LogBook\CLI' );
}

function plugins_loaded() {
	// Registers post type `logbook`.
	$post_type = new Post_Type();
	$post_type->register();

	// Registers admin panel.
	if ( is_admin() ) {
		$admin = new Admin();
		$admin->register();
	}

	/**
	 * Filters the array of default loggers.
	 *
	 * @param array $logger_classes An array of classes of `\LogBook\Logger`.
	 */
	$loggers = apply_filters( 'logbook_default_loggers', array(
		'LogBook\Logger\Activated_Extensions',
		'LogBook\Logger\Delete_Post',
		'LogBook\Logger\Last_Error',
		'LogBook\Logger\Post_Updated',
		'LogBook\Logger\Updated_Core',
		'LogBook\Logger\Updated_Extensions',
		'LogBook\Logger\WP_Login',
		'LogBook\Logger\XML_RPC',
	) );

	foreach ( $loggers as $logger ) {
		init_log( $logger );
	}

	add_action( 'rest_api_init', function() {
		$rest = new Rest_Logs_Controller( Post_Type::post_type );
		$rest->register_routes();
	} );
}

add_action( 'plugins_loaded', 'LogBook\plugins_loaded', 9 );

/**
 * Registers the logger to the specific hooks.
 *
 * @param string $logger_class The extended class of the `LogBook\Logger`.
 */
function init_log( $logger_class ) {
	if ( class_exists( $logger_class ) ) {
		$result = Event::get_instance()->init_log( $logger_class );
		if ( is_wp_error( $result ) ) {
			wp_die( 'Incorrect `LogBook\Logger` object.' );
		}
	} else {
		wp_die( '`' . $logger_class . '` not found.' );
	}
}
