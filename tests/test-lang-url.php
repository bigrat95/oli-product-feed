<?php
/**
 * Unit tests for TranslatePress URL conversion (0.4.1).
 *
 * Covers already-prefixed URLs, default-language URLs, variation query strings,
 * and the channel <link>. Run: php tests/test-lang-url.php
 *
 * phpcs:ignoreFile
 *
 * @package Oliprfe
 */

namespace {

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/' );
	}

	$GLOBALS['oliprfe_test_options'] = array(
		'home'        => 'https://site',
		'trp_settings' => array(
			'default-language'  => 'en_US',
			'publish-languages' => array( 'en_US', 'fr_CA' ),
			'url-slugs'         => array(
				'en_US' => 'en',
				'fr_CA' => 'fr',
			),
		),
	);
	$GLOBALS['oliprfe_test_home_url'] = 'http://site/fr';

	if ( ! function_exists( 'get_option' ) ) {
		/**
		 * @param string $name    Option name.
		 * @param mixed  $default Default.
		 * @return mixed
		 */
		function get_option( $name, $default = false ) {
			return array_key_exists( $name, $GLOBALS['oliprfe_test_options'] ) ? $GLOBALS['oliprfe_test_options'][ $name ] : $default;
		}
	}

	if ( ! function_exists( 'home_url' ) ) {
		/**
		 * @param string $path Path.
		 * @return string
		 */
		function home_url( $path = '' ) {
			$home = rtrim( $GLOBALS['oliprfe_test_home_url'], '/' );
			if ( '' === $path || '/' === $path ) {
				return $home . '/';
			}
			return $home . '/' . ltrim( $path, '/' );
		}
	}

	if ( ! function_exists( 'apply_filters' ) ) {
		/**
		 * @param string $tag     Hook.
		 * @param mixed  $value   Value.
		 * @param mixed  ...$args Extra.
		 * @return mixed
		 */
		function apply_filters( $tag, $value, ...$args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
			return $value;
		}
	}

	if ( ! function_exists( 'wp_parse_url' ) ) {
		/**
		 * @param string $url       URL.
		 * @param int    $component Component.
		 * @return mixed
		 */
		function wp_parse_url( $url, $component = -1 ) {
			return parse_url( $url, $component );
		}
	}

	if ( ! function_exists( 'untrailingslashit' ) ) {
		/**
		 * @param string $value Value.
		 * @return string
		 */
		function untrailingslashit( $value ) {
			return rtrim( (string) $value, '/\\' );
		}
	}

	if ( ! function_exists( 'trailingslashit' ) ) {
		/**
		 * @param string $value Value.
		 * @return string
		 */
		function trailingslashit( $value ) {
			return untrailingslashit( $value ) . '/';
		}
	}

	if ( ! function_exists( 'get_bloginfo' ) ) {
		/**
		 * @param string $show Field.
		 * @return string
		 */
		function get_bloginfo( $show = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
			return 'Test Shop';
		}
	}

	if ( ! function_exists( 'trp_translate' ) ) {
		/**
		 * @param string $text     Text.
		 * @param string $language Language.
		 * @param bool   $sanitize Sanitize.
		 * @return string
		 */
		function trp_translate( $text, $language, $sanitize = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
			return $text;
		}
	}

	if ( ! class_exists( 'TRP_Translate_Press' ) ) {
		/**
		 * TranslatePress stub.
		 */
		class TRP_Translate_Press {
			/**
			 * @return self
			 */
			public static function get_trp_instance() {
				return new self();
			}

			/**
			 * @param string $name Component.
			 * @return Oliprfe_Test_Url_Converter|null
			 */
			public function get_component( $name ) {
				return 'url_converter' === $name ? new Oliprfe_Test_Url_Converter() : null;
			}
		}
	}

	if ( ! class_exists( 'Oliprfe_Test_Url_Converter' ) ) {
		/**
		 * Simulates TranslatePress get_url_for_language() after trp_switch_language():
		 * adds /fr/ even when it is already there, and rewrites https to http.
		 */
		class Oliprfe_Test_Url_Converter {
			/**
			 * double: add /fr/ even if present (0.4.0 bug). once: add only if missing. noop: return input.
			 *
			 * @var string
			 */
			public static $mode = 'double';

			/**
			 * @var int
			 */
			public static $calls = 0;

			/**
			 * @param string $language Target language.
			 * @param string $url      Source URL.
			 * @param string $trp_link Unused.
			 * @return string
			 */
			public function get_url_for_language( $language, $url, $trp_link = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				++self::$calls;
				if ( 'noop' === self::$mode ) {
					return (string) $url;
				}
				$url   = preg_replace( '#^https://#', 'http://', (string) $url );
				$parts = parse_url( $url );
				if ( ! is_array( $parts ) ) {
					return $url;
				}
				$path   = isset( $parts['path'] ) ? $parts['path'] : '/';
				$has_fr = (bool) preg_match( '#^/fr(/|$)#', $path );
				if ( 'double' === self::$mode && $has_fr ) {
					$path = '/fr' . $path;
				} elseif ( ! $has_fr ) {
					$path = '/fr' . ( '/' === $path ? '/' : $path );
				}
				$out = 'http://' . ( isset( $parts['host'] ) ? $parts['host'] : 'site' );
				if ( isset( $parts['port'] ) ) {
					$out .= ':' . $parts['port'];
				}
				$out .= $path;
				if ( isset( $parts['query'] ) ) {
					$out .= '?' . $parts['query'];
				}
				if ( isset( $parts['fragment'] ) ) {
					$out .= '#' . $parts['fragment'];
				}
				return $out;
			}
		}
	}

	require_once dirname( __DIR__ ) . '/src/Util.php';
	require_once dirname( __DIR__ ) . '/src/Lang.php';
	require_once dirname( __DIR__ ) . '/src/Generator.php';
}

