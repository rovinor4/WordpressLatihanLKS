<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPSecurity_Maintenance_Menu extends AIOWPSecurity_Admin_Menu {
	
	/**
	 * Maintenance menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_MAINTENANCE_MENU_SLUG;

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
		'visitor-lockout' => 'render_visitor_lockout',
	);

	/**
	 * Construct adds menu for maintenance
	 */
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
			'visitor-lockout' => __('Visitor lockout', 'all-in-one-wp-security-and-firewall'),
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
		echo '<h2>'.__('Maintenance','all-in-one-wp-security-and-firewall').'</h2>'; // Interface title
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
	 * Renders the submenu's visitor lockout tab
	 *
	 * @return void
	 */
	private function render_visitor_lockout() {
		global $aio_wp_security;
		$maint_msg = '';
		if (isset($_POST['aiowpsec_save_site_lockout'])) {
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-site-lockout')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on site lockout feature settings save!",4);
				die("Nonce check failed on site lockout feature settings save!");
			}

			// Save settings
			$aio_wp_security->configs->set_value('aiowps_site_lockout', isset($_POST["aiowps_site_lockout"]) ? '1' : '');
			$maint_msg = htmlentities(stripslashes($_POST['aiowps_site_lockout_msg']), ENT_COMPAT, "UTF-8");
			$aio_wp_security->configs->set_value('aiowps_site_lockout_msg',$maint_msg); // Text area/msg box
			$aio_wp_security->configs->save_config();

			$this->show_msg_updated(__('Site lockout feature settings saved!', 'all-in-one-wp-security-and-firewall'));

			do_action('aiowps_site_lockout_settings_saved'); // Trigger action hook.

		}

		$aio_wp_security->include_template('wp-admin/maintenance/visitor-lockout.php', false, array());
	}
} //end class