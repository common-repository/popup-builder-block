<?php

/**
 * Plugin Name: Popup Builder Block
 * Description: Powerful Poup Builder block for Gutenberg block editor.
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Plugin URI: https://wpmet.com/plugin/gutenkit/
 * Author: Wpmet
 * Version: 1.0.2
 * Author URI: https://wpmet.com/
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Text Domain: popup-builder-block
 * Domain Path: /languages
 *
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Final class for the Popup Builder Block plugin.
 *
 * @since 1.0.0
 */
final class PopupBuilderBlock {
	/**
	 * The version number of the Popup Builder Block plugin.
	 *
	 * @var string
	 */
	const VERSION = '1.0.2';

	/**
	 * \Gutenkit class constructor.
	 * private for singleton
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		// Plugins helper constants
		$this->helper_constants();

		add_action('admin_notices', array( $this,'gutenkit_missing_notice'));

		// Register CPT
		add_action('init', array( $this,'popup_cpt_menu'));

		// Load after plugin activation
		register_activation_hook( __FILE__, array( $this, 'activated_plugin' ) );

		// Load after plugin deactivation
		register_deactivation_hook( __FILE__, array( $this, 'deactivated_plugin' ) );

		// Remove "View" link from post row actions
		add_filter( 'post_row_actions', array( $this,'remove_popup_view_link'), 10, 2 );

		// Add popup link to the plugin action links
		add_filter('plugin_action_links', array( $this, 'add_popup_link'), 10, 2 );

		// Plugin actions
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	/**
	 * Helper method for plugin constants.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function helper_constants() {
		define('POPUP_BUILDER_BLOCK_PLUGIN_VERSION', self::VERSION);
		define('POPUP_BUILDER_BLOCK_PLUGIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
		define('POPUP_BUILDER_BLOCK_PLUGIN_DIR', trailingslashit(plugin_dir_path(__FILE__)));
		define('POPUP_BUILDER_BLOCK_INC_DIR', POPUP_BUILDER_BLOCK_PLUGIN_DIR . 'includes/');
		define('POPUP_BUILDER_BLOCK_STYLE_DIR', POPUP_BUILDER_BLOCK_PLUGIN_DIR . 'build/styles/');
		define('POPUP_BUILDER_BLOCK_DIR', POPUP_BUILDER_BLOCK_PLUGIN_DIR . 'build/blocks/');
	}

	/**
	 * Registers the Popup Builder custom post type.
	 *
	 * This function registers the Popup Builder custom post type with the necessary labels, arguments, and capabilities.
	 * It also flushes the rewrite rules after registering the post type.
	 *
	 * @since 1.0.0
	 */
	public function popup_cpt_menu() {

		$labels = array(
			'name'          => esc_html__( 'Popup Builder', 'popup-builder-block' ),
			'singular_name' => esc_html__( 'Popup Builder', 'popup-builder-block' ),
			'all_items'     => esc_html__( 'Popup Builder', 'popup-builder-block' ),
			'add_new'       => esc_html__( 'Create New Popup', 'popup-builder-block' ),
			'add_new_item'  => esc_html__( 'Create New Popup', 'popup-builder-block' ),
			'edit_item'     => esc_html__( 'Edit Popup', 'popup-builder-block' ),
			'menu_name'     => esc_html__( 'GutenKit Popup', 'popup-builder-block' ),
			'search_items'  => esc_html__( 'Search Popup', 'popup-builder-block' ),
		);
		$supports = apply_filters( 'gutenkit/cpt/register/supports', [ 'title', 'editor', 'author', 'custom-fields' ] );
	
		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'description'         => 'description',
			'taxonomies'          => [],
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => true,
			'menu_position'       => 101,
			'menu_icon'           => 'dashicons-admin-page',
			'show_in_nav_menus'   => false,
			'show_in_rest'        => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'page',
			'capabilities'        => array(
				'publish_posts'      => 'publish_popup',
				'edit_posts'         => 'edit_popup',
				'delete_posts'       => 'delete_popup',
				'read_private_posts' => 'read_private_popup',
				'edit_post'          => 'edit_popup',
				'delete_post'        => 'delete_popup',
				'read_post'          => 'read_popup',
			),
			'supports'            => $supports,
		);
	
