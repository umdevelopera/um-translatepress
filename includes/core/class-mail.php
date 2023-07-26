<?php
/**
 * Translate email templates.
 *
 * @package um_ext\um_translatepress\core
 */

namespace um_ext\um_translatepress\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translate email templates.
 *
 * @package um_ext\um_translatepress\core
 */
class Mail {


	/**
	 * Class Mail constructor.
	 */
	public function __construct() {

		// Email table.
		add_filter( 'um_email_templates_columns', array( &$this, 'email_table_columns' ), 10, 1 );
		add_filter( 'um_email_notifications', array( &$this, 'email_table_items' ), 10, 1 );

		// Email settings.
		add_filter( 'um_admin_settings_email_section_fields', array( &$this, 'admin_settings_email_section_fields' ), 10, 2 );
		add_filter( 'um_email_send_subject', array( &$this, 'localize_email_subject' ), 10, 2 );

		// Email template file.
		add_filter( 'um_change_email_template_file', array( &$this, 'change_email_template_file' ), 10, 1 );
		add_filter( 'um_change_settings_before_save', array( &$this, 'create_email_template_file' ), 8, 1 );
		add_filter( 'um_locate_email_template', array( &$this, 'locate_email_template' ), 10, 2 );
	}


	/**
	 * Adding locale suffix to the "Subject Line" field.
	 * Example: change 'welcome_email_sub' to 'welcome_email_sub_de_DE'
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $section_fields The email template fields.
	 * @param  string $email_key      The email template slug.
	 * @return array
	 */
	public function admin_settings_email_section_fields( $section_fields, $email_key ) {
		$locale        = UM()->Translatepress()->is_default() ? '' : '_' . UM()->Translatepress()->get_current( 'locale' );
		$value         = UM()->options()->get( $email_key . '_sub' . $locale );
		$value_default = UM()->options()->get( $email_key . '_sub' );

		$section_fields[2]['id']    = $email_key . '_sub' . $locale;
		$section_fields[2]['value'] = empty( $value ) ? $value_default : $value;

		return $section_fields;
	}


	/**
	 * Change email template for searching in the theme folder.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $template The email template slug.
	 * @return string
	 */
	public function change_email_template_file( $template ) {
		if ( ! UM()->Translatepress()->is_default() ) {
			$template = UM()->Translatepress()->get_current( 'locale' ) . '/' . $template;
		}
		return $template;
	}


