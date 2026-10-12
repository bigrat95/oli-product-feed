<?php
/**
 * Plugin Name: Oli Product Feed - early feed endpoint
 * Description: Optional loader. Serves ?oliprfe_feed=<file> before the regular plugins load (faster). Copy this file to wp-content/mu-plugins/. It does nothing when Oli Product Feed is inactive or its WordPress serving is off. Expects the plugin folder name oli-product-feed (the WordPress.org slug).
 * Version: 0.3.6
 * Author: Olivier Bigras
 * License: GPLv2 or later
 *
 * @package Oliprfe
 */

defined( 'ABSPATH' ) || exit;

if ( isset( $_GET['oliprfe_feed'] ) && defined( 'WP_PLUGIN_DIR' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only endpoint.
	// Only when the plugin is active. The path is a local plugin file under WP_PLUGIN_DIR (never a remote URL).
	if ( in_array( 'oli-product-feed/oli-product-feed.php', (array) get_option( 'active_plugins', array() ), true ) ) {
		require_once WP_PLUGIN_DIR . '/oli-product-feed/src/Endpoint.php';
		if ( class_exists( '\Oliprfe\Endpoint' ) ) {
			\Oliprfe\Endpoint::serve_early();
		}
	}
}
