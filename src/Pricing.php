<?php
/**
 * Prices of a product for a feed.
 *
 * @package Oliprfe
 */

namespace Oliprfe;

defined( 'ABSPATH' ) || exit;

/**
 * Price resolution. Integrations replace the WooCommerce prices through the `oliprfe_price` filter.
 */
final class Pricing {

	/**
	 * Price sources (id => label). Integrations add theirs with `oliprfe_price_sources`.
	 *
	 * @return array<string,string>
	 */
	public static function sources() {
		return apply_filters(
			'oliprfe_price_sources',
			array( 'woocommerce' => __( 'WooCommerce prices', 'oli-product-feed' ) )
		);
	}

	/**
	 * Prices of a product in the feed currency.
	 *
	 * Returns null when the product has no usable price (it is then excluded).
	 * Keys: regular (float), sale (float|null), sale_from (int|null timestamp), sale_to (int|null timestamp).
	 *
	 * @param \WC_Product         $product Product or variation.
	 * @param array<string,mixed> $feed    Feed configuration.
	 * @return array<string,mixed>|null
	 */
	public static function get( $product, array $feed ) {
		$prices = self::woocommerce( $product );

		/**
		 * Filters the prices of a product for a feed. Return null to exclude the product.
		 *
		 * @param array|null  $prices  { regular, sale, sale_from, sale_to }.
		 * @param \WC_Product $product Product.
		 * @param array       $feed    Feed configuration.
		 */
		$prices = apply_filters( 'oliprfe_price', $prices, $product, $feed );

		if ( ! is_array( $prices ) || ! isset( $prices['regular'] ) || ! is_numeric( $prices['regular'] ) || (float) $prices['regular'] <= 0 ) {
			return null;
		}
		$prices = wp_parse_args(
			$prices,
			array(
				'sale'      => null,
				'sale_from' => null,
				'sale_to'   => null,
			)
		);
		$now = time();
		// A sale that is over, or that is not lower than the regular price, is ignored.
		if ( null !== $prices['sale'] && ( ! is_numeric( $prices['sale'] ) || (float) $prices['sale'] <= 0 || (float) $prices['sale'] >= (float) $prices['regular'] || ( $prices['sale_to'] && (int) $prices['sale_to'] < $now ) ) ) {
			$prices['sale']      = null;
			$prices['sale_from'] = null;
			$prices['sale_to']   = null;
		}
		$prices['regular'] = (float) $prices['regular'];
		$prices['sale']    = null === $prices['sale'] ? null : (float) $prices['sale'];
		return $prices;
	}

	/**
	 * Raw WooCommerce prices ('edit' context, so that no front-end price filter changes them).
	 *
	 * @param \WC_Product $product Product.
	 * @return array<string,mixed>|null
	 */
	public static function woocommerce( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			$regular = $product->get_variation_regular_price( 'min', false );
			$sale    = $product->get_variation_sale_price( 'min', false );
			return array(
				'regular'   => '' === $regular ? null : (float) $regular,
				'sale'      => ( '' === $sale || (float) $sale >= (float) $regular ) ? null : (float) $sale,
				'sale_from' => null,
				'sale_to'   => null,
			);
		}
		$regular = $product->get_regular_price( 'edit' );
		$sale    = $product->get_sale_price( 'edit' );
		$from    = $product->get_date_on_sale_from( 'edit' );
		$to      = $product->get_date_on_sale_to( 'edit' );
		if ( '' === $regular || null === $regular ) {
			return null;
		}
		return array(
			'regular'   => (float) $regular,
			'sale'      => '' === $sale || null === $sale ? null : (float) $sale,
			'sale_from' => $from ? $from->getTimestamp() : null,
			'sale_to'   => $to ? $to->getTimestamp() : null,
		);
	}
}
