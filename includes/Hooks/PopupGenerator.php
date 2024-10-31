<?php
namespace PopupBuilderBlock\Hooks;

defined( 'ABSPATH' ) || exit;

use WP_Query;
use WP_HTML_Tag_Processor;

class PopupGenerator {
	/**
	 * class constructor.
	 * private for singleton
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', [$this,'cpt_menu'] );
		add_action('admin_init', [$this,'assign_capabilities']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
		add_action('enqueue_block_editor_assets', array( $this, 'block_editor_assets'));
		add_action( 'enqueue_block_assets', array( $this, 'blocks_scripts' ));
		add_action('wp_footer', array($this, 'render_gutenkit_popup'));
	}

	public function block_editor_assets() {
		if(get_post_type() === 'gutenkit-popup') {
			$editor_asset = include_once POPUP_BUILDER_BLOCK_PLUGIN_DIR . 'build/blocks/popup-builder/index.asset.php';
			// Enqueue the editor script
			wp_enqueue_script(
				'popup-builder-block',
				POPUP_BUILDER_BLOCK_PLUGIN_URL . 'build/blocks/popup-builder/index.js',
				$editor_asset['dependencies'],
				$editor_asset['version'],
				[
					'in_footer' => false,
				]
			);

			// Enqueue the editor style
			wp_enqueue_block_style( "gutenkit-pro/popup-builder", array(
				'handle' => 'popup-builder-block-editor-style',
				'src'    => POPUP_BUILDER_BLOCK_PLUGIN_URL . 'build/blocks/popup-builder/index.css',
				'deps'   => array(),
				'ver'    => $editor_asset['version'],
				'media'  => 'all',
			));
		}
	}

	public function enqueue_admin_scripts() {
		// Get the current screen
		$screen = get_current_screen();
	
		// Check if we are on the edit or add new screen for our custom post type
		if ($screen->post_type == 'gutenkit-popup') {
			// Enqueue the stylesheet
			wp_enqueue_style('popup-builder-block-admin', POPUP_BUILDER_BLOCK_PLUGIN_URL . 'assets/css/admin.css', [], POPUP_BUILDER_BLOCK_PLUGIN_VERSION);
		}
	}

	/**
	 * Assigns popup capabilities to the administrator role.
	 *
	 * This function adds specific capabilities to the administrator role, allowing them to perform
	 * actions related to popups. The capabilities added include publishing, editing, deleting, and
	 * reading popups.
	 *
	 * @return void
	 */
	public function assign_capabilities() {
		$roles = array('administrator');
		foreach ($roles as $the_role) {
			$role = get_role($the_role);

			$role->add_cap('publish_popup');
			$role->add_cap('edit_popup');
			$role->add_cap('delete_popup');
			$role->add_cap('read_private_popup');
			$role->add_cap('edit_popup');
			$role->add_cap('delete_popup');
			$role->add_cap('read_popup');
		}
	}

