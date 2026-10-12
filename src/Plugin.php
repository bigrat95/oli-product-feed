<?php
/**
 * Plugin bootstrap.
 *
 * @package Oliprfe
 */

namespace Oliprfe;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every component.
 */
final class Plugin {

	/**
	 * Boots the plugin (plugins_loaded).
	 */
	public static function boot() {
		add_action( 'init', array( Feeds::class, 'register_post_type' ) );
		add_action( 'wp_trash_post', array( Feeds::class, 'on_trash' ) );
		add_action( 'transition_post_status', array( Feeds::class, 'on_status' ), 10, 3 );
		add_action( 'before_delete_post', array( Feeds::class, 'on_trash' ) );
		add_action( 'untrashed_post', array( Feeds::class, 'on_untrash' ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'missing_woocommerce_notice' ) );
			return;
		}

		Integrations\PriceBasedOnCountry::init();
		Endpoint::init();
		Scheduler::init();

		if ( is_admin() ) {
			Admin\Admin::init();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'oli-feed', Cli::class );
		}
	}

	/**
	 * Admin notice when WooCommerce is missing.
	 */
	public static function missing_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Oli Product Feed requires WooCommerce to be installed and active.', 'oli-product-feed' ) . '</p></div>';
	}

	/**
	 * Activation: nothing to create (the output folder is created on the first generation,
	 * so that a custom output folder setting is respected).
	 */
	public static function activate() {
	}

	/**
	 * Deactivation: unschedule every background action.
	 */
	public static function deactivate() {
		Scheduler::unschedule_all();
	}
}
