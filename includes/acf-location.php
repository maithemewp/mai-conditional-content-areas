<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

add_action( 'acf/init', 'maicca_register_template_parts_acf_location', 8 );
/**
 * Registers custom location rules for ACF metaboxes.
 *
 * @since TBD
 *
 * @return void
 */
function maicca_register_template_parts_acf_location() {
	if ( ! function_exists( 'acf_register_location_type' ) ) {
		return;
	}

	class Mai_Template_Part_ACF_Location extends ACF_Location {

		public function initialize() {
			$this->public      = true;
			$this->category    = 'post';
			$this->object_type = 'post';
			$this->name        = 'maicca_template_part';
			$this->label       = __( 'Mai Content Area', 'mai-custom-content-area' );
		}

		public static function get_operators( $rule ) {
			return [
				'==' => __( 'is', 'mai-custom-content-area' ),
			];
		}

		public function get_values( $rule ) {
			return [
				'config' => __( 'Registered via config.php', 'mai-custom-content-area' ),
				'custom' => __( 'Custom', 'mai-custom-content-area' ),
			];
		}

		public function match( $rule, $screen, $field_group ) {
			if ( ! ( isset( $screen['post_type'] ) && isset( $screen['post_id'] ) ) ) {
				return false;
			}

			if ( 'mai_template_part' !== $screen['post_type'] ) {
				return false;
			}

			$custom = maicca_is_custom_content_area( $screen['post_id'] );

			if ( 'config' === $rule['value'] ) {
				return ! $custom;
			}

			if ( 'custom' === $rule['value'] ) {
				return $custom;
			}

			// Should never hit this fallback. Only accepted rule values are 'config' and 'custom'.
			return false;
		}
	}

	acf_register_location_type( 'Mai_Template_Part_ACF_Location' );
}