	/**
	 * Registers the 'gutenkit-popup' custom post type for the GutenKit Popup Builder.
	 *
	 * @since 1.0.0
	 */
	public function cpt_menu() {

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
	 * Enqueues the necessary scripts and styles for the blocks.
	 *  
	 * @since 1.0.0
	 */
	public function blocks_scripts() {
		$args = array(
			'post_type' => 'gutenkit-popup',
			'posts_per_page' => -1,
		);
		$query = new WP_Query($args);

		if ($query->have_posts()) {
			$posts = $query->posts;
			
			foreach($posts as $post) {
				$popupID = $post->ID;
				$x = get_post($popupID);
				do_blocks($x->post_content);
			}
		}
	}

	public function enqueue_frontend_scripts() {
		$fronted_asset = include_once POPUP_BUILDER_BLOCK_PLUGIN_DIR . 'build/blocks/popup-builder/frontend.asset.php';
		
		// Add popup script
		wp_enqueue_script(
			'popup-builder-block',
			POPUP_BUILDER_BLOCK_PLUGIN_URL . 'build/blocks/popup-builder/frontend.js',
			$fronted_asset['dependencies'],
			$fronted_asset['version'],
			[
				'in_footer' => false,
			]
		);

		// Add popup styles
		wp_enqueue_style(
			'popup-builder-block',
			POPUP_BUILDER_BLOCK_PLUGIN_URL . 'build/blocks/popup-builder/style-index.css',
			array(),
			$fronted_asset['version'],
			'all'
		);
	}

	public function match_children_ids($parent_ids, $current_post_id) {
		$values = array_column($parent_ids, 'value');
		$parent_id = wp_get_post_parent_id($current_post_id);
		
		return in_array($parent_id, $values);
	}

	public function match_category_ids($cat_ids, $current_post_id) {
		$post_categories = get_the_category($current_post_id);

		// Extract the 'value' column from the cat_ids array
		$values = array_column($cat_ids, 'value');
		// Check if any 'cat_ID' from the post_categories matches a 'value' in the cat_ids
		$matchFound = false;

		foreach ($post_categories as $term) {
			if (in_array($term->cat_ID, $values)) {
				$matchFound = true;
				break;
			}
		}

		return $matchFound;
	}

	public function match_tag_ids($tag_id, $current_post_id) {
		$post_tags = get_the_tags($current_post_id);
		
		// Extract the 'value' column from the tag_id array
		$values = array_column($tag_id, 'value');
		// Check if any 'term_id' from the post_tags matches a 'value' in the tag_id
		$matchFound = false;

		foreach ($post_tags as $term) {
			if (in_array($term->term_id, $values)) {
				$matchFound = true;
				break;
			}
		}

		return $matchFound;
	}

	/**
	 * Renders the popup content on the frontend.
	 *
	 * This function retrieves the popups that match the display conditions and renders them on the frontend.
	 *
	 * @return void
	 */
	public function render_gutenkit_popup() {
		if(is_admin()) {
			return;
		}

		$args = array(
			'post_type' => 'gutenkit-popup',
			'posts_per_page' => -1,
		);

		$query = new WP_Query($args);
		$current_post_id = get_the_ID();
		$postType = get_post_type($current_post_id);

		$open_trigger = [];
		global $is_popup_opened;

		// check if the query returned any posts
		if ($query->have_posts()) {
			$posts = $query->posts;

			foreach($posts as $post) {
				$popupID = $post->ID;
				$meta_value = get_post_meta($popupID, 'gutenkit_popup_settings', false);
				$displayCondition = $meta_value[0]['displayCondition'];
				$openTrigger = $meta_value[0]['openTrigger'];
				$open_trigger[$popupID] = $openTrigger; //push openTrigger data for future use
				$flag = 0;
				
				if ($openTrigger !== 'none' && !empty($displayCondition)) {
					foreach ($displayCondition as $cond) {
						extract($cond);
						$match = false;
		
						if ($pageType === 'singular') {
							switch ($singular) {
								case 'singular-front-page':
									$match = is_front_page();
									break;
								case 'singular-page':
									$values = !empty($cond['singular-page']) ? array_column($cond['singular-page'], 'value') : [];
									$match = (empty($cond['singular-page']) && is_page()) || in_array($current_post_id, $values);
									break;
								case 'singular-page-child':
									$match = (empty($cond['singular-page-child']) && is_page()) || $this->match_children_ids($cond['singular-page-child'], $current_post_id);
									break;
								case 'singular-page-template':
									$match = ($cond['singular-page-template'] === 'all' && is_page()) || (is_page() && $cond['singular-page-template'] === get_page_template_slug());
									break;
								case 'singular-404':
									$match = is_404();
									break;
								case 'singular-post':
									$values = !empty($cond['singular-post']) ? array_column($cond['singular-post'], 'value') : [];
									$match = (empty($cond['singular-post']) && is_single()) || in_array($current_post_id, $values);
									break;
								case 'singular-post-cat':
									$match = (empty($cond['singular-post-cat']) && is_single()) || (is_single() && $this->match_category_ids($cond['singular-post-cat'], $current_post_id));
									break;
								case 'singular-post-tag':
									$match = (empty($cond['singular-post-tag']) && is_single()) || (is_single() && $this->match_tag_ids($cond['singular-post-tag'], $current_post_id));
									break;
							}
						} elseif ($pageType === 'archive') {
							switch ($archive) {
								case 'archive-all':
									$match = is_archive();
									break;
								case 'archive-category':
									$match = (empty($cond['archive-category']) && is_category()) || (is_category() && $this->match_category_ids($cond['archive-category'], $current_post_id));
									break;
								case 'archive-tag':
									$match = (empty($cond['archive-tag']) && is_tag()) || (is_tag() && $this->match_tag_ids($cond['archive-tag'], $current_post_id));
									break;
								case 'archive-author':
									$match = ($cond['archive-author'] === 'all' && is_author()) || (is_author() && $cond['archive-author'] == get_the_author_meta('ID'));
									break;
								case 'archive-date':
									$match = is_date();
									break;
								case 'archive-search':
									$match = is_search();
									break;
							}
						} elseif ($pageType === 'entire-site' && $postType != 'gutenkit-popup') {
							$match = true;
						}
		
						if ($condition === 'include' && $match) {
							$flag = 1;
						} elseif ($condition === 'exclude' && $match) {
							$flag = 0;
							break;
						}
					}
		
					if ($flag === 1) {
						$this->show_popup_content($popupID);
						$is_popup_opened = true;
					}
				}
			}
		}

				
		$post = get_post();
		if(!empty($post)) {
			// Checking if any link tag contains popup data attribute
			$block_content = new WP_HTML_Tag_Processor($post->post_content);
			
			while ($block_content->next_tag(['data-dynamic-content-url'])) {
				$data_json = $block_content->get_attribute('data-dynamic-content-url');
				if ($data_json !== null) {
					$data = json_decode($data_json, true);
					if(!empty($data['isDynamicContent'])) {
						$content_type = $data['dynamicContentType'];
						
						if(!empty($content_type) && $content_type === 'popup') {
							$popupID = $data['popupID'];
	
							if($open_trigger[$popupID] == 'none') {
								$this->show_popup_content($popupID);
								$is_popup_opened = true;
							}
						}
					}
				}
			}
		}

		if($is_popup_opened === true) {
			// Enqueue the frontend scripts
			$this->enqueue_frontend_scripts();
		}
	}

	/**
	 * Retrieves the content of a popup by its ID.
	 *
	 * This function retrieves the content of a popup by its ID and returns it as a string.
	 *
	 * @param int $post_id The ID of the popup.
	 * @param array $meta_value The meta value of the popup.
	 * @return string The content of the popup.
	 */
	public function show_popup_content($post_id) {
		// Get the post object
		$post = get_post($post_id);
		
		// Check if the post exists and is of the desired post type
		if ($post && $post->post_type == 'gutenkit-popup') {
			// Get the content of the post
			$content = apply_filters('the_content', $post->post_content);

			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