namespace Oliprfe {

	$oliprfe_failed = 0;
	$oliprfe_passed = 0;

	/**
	 * @param string $name     Case name.
	 * @param mixed  $expected Expected.
	 * @param mixed  $actual   Actual.
	 */
	$oliprfe_assert = static function ( $name, $expected, $actual ) use ( &$oliprfe_failed, &$oliprfe_passed ) {
		if ( $expected === $actual ) {
			++$oliprfe_passed;
			echo 'OK  ' . $name . "\n";
			return;
		}
		++$oliprfe_failed;
		echo 'FAIL ' . $name . "\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n";
	};

	// --- Already-prefixed product URL (get_permalink() after trp_switch_language()).
	\Oliprfe_Test_Url_Converter::$mode  = 'double';
	\Oliprfe_Test_Url_Converter::$calls = 0;
	$oliprfe_assert(
		'already-prefixed product URL is not doubled and keeps https',
		'https://site/fr/produit/slug/',
		Lang::feed_url( 'https://site/fr/produit/slug/', 'fr_CA', true )
	);
	$oliprfe_assert(
		'already-prefixed URL does not call the converter',
		0,
		\Oliprfe_Test_Url_Converter::$calls
	);

	$oliprfe_assert(
		'already-prefixed http URL is upgraded by force_https',
		'https://site/fr/produit/slug/',
		Lang::feed_url( 'http://site/fr/produit/slug/', 'fr_CA', true )
	);

	$oliprfe_assert(
		'doubled /fr/fr/ is collapsed',
		'https://site/fr/produit/slug/',
		Lang::feed_url( 'http://site/fr/fr/produit/slug/', 'fr_CA', true )
	);

	// --- Default-language URL, converted once (converter drops https).
	\Oliprfe_Test_Url_Converter::$mode  = 'double';
	\Oliprfe_Test_Url_Converter::$calls = 0;
	$oliprfe_assert(
		'default-language product URL is converted once and https restored',
		'https://site/fr/produit/slug/',
		Lang::feed_url( 'https://site/produit/slug/', 'fr_CA', true )
	);
	$oliprfe_assert(
		'default-language URL calls the converter once',
		1,
		\Oliprfe_Test_Url_Converter::$calls
	);

	$oliprfe_assert(
		'English product slug converted once',
		'https://site/fr/product/slug/',
		Lang::feed_url( 'https://site/product/slug/', 'fr_CA', true )
	);

	// --- Variation query string.
	\Oliprfe_Test_Url_Converter::$calls = 0;
	$oliprfe_assert(
		'default-language variation query string is preserved',
		'https://site/fr/produit/slug/?attribute_pa_color=noir',
		Lang::feed_url( 'https://site/produit/slug/?attribute_pa_color=noir', 'fr_CA', true )
	);
	$oliprfe_assert(
		'already-prefixed variation query string is not doubled',
		'https://site/fr/produit/slug/?attribute_pa_color=noir',
		Lang::feed_url( 'https://site/fr/produit/slug/?attribute_pa_color=noir', 'fr_CA', true )
	);

	// --- prefix_url fallback when home_url() is already /fr/ (converter noop).
	\Oliprfe_Test_Url_Converter::$mode = 'noop';
	$oliprfe_assert(
		'prefix_url uses default home, not switched home_url()',
		'https://site/fr/produit/slug/',
		Lang::feed_url( 'https://site/produit/slug/', 'fr_CA', true )
	);
	$oliprfe_assert(
		'prefix_url on already-prefixed URL is idempotent',
		'https://site/fr/produit/slug/',
		Lang::feed_url( 'https://site/fr/produit/slug/', 'fr_CA', true )
	);
	$oliprfe_assert(
		'prefix_url preserves variation query string',
		'https://site/fr/produit/slug/?attribute_color=red',
		Lang::feed_url( 'https://site/produit/slug/?attribute_color=red', 'fr_CA', true )
	);

	// --- Channel <link>: default-language home, convert once, force_https after.
	\Oliprfe_Test_Url_Converter::$mode = 'double';
	$oliprfe_assert(
		'default_home_url ignores switched home_url()',
		'https://site/',
		Lang::default_home_url()
	);

	$oliprfe_feed = Generator::context(
		array(
			'language'    => 'fr_CA',
			'force_https' => true,
			'name'        => 'Google FR',
		)
	);
	$oliprfe_assert(
		'channel link is https://site/fr/ not http://site/fr/fr/',
		'https://site/fr/',
		$oliprfe_feed['site_link']
	);

	\Oliprfe_Test_Url_Converter::$mode = 'noop';
	$oliprfe_feed                      = Generator::context(
		array(
			'language'    => 'fr_CA',
			'force_https' => true,
			'name'        => 'Google FR',
		)
	);
	$oliprfe_assert(
		'channel link via prefix_url is https://site/fr/',
		'https://site/fr/',
		$oliprfe_feed['site_link']
	);

	$oliprfe_assert(
		'already-prefixed channel home is not doubled',
		'https://site/fr/',
		Lang::feed_url( 'http://site/fr/', 'fr_CA', true )
	);

	echo "\n" . $oliprfe_passed . ' passed, ' . $oliprfe_failed . " failed\n";
	exit( $oliprfe_failed > 0 ? 1 : 0 );
}
