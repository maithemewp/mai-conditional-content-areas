<?php

/**
 * Plugin Name:     Mai Conditional Content Areas
 * Plugin URI:      https://bizbudding.com/mai-theme/plugins/mai-conditional-content-areas/
 * Description:     Display content, calls to action, ads, etc. on posts, pages, and custom post types conditionally by category, tag, taxonomy, entry title, and more.
 * Version:         0.1.0
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
			define( 'MAI_CCA_VERSION', '0.4.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_CCA_PLUGIN_DIR' ) ) {
			define( 'MAI_CCA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Classes Path.
		// if ( ! defined( 'MAI_CCA_CLASSES_DIR' ) ) {
		// 	define( 'MAI_CCA_CLASSES_DIR', MAI_CCA_PLUGIN_DIR . 'classes/' );
		// }

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
		// foreach ( glob( MAI_CCA_CLASSES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {
		add_action( 'admin_init',              [ $this, 'updater' ] );
		add_action( 'init',                    [ $this, 'register_content_types' ] );
		add_filter( 'register_post_type_args', [ $this, 'post_type_args' ], 10, 2 );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
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
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/maithemewp/mai-conditional-content-areas/', __FILE__, 'mai-custom-content-areas' );

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
	 * Register content types.
	 *
	 * @return  void
	 */
	public function register_content_types() {

		/***********************
		 *  Custom Taxonomies  *
		 ***********************/

		register_taxonomy( 'mai_cca_display', [ 'mai_template_part' ], [
			'hierarchical' => false,
			'labels'       => [
				'name'                       => _x( 'Content Area Display', 'Content Area Display General Name', 'mai-custom-content-areas' ),
				'singular_name'              => _x( 'Content Area Display', 'Content Area Display Singular Name', 'mai-custom-content-areas' ),
				'menu_name'                  => __( 'Content Area Display', 'mai-custom-content-areas' ),
				'all_items'                  => __( 'All Items', 'mai-custom-content-areas' ),
				'parent_item'                => __( 'Parent Item', 'mai-custom-content-areas' ),
				'parent_item_colon'          => __( 'Parent Item:', 'mai-custom-content-areas' ),
				'new_item_name'              => __( 'New Item Name', 'mai-custom-content-areas' ),
				'add_new_item'               => __( 'Add New Item', 'mai-custom-content-areas' ),
				'edit_item'                  => __( 'Edit Item', 'mai-custom-content-areas' ),
				'update_item'                => __( 'Update Item', 'mai-custom-content-areas' ),
				'view_item'                  => __( 'View Item', 'mai-custom-content-areas' ),
				'separate_items_with_commas' => __( 'Separate items with commas', 'mai-custom-content-areas' ),
				'add_or_remove_items'        => __( 'Add or remove items', 'mai-custom-content-areas' ),
				'choose_from_most_used'      => __( 'Choose from the most used', 'mai-custom-content-areas' ),
				'popular_items'              => __( 'Popular Items', 'mai-custom-content-areas' ),
				'search_items'               => __( 'Search Items', 'mai-custom-content-areas' ),
				'not_found'                  => __( 'Not Found', 'mai-custom-content-areas' ),
			],
			'meta_box_cb'       => true, // Hides metabox.
			'public'            => false,
			'show_admin_column' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
			'show_ui'           => false,
		] );
	}

	/**
	 * Allow adding new content areas.
	 *
	 * @return array
	 */
	function post_type_args( $args, $post_type ) {
		if ( 'mai_template_part' !== $post_type ) {
			return $args;
		}
		unset( $args['capabilities']['create_posts'] );
		return $args;
	}

	/**
	 * Plugin activation.
	 *
	 * @return  void
	 */
	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
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
