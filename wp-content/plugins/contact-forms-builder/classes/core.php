<?php
/**
 * This is Plugin Core Class
 * Contains definitions about plugin pages i.e. add new form, edit form, show forms and settings
 *
 * @package WpDevArt Forms
 * @since	1.0
 */
 if ( ! defined( 'ABSPATH' ) ) exit;
 
class wpdevartForms {
	
	// plugin admin page's URL slug	
	private $slug; 
	
	public function __construct() { 
		
		global $wpdb;
		$settings = array();
		
/*		if(file_exists(wpda_form_FILE_INI)) {
			$settings = parse_ini_file(wpda_form_FILE_INI);
		}
*/		
		if( empty($settings) || ( !isset($settings['base_slug']) || empty($settings['base_slug']) ) ) {
			$settings['base_slug'] = "wpdevart-forms";
		}
		
		// slugs & menu
		$this->slug['add_new']	= $settings['base_slug'] . '-add-new';
		$this->slug['edit']	= $settings['base_slug'] . '-edit';
		$this->slug['list']	= $settings['base_slug'] . '-list';
		$this->slug['styling']	= $settings['base_slug'] . '-styling';
		$this->slug['settings']	= $settings['base_slug'] . '-settings';
		$this->slug['submissions']	= $settings['base_slug'] . '-submissions';
		$this->slug['extra_settings']	= $settings['base_slug'] . '-extra-settings';
		
		// Set roles for users who can access this plugin
		$allowed_roles = array('editor', 'administrator');
		
		//	ENQUEUE SCIPTS AND STYLES FOR wpdevart WIDGETS
		add_action( 'admin_enqueue_scripts', array($this, 'wpda_form_enqueue_admin_styles') );
		add_action( 'admin_enqueue_scripts', array($this, 'wpda_form_enqueue_admin_scripts') );
		add_action('admin_init', array($this, 'wpda_form_export_form') );
		add_action('admin_init', array($this, 'wpda_form_export_form_submissions') );
		
		// Add post page button
		add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
		add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
		
		// Ajax hook for mce button
		add_action("wp_ajax_wpdevart_forms_mce_ajax",array($this,"wpdevart_forms_mce_ajax"));

		//	wp_get_current_user() required pluggable.php defined in wp-includes/pluggable.php
		$user_role = wp_get_current_user();
		
		if( array_intersect($allowed_roles, $user_role->roles )){
			
			
			// Plugin version
			if(get_option("wpdevart_forms_plugin_version")) {
				update_option("wpdevart_forms_plugin_version", wpda_form_PLUGIN_VERSION);
			} else {
				add_option("wpdevart_forms_plugin_version", wpda_form_PLUGIN_VERSION);
			}
			
			if( wp_get_theme() == 'wpdevart' || wp_get_theme() == 'wpdevart Child' || wp_get_theme() == 'wpdevart Theme' || wp_get_theme() == 'wpdevart Child Theme' ) {
				//	if wpdevart Theme is active, then we do not add sidebar menu because
				//	wpdevart Theme automatically adds sidebar menu items under wpdevart tab
			} else {
				// add plugin  pages to wordpress admin menu
				add_action('admin_menu', array($this, 'wpda_form_add_menu'));
			}
		}
		$this->wpda_form_add_gutenberg();
	}
	// Admin post/page tinmce buttons function
	public function mce_external_plugins( $plugin_array ) {
		$plugin_array['wpdevart_forms'] = wpda_form_PLUGIN_URI. 'assets/js/mce-button.js';
		return $plugin_array;
	}

/*############  MCE buttons function ################*/	
	
	public function mce_buttons( $buttons ) {
		array_push( $buttons, 'wpdevart_forms');
		return $buttons;
	}
	
/*############  MCE Ajax function ################*/	
	