	/**
	 * Create email template file in the theme folder.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $settings Input data.
	 * @return array
	 */
	public function create_email_template_file( $settings ) {
		if ( isset( $settings['um_email_template'] ) ) {
			$template      = $settings['um_email_template'];
			$template_path = UM()->mail()->get_template_file( 'theme', $template );

			if ( ! file_exists( $template_path ) ) {
				$template_dir = dirname( $template_path );

				if ( wp_mkdir_p( $template_dir ) ) {
					file_put_contents( $template_path, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				}
			}
		}
		return $settings;
	}


	/**
	 * Add header for the column 'translations' in the Email table.
	 *
	 * @since 1.0.0
	 *
	 * @global object $polylang The Translatepress instance.
	 *
	 * @param  array $columns The Email table headers.
	 * @return array
	 */
	public function email_table_columns( $columns ) {
		$languages = UM()->Translatepress()->get_languages_list();

		if ( count( $languages ) > 0 ) {
			$language_names = UM()->Translatepress()->get_component( 'languages' )->get_language_names( $languages );

			$flags_column = '';
			foreach ( $languages as $language ) {
				$flag          = UM()->Translatepress()->get_component( 'language_switcher' )->add_flag( $language, $language_names[ $language ] );
				$flags_column .= '<span class="um-flag" style="margin:2px">' . $flag . '</span>';
			}

			$new_columns = array();
			foreach ( $columns as $column_key => $column_content ) {
				$new_columns[ $column_key ] = $column_content;
				if ( 'email' === $column_key && ! isset( $new_columns['tp_translations'] ) ) {
					$new_columns['tp_translations'] = $flags_column;
				}
			}

			$columns = $new_columns;
		}

		return $columns;
	}


	/**
	 * Add cell for the column 'translations' in the Email table.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $email_notifications Email templates data.
	 * @return string
	 */
	public function email_table_items( $email_notifications ) {
		$languages = UM()->Translatepress()->get_languages_list();

		foreach ( $email_notifications as &$email_notification ) {
			$email_notification['tp_translations'] = '';
			foreach ( $languages as $language ) {
				$email_notification['tp_translations'] .= $this->email_table_cell_tp_translations( $email_notification['key'], $language );
			}
		}

		return $email_notifications;
	}


	/**
	 * Get content for the cell of the column 'translations' in the Email table.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $template The email template slug.
	 * @param  string $code     Slug or locale of the queried language.
	 * @return string
	 */
	public function email_table_cell_tp_translations( $template, $code ) {
		$default = UM()->Translatepress()->get_default( 'locale' );
		$lang    = $code === $default ? '' : trailingslashit( $code );

		// theme location.
		$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' ) . $lang . $template . '.php';

		// plugin location for default language.
		if ( empty( $lang ) && ! file_exists( $template_path ) ) {
			$template_path = UM()->mail()->get_template_file( 'plugin', $template );
		}

		$slugs = UM()->Translatepress()->get_component( 'settings' )->get_setting( 'url-slugs' );
		$link  = add_query_arg(
			array(
				'email' => $template,
				'lang'  => $slugs[ $code ],
			)
		);

		$language_names = UM()->Translatepress()->get_component( 'languages' )->get_language_names( array( $code ) );
		$language_name  = $language_names[ $code ];

		if ( file_exists( $template_path ) ) {

			// translators: %s - language name.
			$hint      = sprintf( __( 'Edit the translation in %s', 'um-translatepress' ), $language_name );
			$icon_html = sprintf(
				'<a href="%1$s" title="%2$s"><span class="screen-reader-text">%3$s</span><span class="dashicons dashicons-edit"></span></a>',
				esc_url( $link ),
				esc_html( $hint ),
				esc_html( $hint )
			);
		} else {

			// translators: %s - language name.
			$hint      = sprintf( __( 'Add a translation in %s', 'um-translatepress' ), $language_name );
			$icon_html = sprintf(
				'<a href="%1$s" title="%2$s"><span class="screen-reader-text">%3$s</span><span class="dashicons dashicons-plus"></span></a>',
				esc_url( $link ),
				esc_attr( $hint ),
				esc_html( $hint )
			);
		}

		return $icon_html;
	}


	/**
	 * Replace email Subject with translated value on email send.
	 * Example: change 'welcome_email_sub' to 'welcome_email_sub_de_DE'
	 *
	 * @since 1.0.0
	 *
	 * @param  string $subject  Default subject.
	 * @param  string $template The email template slug.
	 * @return string
	 */
	public function localize_email_subject( $subject, $template ) {
		$locale        = UM()->Translatepress()->is_default() ? '' : '_' . UM()->Translatepress()->get_current( 'locale' );
		$value         = UM()->options()->get( $template . '_sub' . $locale );
		$value_default = UM()->options()->get( $template . '_sub' );
		return empty( $value ) ? $value_default : $value;
	}


	/**
	 * Change email template path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $template      The email template path.
	 * @param  string $template_name The email template slug.
	 * @return string
	 */
	public function locate_email_template( $template, $template_name ) {
		$blog_id = is_multisite() ? trailingslashit( get_current_blog_id() ) : '';
		$locale  = UM()->Translatepress()->is_default() ? '' : trailingslashit( UM()->Translatepress()->get_current( 'locale' ) );

		// check if there is a template in the theme folder.
		$template = locate_template(
			array(
				trailingslashit( 'ultimate-member/email' ) . $blog_id . $locale . $template_name . '.php',
				trailingslashit( 'ultimate-member/email' ) . $blog_id . $template_name . '.php',
				trailingslashit( 'ultimate-member/email' ) . $template_name . '.php',
			)
		);

		// if there isn't template at theme folder get template file from plugin dir.
		if ( ! $template ) {
			$path     = empty( UM()->mail()->path_by_slug[ $template_name ] ) ? um_path . 'templates/email' : UM()->mail()->path_by_slug[ $template_name ];
			$template = trailingslashit( $path ) . $template_name . '.php';
		}

		return wp_normalize_path( $template );
	}

}
