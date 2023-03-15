<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPSecurity_Misc_Options_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * Misc menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_MISC_MENU_SLUG;
	
	/**
	 * Specify all the tabs of this menu
	 *
	 * @var array
	 */
	protected $menu_tabs;

	/**
	 * Specify all the tabs handler methods
	 *
	 * @var array
	 */
	protected $menu_tabs_handler = array(
		'copy-protection' => 'render_copy_protection',
		'frames' => 'render_frames',
		'user-enumeration' => 'render_user_enumeration',
		'wp-rest-api' => 'render_wp_rest_api',
	);

	public function __construct() {
		$this->render_menu_page();
	}

	/**
	 * Populates $menu_tabs array.
	 *
	 * @return Void
	 */
	private function set_menu_tabs() {
		$this->menu_tabs = array(
			'copy-protection' => __('Copy protection', 'all-in-one-wp-security-and-firewall'),
			'frames' => __('Frames', 'all-in-one-wp-security-and-firewall'),
			'user-enumeration' => __('User enumeration', 'all-in-one-wp-security-and-firewall'),
			'wp-rest-api' => __('WP REST API', 'all-in-one-wp-security-and-firewall'),
		);
	}

	/*
	 * Renders our tabs of this menu as nav items
	 */
	private function render_menu_tabs() {
		$current_tab = $this->get_current_tab();

		echo '<h2 class="nav-tab-wrapper">';
		foreach ($this->menu_tabs as $tab_key => $tab_caption) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->menu_page_slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
	}
	
	/*
	 * The menu rendering goes here
	 */
	private function render_menu_page() {
		echo '<div class="wrap">';
		echo '<h2>'.__('Miscellaneous','all-in-one-wp-security-and-firewall').'</h2>';//Interface title
		$this->set_menu_tabs();
		$tab = $this->get_current_tab();
		$this->render_menu_tabs();
		?>
		<div id="poststuff"><div id="post-body">
		<?php 
		// $tab_keys = array_keys($this->menu_tabs);
		call_user_func(array($this, $this->menu_tabs_handler[$tab]));
		?>
		</div></div>
		</div><!-- end of wrap -->
		<?php
	}
	
	/**
	 * Renders the submenu's copy protection tab
	 *
	 * @return Void
	 */
	private function render_copy_protection() {
		global $aio_wp_security;
		$maint_msg = '';
		if (isset($_POST['aiowpsec_save_copy_protection'])) {
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-copy-protection')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on copy protection feature settings save!",4);
				die("Nonce check failed on copy protection feature settings save!");
			}
			
			// Save settings
			$aio_wp_security->configs->set_value('aiowps_copy_protection',isset($_POST["aiowps_copy_protection"])?'1':'');
			$aio_wp_security->configs->save_config();

			$this->show_msg_updated(__('Copy Protection feature settings saved!', 'all-in-one-wp-security-and-firewall'));

		}

		$aio_wp_security->include_template('wp-admin/miscellaneous/copy-protection.php', false, array());
	}
	
	/**
	 * Renders the submenu's render frames tab
	 *
	 * @return Void
	 */
	private function render_frames() {
		global $aio_wp_security;
		$maint_msg = '';
		if (isset($_POST['aiowpsec_save_frame_display_prevent'])) {
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-prevent-display-frame')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on prevent display inside frame feature settings save!",4);
				die("Nonce check failed on prevent display inside frame feature settings save!");
			}
			
			// Save settings
			$aio_wp_security->configs->set_value('aiowps_prevent_site_display_inside_frame',isset($_POST["aiowps_prevent_site_display_inside_frame"])?'1':'');
			$aio_wp_security->configs->save_config();

			$this->show_msg_updated(__('Frame Display Prevention feature settings saved!', 'all-in-one-wp-security-and-firewall'));

		}
		
		$aio_wp_security->include_template('wp-admin/miscellaneous/frames.php', false, array());
	}
	
	/**
	 * Renders the submenu's user enumeration tab
	 *
	 * @return Void
	 */
	private function render_user_enumeration() {
		global $aio_wp_security;
		$maint_msg = '';
		if (isset($_POST['aiowpsec_save_users_enumeration'])) {
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-users-enumeration')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on prevent user enumeration feature settings save!",4);
				die("Nonce check failed on prevent user enumeration feature settings save!");
			}

			// Save settings
			$aio_wp_security->configs->set_value('aiowps_prevent_users_enumeration',isset($_POST["aiowps_prevent_users_enumeration"])?'1':'');
			$aio_wp_security->configs->save_config();

			$this->show_msg_updated(__('User Enumeration Prevention feature settings saved!', 'all-in-one-wp-security-and-firewall'));

		}
		
		$aio_wp_security->include_template('wp-admin/miscellaneous/user-enumeration.php', false, array());
	}

	/**
	 * Renders the submenu's WP REST API tab
	 *
	 * @return Void
	 */
	private function render_wp_rest_api() {
		global $aio_wp_security;
		$maint_msg = '';
		if (isset($_POST['aiowpsec_save_rest_settings'])) {
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-rest-settings')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on REST API security feature settings save!",4);
				die("Nonce check failed on REST API security feature settings save!");
			}

			// Save settings
			$aio_wp_security->configs->set_value('aiowps_disallow_unauthorized_rest_requests',isset($_POST["aiowps_disallow_unauthorized_rest_requests"])?'1':'');
			$aio_wp_security->configs->save_config();

			$this->show_msg_updated(__('WP REST API Security feature settings saved!', 'all-in-one-wp-security-and-firewall'));

		}
		
		$aio_wp_security->include_template('wp-admin/miscellaneous/wp-rest-api.php', false, array());
	}

} //end class
