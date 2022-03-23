<?php

/**
 * Plugin Name:     Mai Custom Content Areas
 * Plugin URI:      https://bizbudding.com/mai-theme/plugins/mai-custom-content-areas/
 * Description:     Display content, calls to action, ads, etc. on posts, pages, and custom post types conditionally by category, tag, taxonomy, entry title, and more.
 * Version:         1.2.1
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main Mai_CCA_Plugin Class.
 *
 * @since 0.1.0
 */
final class Mai_CCA_Plugin {

	/**
	 * @var   Mai_CCA_Plugin The one true Mai_CCA_Plugin
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_CCA_Plugin Instance.
	 *
	 * Insures that only one instance of Mai_CCA_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_CCA_Plugin::setup_constants() Setup the constants needed.
	 * @uses    Mai_CCA_Plugin::includes() Include the required files.
	 * @uses    Mai_CCA_Plugin::hooks() Activate, deactivate, etc.
	 * @see     Mai_CCA_Plugin()
	 * @return  object | Mai_CCA_Plugin The one true Mai_CCA_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_CCA_Plugin;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-custom-content-areas' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-custom-content-areas' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'MAI_CCA_VERSION' ) ) {
			define( 'MAI_CCA_VERSION', '1.2.1' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_CCA_PLUGIN_DIR' ) ) {
			define( 'MAI_CCA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Classes Path.
		if ( ! defined( 'MAI_CCA_CLASSES_DIR' ) ) {
			define( 'MAI_CCA_CLASSES_DIR', MAI_CCA_PLUGIN_DIR . 'classes/' );
		}

		// Plugin Includes Path.
		if ( ! defined( 'MAI_CCA_INCLUDES_DIR' ) ) {
			define( 'MAI_CCA_INCLUDES_DIR', MAI_CCA_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_CCA_PLUGIN_URL' ) ) {
			define( 'MAI_CCA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'MAI_CCA_PLUGIN_FILE' ) ) {
			define( 'MAI_CCA_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'MAI_CCA_BASENAME' ) ) {
			define( 'MAI_CCA_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Includes.
		foreach ( glob( MAI_CCA_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
		// Classes.
		foreach ( glob( MAI_CCA_CLASSES_DIR . '*.php' ) as $file ) { include $file; }
		// Blocks.
		include_once MAI_CCA_PLUGIN_DIR . 'src/index.php';
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {
		add_action( 'admin_init',              [ $this, 'updater' ] );
		// add_action( 'init',                    [ $this, 'register_block' ] );
		add_filter( 'register_post_type_args', [ $this, 'post_type_args' ], 10, 2 );
		add_action( 'plugins_loaded',          [ $this, 'run' ] );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if current user cannot manage plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-custom-content-areas/', __FILE__, 'mai-custom-content-areas' );

		// Maybe set github api token.
		if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		}

		// Add icons for Dashboard > Updates screen.
		if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
			$updater->addResultFilter(
				function ( $info ) use ( $icons ) {
					$info->icons = $icons;
					return $info;
				}
			);
		}
	}

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 * Behind the scenes, it registers also all assets so they can be enqueued
	 * through the block editor in the corresponding context.
	 *
	 * @since TBD
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 *
	 * @return void
	 */
	public function register_block() {
		// register_block_type( __DIR__ . '/build' );
		register_block_type(
			plugin_dir_path( __FILE__ ) . 'build',
			array(
				'render_callback' => [ $this, 'render_block' ],
			)
		);

		// wp_register_script(
		// 	'mai-custom-content-area-editor',
		// 	plugins_url( 'build/block-test.js', __FILE__ ),
		// 	$asset_file['dependencies'],
		// 	$asset_file['version']
		// );

		// automatically load dependencies and version.
		// $asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php');

		// wp_register_script(
		// 	'mai-custom-content-area',
		// 	plugins_url( 'build/block.js', __FILE__ ),
		// 	$asset_file['dependencies'],
		// 	$asset_file['version']
		// );

		// register_block_type( 'mai/custom-content-area',
		// 	[
		// 		'api_version'     => 2,
		// 		'editor_script'   => 'mai-custom-content-area-editor',
		// 		// 'editor_style'    => 'mai-custom-content-area-editor',
		// 		// 'style'           => 'mai-custom-content-area',
		// 		'render_callback' => [ $this, 'render_block' ],
		// 		// 'attributes'      => [
		// 			// 'cca' => [
		// 				// 'type'    => 'number',
		// 				// 'default' => 0,
		// 			// ],
		// 		// ]
		// 	]
		// );
	}

	/**
	 * This function is called when the block is being rendered on the front end of the site
	 *
	 * @param array    $attributes     The array of attributes for this block.
	 * @param string   $content        Rendered block output. ie. <InnerBlocks.Content />.
	 * @param WP_Block $block_instance The instance of the WP_Block class that represents the block being rendered.
	 */
	function render_block( $attributes, $content, $block_instance ) {
		$html = sprintf( '<p %s>', get_block_wrapper_attributes() );
			if ( isset( $attributes['message'] ) ) {
				$html .= wp_kses_post ( $attributes['message'] );
			}
		$html .= '</p>';

		return $html;
	}

	/**
	 * Allow adding new content areas.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function post_type_args( $args, $post_type ) {
		if ( 'mai_template_part' !== $post_type ) {
			return $args;
		}
		unset( $args['capabilities']['create_posts'] );
		return $args;
	}

	/**
	 * Runs plugin if Mai Engine is active.
	 *
	 * @return Mai_CCA_Block
	 */
	public function run() {
		if ( ! class_exists( 'Mai_Engine' ) ) {
			return;
		}

		new Mai_CCA_Block;
	}
}

/**
 * The main function for that returns Mai_CCA_Plugin
 *
 * The main function responsible for returning the one true Mai_CCA_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_CCA_Plugin(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_CCA_Plugin The one true Mai_CCA_Plugin Instance.
 */
function maicca_plugin() {
	return Mai_CCA_Plugin::instance();
}

// Get Mai_CCA_Plugin Running.
maicca_plugin();
