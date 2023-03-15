<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class AIOWPSecurity_User_Registration_Menu extends AIOWPSecurity_Admin_Menu {

	/**
	 * User registration menu slug
	 *
	 * @var string
	 */
	protected $menu_page_slug = AIOWPSEC_USER_REGISTRATION_MENU_SLUG;
	
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
		'manual-approval' => 'render_manual_approval',
		'registration-captcha' => 'render_registration_captcha',
		'registration-honeypot' => 'render_registration_honeypot',
	);
	
	/**
	* Class constructor
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
			'manual-approval' => __('Manual approval', 'all-in-one-wp-security-and-firewall'),
			'registration-captcha' => __('Registration CAPTCHA', 'all-in-one-wp-security-and-firewall'),
			'registration-honeypot' => __('Registration honeypot', 'all-in-one-wp-security-and-firewall'),
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
		echo '<h2>'.__('User registration','all-in-one-wp-security-and-firewall').'</h2>'; // Interface title
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
	 * Renders the submenu's manual approval tab
	 *
	 * @return Void
	 */
	private function render_manual_approval() {
		global $aio_wp_security, $aiowps_feature_mgr;

		include_once 'wp-security-list-registered-users.php'; // For rendering the AIOWPSecurity_List_Table
		$user_list = new AIOWPSecurity_List_Registered_Users();

		if (isset($_POST['aiowps_save_user_registration_settings'])) { // Do form submission tasks
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-user-registration-settings-nonce')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on save user registration settings!",4);
				die("Nonce check failed on save user registration settings!");
			}

			// Save settings
			$aio_wp_security->configs->set_value('aiowps_enable_manual_registration_approval', isset($_POST["aiowps_enable_manual_registration_approval"]) ? '1' : '');

			// Commit the config settings
			$aio_wp_security->configs->save_config();
			
			// Recalculate points after the feature status/options have been altered
			$aiowps_feature_mgr->check_feature_status_and_recalculate_points();

			$this->show_msg_updated(__('Settings were successfully saved', 'all-in-one-wp-security-and-firewall'));
		}
		
		if (isset($_REQUEST['action'])) { // Do list table form row action tasks
			if ($_REQUEST['action'] == 'approve_acct') { // Approve link was clicked for a row in list table
				$nonce = isset($_REQUEST['aiowps_nonce']) ? $_REQUEST['aiowps_nonce'] : '';
				if (!isset($nonce) ||!wp_verify_nonce($nonce, 'approve_user_acct')) {
					$aio_wp_security->debug_logger->log_debug("Nonce check failed for approve registered user account operation.", 4);
					die(__('Nonce check failed for approve registered user account operation.','all-in-one-wp-security-and-firewall'));
				}
				$user_list->approve_selected_accounts(strip_tags($_REQUEST['user_id']));
			}
			
			if ($_REQUEST['action'] == 'delete_acct') { // Delete link was clicked for a row in list table
				$nonce = isset($_REQUEST['aiowps_nonce']) ? $_REQUEST['aiowps_nonce'] : '';
				if (!wp_verify_nonce($nonce, 'delete_user_acct')) {
					$aio_wp_security->debug_logger->log_debug("Nonce check failed for delete registered user account operation.", 4);
					die(__('Nonce check failed for delete registered user account operation.','all-in-one-wp-security-and-firewall'));
				}
				$user_list->delete_selected_accounts(strip_tags($_REQUEST['user_id']));
			}

			if ($_REQUEST['action'] == 'block_ip') { // Block IP link was clicked for a row in list table
				$nonce = isset($_REQUEST['aiowps_nonce']) ? $_REQUEST['aiowps_nonce'] : '';
				if (!isset($nonce) || !wp_verify_nonce($nonce, 'block_ip')) {
					$aio_wp_security->debug_logger->log_debug("Nonce check failed for block IP operation of registered user!", 4);
					die(__('Nonce check failed for block IP operation of registered user!','all-in-one-wp-security-and-firewall'));
				}
				$user_list->block_selected_ips(strip_tags($_REQUEST['ip_address']));
			}
		}

		$aio_wp_security->include_template('wp-admin/user-registration/manual-approval.php', false, array('user_list' => $user_list, 'aiowps_feature_mgr' => $aiowps_feature_mgr));
	}
	
	/**
	 * Renders the submenu's registration captcha tab
	 *
	 * @return Void
	 */
	private function render_registration_captcha() {
		global $aio_wp_security, $aiowps_feature_mgr;
		
		if (isset($_POST['aiowpsec_save_registration_captcha_settings'])) { // Do form submission tasks
			$error = '';
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-registration-captcha-settings-nonce')) {
				$aio_wp_security->debug_logger->log_debug('Nonce check failed on registration CAPTCHA settings save.', 4);
				die('Nonce check failed on registration CAPTCHA settings save.');
			}

			// Save all the form values to the options
			$random_20_digit_string = AIOWPSecurity_Utility::generate_alpha_numeric_random_string(20); // Generate random 20 char string for use during CAPTCHA encode/decode
			$aio_wp_security->configs->set_value('aiowps_captcha_secret_key', $random_20_digit_string);
			$aio_wp_security->configs->set_value('aiowps_enable_registration_page_captcha',isset($_POST["aiowps_enable_registration_page_captcha"]) ? '1' : '');
			$aio_wp_security->configs->save_config();
			
			// Recalculate points after the feature status/options have been altered
			$aiowps_feature_mgr->check_feature_status_and_recalculate_points();
			
			$this->show_msg_settings_updated();
		}
		
		$aio_wp_security->include_template('wp-admin/user-registration/registration-captcha.php', false, array('aiowps_feature_mgr' => $aiowps_feature_mgr));
	}
 
	private function render_registration_honeypot() {
		global $aio_wp_security, $aiowps_feature_mgr;
		
		if (isset($_POST['aiowpsec_save_registration_honeypot_settings'])) { // Do form submission tasks
			$error = '';
			$nonce = $_REQUEST['_wpnonce'];
			if (!wp_verify_nonce($nonce, 'aiowpsec-registration-honeypot-settings-nonce')) {
				$aio_wp_security->debug_logger->log_debug("Nonce check failed on registration honeypot settings save!", 4);
				die("Nonce check failed on registration honeypot settings save!");
			}

			// Save all the form values to the options
			$aio_wp_security->configs->set_value('aiowps_enable_registration_honeypot', isset($_POST["aiowps_enable_registration_honeypot"]) ? '1' : '');
			$aio_wp_security->configs->save_config();
			
			// Recalculate points after the feature status/options have been altered
			$aiowps_feature_mgr->check_feature_status_and_recalculate_points();
			
			$this->show_msg_settings_updated();
		}

		$aio_wp_security->include_template('wp-admin/user-registration/registration-honeypot.php', false, array('aiowps_feature_mgr' => $aiowps_feature_mgr));
	}
} //end class