		register_post_type( 'gutenkit-popup', $args );
		flush_rewrite_rules();
	}

	/**
	 * Removes the "view" action link for the "gutenkit-popup" post type.
	 *
	 * @param array $actions The list of action links for the post.
	 * @param WP_Post $post The post object.
	 * @return array The modified list of action links.
	 */
	public function remove_popup_view_link( $actions, $post ) {
		if ( 'gutenkit-popup' === $post->post_type ) {
			unset( $actions['view'] );
		}
		return $actions;
	}

	/**
	 * After activation hook method
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function activated_plugin() {
		$this->moduleStatus('active');
	}

	/**
	 * After deactivation hook method
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function deactivated_plugin() {
		$this->moduleStatus('inactive');
	}

	/**
	 * Module status update method
	 *
	 * @param string $status
	 * @return void
	 * @since 1.0.0
	 */
	public function moduleStatus($status) {
		$modules_list = get_option('gutenkit_modules_list');

		if ( $modules_list ) {
			$new_modules_list = $modules_list;
			$new_modules_list['popup-builder'] = array_merge($new_modules_list['popup-builder'], ['status' => $status]);

			update_option('gutenkit_modules_list', $new_modules_list);
		}
	}

	/**
	 * Add popup link to the plugin action links.
	 *
	 * @param array  $plugin_actions An array of plugin action links.
	 * @param string $plugin_file    Path to the plugin file relative to the plugins directory.
	 * @return array An array of plugin action links.
	 * @since 1.0.0
	 */
	public function add_popup_link($plugin_actions, $plugin_file) {
		$new_actions = array();
		$plugin_slug = 'popup-builder-block';
		$plugin_name = "{$plugin_slug}/{$plugin_slug}.php";

		if ( $plugin_name === $plugin_file ) {
			$menu_link = 'edit.php?post_type=gutenkit-popup';
			$new_actions['met_settings'] = sprintf('<a href="%s">%s</a>', esc_url( $menu_link ), esc_html__('Build Popup', 'popup-builder-block'));
		}
	
		return array_merge( $new_actions, $plugin_actions );
	}
	/**
	 * Checks if GutenKit Blocks is installed and active.
	 * If GutenKit Blocks is not installed, displays an error notice with a link to install it.
	 * If GutenKit Blocks is installed but not active, displays a warning notice with a link to activate it.
	 *
	 * @return void
	 */
	public function gutenkit_missing_notice() {
		if(class_exists('Gutenkit')) return;

		global $wp_filesystem;

		// Include the file.php if not already included
		if (!function_exists('WP_Filesystem')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}

		// Initialize the WP filesystem, false to use the direct method
		WP_Filesystem();

		$plugin_slug = 'gutenkit-blocks-addon';
		$plugin_file_path = 'gutenkit-blocks-addon/gutenkit-blocks-addon.php';

		// Check if GutenKit Blocks is installed
		if ($wp_filesystem->exists(WP_PLUGIN_DIR . '/' . $plugin_file_path)) {
			if (!function_exists('is_plugin_active')) {
				include_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}

			// Check if GutenKit Blocks is active
			if (!is_plugin_active($plugin_file_path)) {
				// GutenKit Blocks is installed but not active, show activate button
				?>
				<div class="notice notice-warning is-dismissible">
					<div>
						<p><?php esc_html_e('Poup Builder Block requires GutenKit Blocks, which is currently NOT RUNNING..', 'popup-builder-block'); ?>
						<p>
							<a href="<?php echo esc_url(wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file_path, 'activate-plugin_' . $plugin_file_path)); ?>"
								class="button button-primary">
								<?php esc_html_e('Activate GutenKit Blocks', 'popup-builder-block'); ?>
							</a>
					</div>
				</div>
				<?php
			}
		} else {
			// GutenKit Blocks is not installed, show error notice
			$nonce_action = 'install-plugin_' . $plugin_slug;
			$nonce = wp_create_nonce($nonce_action);
			?>
			<div class="notice notice-error is-dismissible">
				<div>
					<p><?php esc_html_e('Poup Builder Block requires GutenKit Blocks, which is currently NOT RUNNING.', 'popup-builder-block'); ?>
					<p>
						<a href="<?php echo esc_url(admin_url('update.php?action=install-plugin&plugin=' . $plugin_slug . '&_wpnonce=' . $nonce)); ?>"
							class="button button-primary">
							<?php esc_html_e('Install GutenKit Blocks', 'popup-builder-block'); ?>
						</a>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Plugins loaded method.
	 * loads our others classes and textdomain.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		/**
		 * Loads the plugin text domain for the Poup Builder Block.
		 *
		 * This function is responsible for loading the translation files for the plugin.
		 * It sets the text domain to 'popup-builder-block' and specifies the directory
		 * where the translation files are located.
		 *
		 * @param string $domain   The text domain for the plugin.
		 * @param bool   $network  Whether the plugin is network activated.
		 * @param string $directory The directory where the translation files are located.
		 * @return bool True on success, false on failure.
		 * @since 1.0.0
		 */
		load_plugin_textdomain('popup-builder-block', false, POPUP_BUILDER_BLOCK_PLUGIN_DIR . 'languages/');

		// Include the class files
		require_once POPUP_BUILDER_BLOCK_INC_DIR . 'Config/PostMeta.php';
		require_once POPUP_BUILDER_BLOCK_INC_DIR . 'Hooks/PopupGenerator.php';

		/**
		 * Initializes the Popup Builder Block admin functionality.
		 *
		 * This function creates an instance of the PopupBuilderBlock\Admin\Admin class and initializes the admin functionality for the Popup Builder Block plugin.
		 *
		 * @since 1.0.0
		 */
		new PopupBuilderBlock\Config\PostMeta();
		new PopupBuilderBlock\Hooks\PopupGenerator();
	}
}

/**
 * Kickoff the plugin
 *
 * @since 1.0.0
 * 
 */
new PopupBuilderBlock();