	public function wpdevart_forms_mce_ajax(){
		if(!current_user_can('edit_posts')){
			exit;
		}
		?>
		<html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title>Wpdevart Form</title>           
            <base target="_self">
            <script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
			<script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
            <script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
			<?php wp_print_scripts('jquery') ?>
        </head>
        <body id="link"   class="forceColors">
        <?php
            global $wpdb;
			global $wpda_form_table;      
			$wpdevart_forms = $wpdb->get_results("SELECT * FROM ".$wpda_form_table['wpdevart_forms']);
 
            ?>
            <table width="100%" class="paramlist admintable" cellspacing="1" style="margin-bottom: 120px;">
                <tbody>
                    <tr>
                        <td style="width: 100px;" class="paramlist_key">
                            <span class="editlinktip">
                                <label style="font-size:14px" id="paramsstandcatid-lbl" for="Category" class="hasTip">Select Form: </label>
                            </span>
                        </td>
                        <td class="paramlist_value" >                        
							<?php
                             if(count($wpdevart_forms) > 0){?>   
                                    <select id="wpdevart_forms_id" style="width: 200px; font-size: 14;">
                                    <?php foreach ($wpdevart_forms as $key => $form ){ ?>
                                            <option value="<?php echo $form->id ?>"<?php if(isset($_GET['id'])) selected(intval($_GET['id']), $form->id ); ?> > 
                                                <?php echo $form->name; ?>
                                            </option>
                                    <?php } ?>
                                    </select>
                            <?php } else {
                                echo '<label>There is not any form created yet. Please create a form from WpDevArt Forms, then a list of forms will be displayed here.</label>';
                            } ?>                       
                        </td>
                    </tr>
                </tbody>
            </table>
       		<div class="mceActionPanel">
                <div style="float: left">
                    <input type="button" id="cancel" name="cancel" value="Cancel" onClick="tinyMCEPopup.close();"/>
                </div>
    
                <div style="float: right">
                    <input type="submit" id="insert" name="insert" value="Insert" onClick="insert_poll();"/>
                    <input type="hidden" name="iden" value="1"/>
                </div>
            </div>
        
    
        	<script type="text/javascript">
				function insert_poll() {					  
					if(jQuery('#wpdevart_forms_id').val()!='0'){
						var tagtext;
						tagtext = '[wpdevart_forms id="' + jQuery('#wpdevart_forms_id').val()+'" ]';
						window.parent.tinyMCE.execCommand('mceInsertContent', false, tagtext);
						tinyMCEPopup.close();
					}
					else{
						tinyMCEPopup.close();
					}
				}    
        	</script>
        	</body>
        </html>
        <?php
        die();

	}
 	// Function that adds menus to left-hand sidebar
	public function wpda_form_add_menu() {
		global $submenu;
		add_menu_page('wpdevart Forms', 'Wpdevart Forms', 'manage_options', $this->slug['list'], array($this, 'wpda_form_list_forms'), wpda_form_PLUGIN_URI .'assets/images/admin-icon.png', 66);
		add_submenu_page(null, 'Add New Form', 'Add New', 'manage_options', $this->slug['add_new'], array($this, 'wpda_form_add_new_form'));
		add_submenu_page(null, 'wpdevart Forms - Edit', 'Edit', 'manage_options', $this->slug['edit'], array($this, 'wpda_form_edit_form'));	
		add_submenu_page(null, 'wpdevart Forms - Styling', 'Styling', 'manage_options', $this->slug['styling'], array($this, 'wpda_form_form_styling'));
		add_submenu_page(null, 'wpdevart Forms - Submissions ', 'Submissions', 'manage_options', $this->slug['submissions'], array($this, 'wpda_form_form_submissions'));
		add_submenu_page(null, 'wpdevart Forms Settings ', 'Settings','manage_options', $this->slug['settings'], array($this, 'wpda_form_forms_settings'));
		add_submenu_page(null, 'wpdevart Forms Extra settings ', 'Extra Settings', 'manage_options', $this->slug['extra_settings'], array($this, 'wpda_form_forms_extra_settings'));
		add_submenu_page($this->slug['list'], 'Featured Plugins', 'Featured Plugins', 'manage_options', 'featured_plugins', array($this, 'featured_plugins'));
		add_submenu_page($this->slug['list'], 'Uninstall', 'Uninstall', 'manage_options', 'wpda_form_uninstall', array($this, 'uninstall_controller'));
		if(isset($submenu[$this->slug['list']]))
			add_submenu_page( $this->slug['list'], "Support or Any Ideas?", "<span style='color:#00ff66' >Support or Any Ideas?</span>", 'manage_options',"wpdevar_contact_form_any_ideas",array($this, 'any_ideas'),156);
		if(isset($submenu[$this->slug['list']]))
			$submenu[$this->slug['list']][3][2]=wpdevart_contactform_support_url;
	}
	
