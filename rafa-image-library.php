<?php namespace RafaImageLibrary;
/*
Plugin Name: RAFA Image Library
Description: Creates a downloadable image library
Version:     1.0.0
Author:      Rafa Digital
*/

class RafaImageLibrary {

	static $storage_dir;
	static $table_name;

	function __construct() {
		/**
		 * Setup
		 */

		register_activation_hook(__FILE__, array($this, 'activate_plugin'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));

		add_action('init', array($this, 'setup_plugin'));

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		add_filter('manage_edit-rafa-image-library_columns', array($this, 'eil_columns'));
		add_action('manage_rafa-image-library_posts_custom_column', array($this, 'eil_column_content'), 10, 2);

		add_image_size('eil-thumbnail', 268, 300, true);

		/**
		 * Hooks
		 */

		/* Frontend */
		add_action('eil_display_recent', array(__NAMESPACE__ . '\Display', 'display_recent'), 10, 2);
		add_action('eil_display_featured', array(__NAMESPACE__ . '\Display', 'display_featured'), 10, 2);
		add_action('eil_display_category', array(__NAMESPACE__ . '\Display', 'display_category'), 10, 4);
		add_action('eil_display_cart', array(__NAMESPACE__ . '\Display', 'display_cart'));
		add_action('eil_display_search_results', array(__NAMESPACE__ . '\Display', 'display_search_results'), 10, 4);
		add_action('eil_display_item', array(__NAMESPACE__ . '\Display', 'display_item'));
		add_action('eil_display_errors', array(__NAMESPACE__ . '\Display', 'display_errors'));
		add_action('eil_get_categories', array(__NAMESPACE__ . '\Category', 'get_categories'));

		add_action('init', array(__NAMESPACE__ . '\Display', 'load_more'));
		add_action('init', array(__NAMESPACE__ . '\Download', 'build_download'));
		add_action('template_redirect', array(__NAMESPACE__ . '\Download', 'download_file'));

		/* Admin */
		add_action('admin_init', array(__NAMESPACE__ . '\LibraryItem', 'toggle_featured'));
		add_action('admin_init', array($this, 'check_dependencies'));
	}

	function activate_plugin() {
		Db::install();
		Cron::setup_cron();
	}

	function deactivate_plugin() {
		Cron::delete_cron();
	}

	function setup_plugin() {
		global $wpdb;

		self::$storage_dir = plugin_dir_path(__FILE__) . 'storage/';
		self::$table_name = $wpdb->prefix . 'eil_downloads';

		include('includes/post_types_tax.php');
		include('includes/acf.php');
	}

	function check_dependencies() {
		if(is_admin() && current_user_can('activate_plugins') && !is_plugin_active('advanced-custom-fields/acf.php')) {
			add_action('admin_notices', array($this, 'plugin_notice'));

			deactivate_plugins(plugin_basename(__FILE__));

			if(isset($_GET['activate'])) {
				unset($_GET['activate']);
			}
		}
	}

	function plugin_notice() {
		?>
		<div class="error"><p>rafa Image Library requires Advanced Custom Fields, please activate it before activating this plugin.</p></div>
		<?php
	}

	function eil_columns($columns) {

		$columns['eil-featured'] = __('Featured', 'rafa_image_library');

		return $columns;
	}

	function eil_column_content($column, $post_id) {
		switch($column) {
			case 'eil-featured' :
				$featured = get_post_meta($post_id, 'eil-featured', true);
				$url = wp_nonce_url(admin_url('/?eil_featurepost=' . $post_id), 'eil_featurepost');

				echo "<a href=\"{$url}\">";
				if ($featured === 'yes') {
					echo '<span class="eil-featured dashicons dashicons-star-filled"></span>';
				} else {
					echo '<span class="eil-featured not-featured dashicons dashicons-star-empty"></span>';
				}
				echo '</a>';

				break;
			default :
				break;
		}
	}

	function enqueue_scripts() {
		wp_enqueue_script('eil-js', plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('eil-cart-js', plugin_dir_url(__FILE__) . 'assets/js/modules/cart.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('eil-loadmore-js', plugin_dir_url(__FILE__) . 'assets/js/modules/loadMore.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('eil-downloaditems-js', plugin_dir_url(__FILE__) . 'assets/js/modules/downloadItems.js', array('jquery'), '1.0.0', true);

		if(Helpers::is_download_page()) {
			wp_enqueue_script('eil-download-js', plugin_dir_url(__FILE__) . 'assets/js/download.js', array('jquery'), '1.0.0', true);
		}
	}

}

include 'classes/Db.php';
include 'classes/Settings.php';
include 'classes/Helpers.php';
include 'classes/Rewrite.php';
include 'classes/Query.php';
include 'classes/LibraryItem.php';
include 'classes/Category.php';
include 'classes/Display.php';
include 'classes/Cart.php';
include 'classes/Download.php';
include 'classes/Cron.php';
include 'includes/api.php';
new rafaImageLibrary;
