<?php
/**
 * Init the extension.
 *
 * @package um_ext\um_translatepress\core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The "Ultimate Member - Translatepress" extension initialization.
 *
 * @package um_ext\um_translatepress\core
 */
class UM_Translatepress {


	/**
	 * Class TRP_Translate_Press
	 *
	 * @var \TRP_Translate_Press
	 */
	private $trp = null;


	/**
	 * An instance of the class.
	 *
	 * @var um_translatepress
	 */
	private static $instance;


	/**
	 * Creates an instance of the class.
	 *
	 * @return um_translatepress
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Class um_translatepress constructor.
	 */
	public function __construct() {
		if ( $this->is_active() ) {
			$this->trp = \TRP_Translate_Press::get_trp_instance();

			$this->fields();
			$this->mail();
		}
	}


	/**
	 * Subclass that translates form fields.
	 *
	 * @return um_ext\um_translatepress\core\Fields()
	 */
	public function fields() {
		if ( empty( UM()->classes['um_translatepress_fields'] ) ) {
			UM()->classes['um_translatepress_fields'] = new um_ext\um_translatepress\core\Fields();
		}
		return UM()->classes['um_translatepress_fields'];
	}


	/**
	 * Subclass that translates email templates.
	 *
	 * @return um_ext\um_translatepress\core\Mail()
	 */
	public function mail() {
		if ( empty( UM()->classes['um_translatepress_mail'] ) ) {
			UM()->classes['um_translatepress_mail'] = new um_ext\um_translatepress\core\Mail();
		}
		return UM()->classes['um_translatepress_mail'];
	}


	/**
	 * Returns particular component by name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $component  'loader' | 'settings' | 'translation_render' |
	 *                           'machine_translator' | 'query' | 'language_switcher' |
	 *                           'translation_manager' | 'url_converter' | 'languages'.
	 * @return mixed
	 */
	public function get_component( $component ) {
		return $this->trp->get_component( $component );
	}


	/**
	 * Returns the current language.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $field The language format to return: 'locale' or 'slug'. Defaults to 'slug'.
	 * @return string
	 */
	public function get_current( $field = 'slug' ) {

		if ( isset( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
		}
		if ( empty( $lang ) || 'all' === $lang ) {
			$lang = get_locale();
		}

		if ( 'locale' === $field && 2 === strlen( $lang ) ) {
			$slugs = $this->trp->get_component( 'settings' )->get_setting( 'url-slugs' );
			$lang  = current( array_keys( $slugs, $lang, true ) );
		}

		return 'slug' === $field ? substr( $lang, 0, 2 ) : $lang;
	}


	/**
	 * Returns the default language.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $field The language format to return: 'locale' or 'slug'. Defaults to 'slug'.
	 * @return string
	 */
	public function get_default( $field = 'slug' ) {
		$lang = $this->trp->get_component( 'settings' )->get_setting( 'default-language' );
		return 'slug' === $field ? substr( $lang, 0, 2 ) : $lang;
	}


	/**
	 * Returns the list of available languages.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_languages_list() {
		return $this->trp->get_component( 'settings' )->get_setting( 'publish-languages' );
	}


	/**
	 * Check if Translatepress is active.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_active() {
		return defined( 'TRP_PLUGIN_VERSION' ) && class_exists( '\TRP_Translate_Press' );
	}


	/**
	 * Check if the default language is chosen.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_default() {
		return $this->get_current() === $this->get_default();
	}

}