	/*############  Function for the any ideas page ################*/
	
	public function any_ideas() {
		
	}
	// Call back function add new (form page)
	public function wpda_form_add_new_form() {
		require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/add-new-form.php';
	}
	
	// Gutenberg
	public function wpda_form_add_gutenberg() {
		$wpda_form_gutenberg= new wpda_form_gutenberg();
	}
	
	// Call back function edit form (page)
	public function wpda_form_edit_form() {
		require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/edit-form.php';
	}
	
	// Call back function for En-listing forms (page)
	public function wpda_form_list_forms() {
		wpda_form_db_tables();
		require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/list-forms.php';
	}
	
	// Call back function for forms styling (page)
	public function wpda_form_form_styling() {
		require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/form-styling.php';
	}
	
	// Call back function for forms submissions(page), How many times a forms submitted and containing what data
	public function wpda_form_form_submissions(){
		require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/form-submissions.php';
	}
	
	// Call back function for forms setting (page), for later use
	public function wpda_form_forms_settings(){		
		//	later use
		//	echo "Page not found";
		exit;
	}
	
	// Call back function for forms extra settings (page), 
	public function wpda_form_forms_extra_settings() {
		require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/extra-settings.php';
	}
	
	//	Function for importing form from export file
	public function wpda_form_import_form() {
		//pro feature
	}
		
		
	// -------------------------------------------
	//				FORM EXPORT
	//  ------------------------------------------

    /*############  Function for the form export  ################*/	
	
	public function wpda_form_export_form() {
		if(isset($_POST['btn_export_form'])) {
			require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/export-form.php';
		}
	}
	
	//	Exporting forms submissions [ in csv formate ]
	public function wpda_form_export_form_submissions() {
		if(isset($_POST['export_form_record'])) { 
			require_once wpda_form_PLUGIN_DIR . '/wpdevart-inc/export-form-submissions.php';
		}
	}
	
