<?php
/**
 * Language layer: TranslatePress adapter (strings and URLs). Without a multilingual plugin, values are returned untouched.
 *
 * @package Oliprfe
 */

namespace Oliprfe;

defined( 'ABSPATH' ) || exit;

/**
 * Multilingual helpers.
 */
final class Lang {

	/**
	 * Per-request cache of translated strings.
	 *
	 * @var array<string,string>
	 */
	private static $cache = array();

	/**
	 * Is TranslatePress active?
	 *
	 * @return bool
	 */
	public static function has_translatepress() {
		return class_exists( 'TRP_Translate_Press' ) && function_exists( 'trp_translate' );
	}

	/**
	 * Available languages (code => name).
	 *
	 * @return array<string,string>
	 */
	public static function languages() {
		$langs = array();
		if ( self::has_translatepress() ) {
			$settings = get_option( 'trp_settings', array() );
			$codes    = isset( $settings['publish-languages'] ) && is_array( $settings['publish-languages'] ) ? $settings['publish-languages'] : array();
			$names    = function_exists( 'trp_get_languages' ) ? trp_get_languages() : array();
			foreach ( $codes as $code ) {
				$langs[ $code ] = isset( $names[ $code ] ) ? $names[ $code ] : $code;
			}
		}
		return apply_filters( 'oliprfe_languages', $langs );
	}

	/**
	 * Default language code ('' without a multilingual plugin).
	 *
	 * @return string
	 */
	public static function default_language() {
		if ( self::has_translatepress() ) {
			$settings = get_option( 'trp_settings', array() );
			return isset( $settings['default-language'] ) ? (string) $settings['default-language'] : '';
		}
		return '';
	}

	/**
	 * Does this feed language need translation?
	 *
	 * @param string $language Language code.
	 * @return bool
	 */
	public static function needs_translation( $language ) {
		return '' !== $language && self::has_translatepress() && $language !== self::default_language();
	}

	/**
	 * Translates a string or an HTML fragment.
	 *
	 * @param string $text     Text or HTML in the default language.
	 * @param string $language Target language.
	 * @return string
	 */
	public static function translate( $text, $language ) {
		$text = (string) $text;
		if ( '' === trim( $text ) || ! self::needs_translation( $language ) ) {
			return $text;
		}
		$key = $language . '|' . md5( $text );
		if ( isset( self::$cache[ $key ] ) ) {
			return self::$cache[ $key ];
		}
		$translated = trp_translate( $text, $language, false );
		$translated = is_string( $translated ) && '' !== trim( $translated ) ? $translated : $text;
		$translated = apply_filters( 'oliprfe_translate', $translated, $text, $language );
		if ( count( self::$cache ) > 5000 ) {
			self::$cache = array();
		}
		self::$cache[ $key ] = $translated;
		return $translated;
	}

	/**
	 * Translates a product description: rendered like the front end (wpautop, texturize), translated, then returned as HTML.
	 *
	 * @param string $html     Raw description.
	 * @param string $language Target language.
	 * @return string
	 */
	public static function translate_content( $html, $language ) {
		if ( ! self::needs_translation( $language ) ) {
			return (string) $html;
		}
		return self::translate( self::render_content( $html ), $language );
	}

	/**
	 * Post content rendered like the front end (the form TranslatePress stores translations for).
	 *
	 * @param string $html Raw post content.
	 * @return string
	 */
	public static function render_content( $html ) {
		$rendered = wptexturize( wpautop( strip_shortcodes( (string) $html ) ) );
		return convert_chars( convert_smilies( $rendered ) );
	}

	/**
	 * URL in the feed language.
	 *
	 * @param string $url      URL in the default language.
	 * @param string $language Target language.
	 * @return string
	 */
	public static function url( $url, $language ) {
		if ( ! self::needs_translation( $language ) ) {
			return $url;
		}
		$trp       = \TRP_Translate_Press::get_trp_instance();
		$converter = $trp ? $trp->get_component( 'url_converter' ) : null;
		if ( $converter && method_exists( $converter, 'get_url_for_language' ) ) {
			$converted = $converter->get_url_for_language( $language, $url, '' );
			if ( is_string( $converted ) && '' !== $converted ) {
				$url = $converted;
			}
		}
		return (string) apply_filters( 'oliprfe_language_url', $url, $language );
	}
}