	//	Enqueue backend form pages css
	public function wpda_form_enqueue_admin_styles() { 
		$page = "";
		if(isset($_GET['page'])) {
			
			$page = $_GET['page'];
			
			//	this will load styles to only those pages that are in slug
			if ( ! in_array($page, $this->slug) )
				return;
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'wf-bootstrap', wpda_form_PLUGIN_URI . 'assets/css/bootstrap.min.css');
			wp_enqueue_style( 'wf-font-awesome', wpda_form_PLUGIN_URI . 'assets/css/font-awesome.min.css');
			wp_enqueue_style( 'wf-tabs', wpda_form_PLUGIN_URI . 'assets/css/tabs.min.css');
			wp_enqueue_style( 'wf-sortable', wpda_form_PLUGIN_URI . 'assets/css/sortable.min.css');
			wp_enqueue_style( 'wf-message-effects', wpda_form_PLUGIN_URI . 'assets/css/message-effects.min.css');
			//wp_enqueue_style( 'wf-wpdevart', wpda_form_PLUGIN_URI . 'assets/wpdevart.min.css');
			wp_enqueue_style( 'wf-wpdevart', wpda_form_PLUGIN_URI . 'assets/wpdevart.css');
		}
	}
	
	//	Enqueue backend form pages scripts
	public function wpda_form_enqueue_admin_scripts() 
	{
		$page = "";
		if(isset($_GET['page'])) {
			$page = $_GET['page'];
			
			//	this will load scripts to only those pags that are in slug
			if ( ! in_array($page, $this->slug) )
				return;
			
			wp_enqueue_script('wf-forms-custom', wpda_form_PLUGIN_URI . 'assets/js/wpdevart-forms-custom.min.js', array('jquery', 'wp-color-picker'), '1.0', true );
			wp_enqueue_script('jquery', array(), '1.0', false );
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('wf-bootstrap', wpda_form_PLUGIN_URI . 'assets/js/bootstrap.min.js', array(), '1.0',true);
			wp_enqueue_script('wf-message-effects', wpda_form_PLUGIN_URI . '/assets/js/message-effects.js', array(), '1.0', true );
			wp_enqueue_script('wf-wpdevart', wpda_form_PLUGIN_URI . 'assets/wpdevart.js', array('jquery'), '1.0', true);
			wp_enqueue_script('wf-ajax_custom_script', wpda_form_PLUGIN_URI . 'ajax/wpdevart-ajax-calls.js', array(), 1.0, false);
			wp_enqueue_script('wf-jquery-form', wpda_form_PLUGIN_URI . '/frontend/js/jquery-form-js.min.js', array(), '1.0', false );
		}
	}

    /*############  Function for uninstalling the controller  ################*/	
	
	public function uninstall_controller(){
		if(isset( $_POST['wpdevartForms_uninstall_bad'] )   && wp_verify_nonce( $_POST['wpdevartForms_uninstall_bad'], 'wpdevartForms_uninstall')){
			$this->remove_databese_and_deactivete();
			return;
		}
		$this->display_uninstall_main();
	}

    /*############  Function for removing the database and deactivation  ################*/		
	
	private function remove_databese_and_deactivete(){
		global $wpdb;
		global $wpda_form_table;
		
		/**
		*	remove all attachments of the form
		*/
		// remove attachments of submissions of current form 
			$attachment_fields_ids = $wpdb -> get_results ("SELECT id FROM " .$wpda_form_table['fields'] ." WHERE  fieldtype='file' ");
			if(!empty($attachment_fields_ids))
			{
				foreach($attachment_fields_ids as $row):
					$ids[] = $row->id;
				endforeach;
				
				if(count($ids)>1){
					$arr = implode(',',$ids);
					$attachment_fields_submit_values = $wpdb -> get_results ("SELECT field_value FROM " .$wpda_form_table['submissions'] ." WHERE fk_field_id IN ($arr) AND  field_value IS NOT NULL ");
				} else {
					$id = $ids[0];
					$attachment_fields_submit_values = $wpdb -> get_results ("SELECT field_value FROM " .$wpda_form_table['submissions'] ." WHERE fk_field_id = $id AND  field_value IS NOT NULL ");
				}
				
				 foreach($attachment_fields_submit_values as $row)
				 {
					$result =  $row->field_value; 
					//	get the site URL with domain
					$site_url= get_site_url();
					// 	convert abs path to relative
					
					$rel_path=  str_replace($site_url,'',$result);
					if( file_exists(ABSPATH .$rel_path))
					{
						unlink(ABSPATH .$rel_path);
					}
				 }
			}
		
		//	remove styling of forms 
		if(get_option('wpdevart_forms_style')){
			delete_option('wpdevart_forms_style');
		}
		
		//drop tables
		
		$wpdb->query( "DROP TABLE IF EXISTS " .$wpda_form_table['submissions'] );
		$wpdb->query( "DROP TABLE IF EXISTS " .$wpda_form_table['submit_time'] );
		$wpdb->query( "DROP TABLE IF EXISTS " .$wpda_form_table['subfields'] );
		$wpdb->query( "DROP TABLE IF EXISTS " .$wpda_form_table['fields']);
		$wpdb->query( "DROP TABLE IF EXISTS " .$wpda_form_table['wpdevart_forms'] );
		
		if(get_option("wpdevart_forms_plugin_version")) {
			delete_option("wpdevart_forms_plugin_version");
		}
		
		// remove attachment folder
		$wp_upload_dir = wp_upload_dir();
		$wpdevart_forms_uploads_dir  = wpda_form_uploads_dir();
		
		//	Delete folder of for wpdevart-forms-attachments
		wpda_form_rrmdir($wp_upload_dir['basedir'].'/'.$wpdevart_forms_uploads_dir);		
		?>
		<div id="message" class="updated fade">
		  <p>The following Database Tables successfully deleted:</p>
		  <p><?php echo $wpdb->prefix; ?>wpda_form_fields,</p>
		  <p><?php echo $wpdb->prefix; ?>wpda_form_forms,</p>
		  <p><?php echo $wpdb->prefix; ?>wpda_form_subfields,</p>
           <p><?php echo $wpdb->prefix; ?>wpda_form_submissions,</p>
		  <p><?php echo $wpdb->prefix; ?>wpda_form_submit_time,</p>
		</div>
		<div class="wrap">
		  <h2>Uninstall Form</h2>
		  <p><strong><a href="<?php echo wp_nonce_url('plugins.php?action=deactivate&amp;plugin=contact-forms-builder/wpdevart-form.php', 'deactivate-plugin_contact-forms-builder/wpdevart-form.php'); ?>">Click Here</a> To Finish the Form Uninstallation</strong></p>
		  <input id="task" name="task" type="hidden" value="" />
		</div>
	  <?php	
	}
	
    /*############  Function for the uninstall page  ################*/		
	
	private function display_uninstall_main(){
		global $wpdb;
		?>
        <form method="post" action="admin.php?page=wpda_form_uninstall" style="width:99%;">
			 <?php wp_nonce_field('wpdevartForms_uninstall','wpdevartForms_uninstall_bad'); ?>
              <div class="wrap">
                <span class="uninstall_icon"></span>
                <h2>Uninstall WpDevArt Forms</h2>
                <p>
                  Deactivating the WpDevArt Forms plugin does not remove any data that may have been created. To completely remove this plugin and all created forms data, you can uninstall it here.
                </p>
                <p style="color: #7052fb;font-weight: bold;">
                  <strong>WARNING:</strong>
                  Once uninstalled, this can't be undone. You should use a Database Backup plugin of WordPress to back up all the data first.
                </p>
                <p style="color: #7052fb;font-weight: bold;">
                  <strong>The following Database Tables will be deleted:</strong>
                </p>
                <table class="widefat">
                  <thead>
                    <tr>
                      <th>Database Tables</th>
                    </tr>
                  </thead>
                  <tr>
                    <td valign="top">
                      <ol>
                          <li><?php echo $wpdb->prefix; ?>wpda_form_fields</li>
                          <li><?php echo $wpdb->prefix; ?>wpda_form_forms</li>
                          <li><?php echo $wpdb->prefix; ?>wpda_form_subfields</li>
                          <li><?php echo $wpdb->prefix; ?>wpda_form_submissions</li>
                          <li><?php echo $wpdb->prefix; ?>wpda_form_submit_time</li>
                      </ol>
                    </td>
                  </tr>
                </table>
                <p style="text-align: center;">
                  Do you really want to delete all data?
                </p>
                <p style="text-align: center;">
                  <input type="checkbox" id="check_yes" value="yes" />&nbsp;<label for="check_yes">Yes</label>
                </p>
                <p style="text-align: center;">
                  <input type="submit" value="UNINSTALL" class="button-primary" onclick="if (check_yes.checked) { 
                                                                                            if (confirm('You are About to Uninstall Form.\nThis Action Is Not Reversible.')) {
                                                                                               
                                                                                            } else {
                                                                                                return false;
                                                                                            }
                                                                                          }
                                                                                          else {
                                                                                            return false;
                                                                                          }" />
                </p>
              </div>
            </form>
          <?php
    
		
		
	}
	
    /*############  Function for the featured plugins page  ################*/		
	
	public function featured_plugins(){
		$plugins_array=array(
			'gallery_album'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/gallery-album-icon.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-gallery-plugin/',
						'title'			=>	'WordPress Gallery plugin',
						'description'	=>	'The gallery plugin is a useful tool that will help you to create Galleries and Albums. Try our nice Gallery views and awesome animations'
						),		
			'coming_soon'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/coming_soon.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-coming-soon-plugin/',
						'title'			=>	'Coming soon and Maintenance mode',
						'description'	=>	'Coming soon and Maintenance mode plugin is an awesome tool to show your visitors that you are working on your website to make it better.'
						),
			'countdown-extended'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/icon-128x128.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-countdown-extended-version/',
						'title'			=>	'WordPress Countdown Extended',
						'description'	=>	'Countdown extended is a fresh and extended version of the countdown timer. You can easily create and add countdown timers to your website.'
						),	
			'Booking Calendar'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/Booking_calendar_featured.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-booking-calendar-plugin/',
						'title'			=>	'WordPress Booking Calendar',
						'description'	=>	'WordPress Booking Calendar plugin is an awesome tool to create a booking system for your website. Create booking calendars in a few minutes.'
						),
			'Pricing Table'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/Pricing-table.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-pricing-table-plugin/',
						'title'			=>	'WordPress Pricing Table',
						'description'	=>	'WordPress Pricing Table plugin is a nice tool for creating beautiful pricing tables. Use WpDevArt pricing table themes and create tables just in a few minutes.'
						),		
			'chart'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/chart-featured.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-organization-chart-plugin/',
						'title'			=>	'WordPress Organization Chart',
						'description'	=>	'WordPress organization chart plugin is a great tool for adding organizational charts to your WordPress websites.'
						),						
			'youtube'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/youtube.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-youtube-embed-plugin/',
						'title'			=>	'WordPress YouTube Embed',
						'description'	=>	'YouTube Embed plugin is a convenient tool for adding videos to your website. Use YouTube Embed plugin for adding YouTube videos in posts/pages, widgets.'
						),
			'countdown'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/countdown.jpg',
						'site_url'		=>	'https://wpdevart.com/wordpress-countdown-plugin/',
						'title'			=>	'WordPress Countdown plugin',
						'description'	=>	'WordPress Countdown plugin is a nice tool for creating countdown timers for your website posts/pages and widgets.'
						),
			'lightbox'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/lightbox.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-lightbox-plugin/',
						'title'			=>	'WordPress Lightbox plugin',
						'description'	=>	'WordPress Lightbox Popup is a highly customizable and responsive plugin for displaying images and videos in a popup.'
						),
			'facebook'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/facebook.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-facebook-like-box-plugin/',
						'title'			=>	'Social Like Box',
						'description'	=>	'Facebook like box plugin will help you to display Facebook like box on your website, just add Facebook Like box widget to the sidebar or insert it into posts/pages and use it.'
						),
			'vertical_menu'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/vertical-menu.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-vertical-menu-plugin/',
						'title'			=>	'WordPress Vertical Menu',
						'description'	=>	'WordPress Vertical Menu is a handy tool for adding nice vertical menus. You can add icons for your website vertical menus using our plugin.'
						),						
			'poll'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/poll.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-polls-plugin/',
						'title'			=>	'WordPress Polls system',
						'description'	=>	'WordPress Polls system is a handy tool for creating polls and survey forms for your visitors. You can use our polls on widgets, posts, and pages.'
						),
			'comments_facebook'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/facebook-comments-icon.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-facebook-comments-plugin',
						'title'			=>	'WpDevArt Social comments',
						'description'	=>	'WordPress Facebook comments plugin will help you to display Facebook Comments on your website. You can use Facebook Comments on your pages/posts.'
						),						
			'duplicate_page'=>array(
						'image_url'		=>	wpda_form_PLUGIN_URI.'images/featured_plugins/featured-duplicate.png',
						'site_url'		=>	'https://wpdevart.com/wordpress-duplicate-page-plugin-easily-clone-posts-and-pages/',
						'title'			=>	'WordPress Duplicate page',
						'description'	=>	'Duplicate Page or Post is a great tool that allows duplicating pages and posts. Now you can do it with one click.'
						),						
						
			
		);
		?>
        <style>
         .featured_plugin_main{
			background-color: #ffffff;
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			float: left;
			margin-right: 30px;
			margin-bottom: 30px;
			width: calc((100% - 90px)/3);
			border-radius: 15px;
			box-shadow: 1px 1px 7px rgba(0,0,0,0.04);
			padding: 20px 25px;
			text-align: center;
			-webkit-transition:-webkit-transform 0.3s;
			-moz-transition:-moz-transform 0.3s;
			transition:transform 0.3s;   
			-webkit-transform: translateY(0);
			-moz-transform: translateY0);
			transform: translateY(0);
			min-height: 344px;
		 }
		.featured_plugin_main:hover{
			-webkit-transform: translateY(-2px);
			-moz-transform: translateY(-2px);
			transform: translateY(-2px);
		 }
		.featured_plugin_image{
			max-width: 128px;
			margin: 0 auto;
		}
		.blue_button{
    display: inline-block;
    font-size: 15px;
    text-decoration: none;
    border-radius: 5px;
    color: #ffffff;
    font-weight: 400;
    opacity: 1;
    -webkit-transition: opacity 0.3s;
    -moz-transition: opacity 0.3s;
    transition: opacity 0.3s;
    background-color: #7052fb;
    padding: 10px 22px;
    text-transform: uppercase;
		}
		.blue_button:hover,
		.blue_button:focus {
			color:#ffffff;
			box-shadow: none;
			outline: none;
		}
		.featured_plugin_image img{
			max-width: 100%;
		}
		.featured_plugin_image a{
		  display: inline-block;
		}
		.featured_plugin_information{	

		}
		.featured_plugin_title{
	color: #7052fb;
	font-size: 18px;
	display: inline-block;
		}
		.featured_plugin_title a{
	text-decoration:none;
	font-size: 19px;
    line-height: 22px;
	color: #7052fb;
					
		}
		.featured_plugin_title h4{
			margin: 0px;
			margin-top: 20px;		
			min-height: 44px;	
		}
		.featured_plugin_description{
			font-size: 14px;
				min-height: 63px;
		}
		@media screen and (max-width: 1460px){
			.featured_plugin_main {
				margin-right: 20px;
				margin-bottom: 20px;
				width: calc((100% - 60px)/3);
				padding: 20px 10px;
			}
			.featured_plugin_description {
				font-size: 13px;
				min-height: 63px;
			}
		}
		@media screen and (max-width: 1279px){
			.featured_plugin_main {
				width: calc((100% - 60px)/2);
				padding: 20px 20px;
				min-height: 363px;
			}	
		}
		@media screen and (max-width: 768px){
			.featured_plugin_main {
				width: calc(100% - 30px);
				padding: 20px 20px;
				min-height: auto;
				margin: 0 auto 20px;
				float: none;
			}	
			.featured_plugin_title h4{
				min-height: auto;
			}	
			.featured_plugin_description{
				min-height: auto;
					font-size: 14px;
			}	
		}

        </style>
      
		<h1 style="text-align: center;font-size: 50px;font-weight: 700;color: #2b2350;margin: 20px auto 25px;line-height: 1.2;">Featured Plugins</h1>
		<?php foreach($plugins_array as $key=>$plugin) { ?>
		<div class="featured_plugin_main">
			<div class="featured_plugin_image"><a target="_blank" href="<?php echo $plugin['site_url'] ?>"><img src="<?php echo $plugin['image_url'] ?>"></a></div>
			<div class="featured_plugin_information">
				<div class="featured_plugin_title"><h4><a target="_blank" href="<?php echo $plugin['site_url'] ?>"><?php echo $plugin['title'] ?></a></h4></div>
				<p class="featured_plugin_description"><?php echo $plugin['description'] ?></p>
				<a target="_blank" href="<?php echo $plugin['site_url'] ?>" class="blue_button">Check The Plugin</a>
			</div>
			<div style="clear:both"></div>                
		</div>
		<?php } 
	
	}
} 
?>