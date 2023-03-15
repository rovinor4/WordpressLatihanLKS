<?php
/*GDPR Wpdevart*/
/*Contact form*/

class wpdevart_contact_form_gdpr{
	
	private $privace_police="Contact Form WpDevArt has the opportunity for submitting form. When users submit subscribe form, they can also provide personal information, such as name, email and so on. All this data will be saved in WordPress database, so you need to receive user agreement when they submit subscribe form. Also, you need to get the user agreement when you delete or export their data upon their request. In accordance with GDPR, you need to be sure that all information is protected and the other services that you are using also observe data protection. In this case, you have liability, so check other services privacy policy as well and tell them to follow to the GDPR.";
	private $privace_police_title="Contact Form WpDevArt";
	
	public function __construct(){
		$this->call_hooks();
	}

/*############  Call hooks function ################*/

	private function call_hooks(){	
		add_filter('wp_privacy_personal_data_exporters', array($this,'registr_exporter'), 10 );
		add_filter('admin_init', array($this,'privacy_policy_content'),10);
		add_filter('wp_privacy_personal_data_erasers', array($this,'eraser_date'), 10);
	}

/*############  Function for registering exporter ################*/	
	
	public function registr_exporter($exporters){
		$exporters['wpdevart-contact-form'] = array(
			'exporter_friendly_name'	=>$this->privace_police_title,
			'callback'					=> array($this,'exporter_by_email')
		);
		return $exporters;
	}

/*############  Function for getting dates from the database ################*/
	
	private function get_date_from_databese($email_address=''){
		global $wpdb; 
		global $wpda_form_table;
		$all_submisions_by_email=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpda_form_table['submissions']." RIGHT JOIN ".$wpda_form_table['fields']." ON ".$wpda_form_table['submissions'].".fk_field_id=".$wpda_form_table['fields'].".id  WHERE `fk_submit_time_id` IN (SELECT fk_submit_time_id FROM ".$wpda_form_table['submissions']." WHERE `field_value` LIKE %s GROUP BY fk_submit_time_id) ORDER BY fk_submit_time_id",$email_address));
		
		return $all_submisions_by_email;
	}

/*############  Function for removing dates from the database ################*/
	
	private function remove_date_from_databese($email_address=''){
		$mailing_lists=json_decode(stripslashes(get_option('users_mailer')),true);
		if(isset($mailing_lists[$email_address])){
			unset($mailing_lists[$email_address]);
			update_option('users_mailer',json_encode($mailing_lists));
			return true;
		}else{
			return false;
		}
	}

/*############  Function for the eraser date ################*/
	
	public function eraser_date( $erasers ) {
		$erasers['wpdevart-contact-form'] = array(
		  'eraser_friendly_name' => $this->privace_police_title,
		  'callback'             => array($this,'eraser_date_email'),
		);
		return $erasers;
	}
	
/*############  Function for the privacy policy content ################*/	
	
	public function privacy_policy_content(){
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content(
			$this->privace_police_title, // section name
			$this->privace_police // content
		);
	}
	
	public function eraser_date_email( $email_address, $page = 1 ) {
		global $wpdb; 
		global $wpda_form_table;
		$submited_time_ids=array();
		$submited_time_result=$wpdb->get_results($wpdb->prepare("SELECT fk_submit_time_id FROM ".$wpda_form_table['submissions']." WHERE `field_value` LIKE %s GROUP BY fk_submit_time_id",$email_address));
		$items_removed=0;
		if(count($submited_time_result)>0){
			$items_removed=1;
		}else{
			return array(
			  'items_removed' => $items_removed,
			  'items_retained' => false,
			  'messages' => array(),
			  'done' => true,
			);
		}
		foreach($submited_time_result as $id){			
			array_push($submited_time_ids,$id->fk_submit_time_id);
		}
		$submited_time_ids=implode(",",$submited_time_ids);
		$this->unlink_images($email_address);
		$wpdb->query("DELETE FROM ".$wpda_form_table['submissions']." WHERE `fk_submit_time_id` IN (".$submited_time_ids.")");
		$wpdb->query("DELETE FROM ".$wpda_form_table['submit_time']." WHERE `id` IN (".$submited_time_ids.")");
		return array(
		  'items_removed' => $items_removed,
		  'items_retained' => false,
		  'messages' => array('removed'),
		  'done' => true,
		);
	}
	private function unlink_images($email_address){
		global $wpdb;
		global $wpda_form_table;
		$wpdb->show_errors();
		$uploaded_files=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpda_form_table['submissions']." RIGHT JOIN ".$wpda_form_table['fields']." ON ".$wpda_form_table['submissions'].".fk_field_id=".$wpda_form_table['fields'].".id  WHERE `fk_submit_time_id` IN (SELECT fk_submit_time_id FROM ".$wpda_form_table['submissions']." WHERE `field_value` LIKE %s GROUP BY fk_submit_time_id) AND fieldtype='file' ORDER BY fk_submit_time_id",$email_address));
		if(count($uploaded_files)>0){
			foreach($uploaded_files as $uploaded_file){
				$site_url= get_site_url();
				$rel_path=  str_replace($site_url,'',$uploaded_file->field_value);

				if( file_exists(ABSPATH .$rel_path)) {
					unlink(ABSPATH .$rel_path);
				}
			}
		}		
		return $all_submisions_by_email;	
	}
	public function exporter_by_email($email_address, $page = 1){
		// Limit us to 500 at a time to avoid timing out.
		$done=true;
		$data_to_export=array();
		$export_items=array();
		$all_dates=$this->get_date_from_databese($email_address);
		$limit = 500;	
		$submited_count=0;
		foreach ( $all_dates as $field ) {
			if($submited_count==0){
				$submited_count=$field->fk_submit_time_id;
			}
			if($submited_count!=$field->fk_submit_time_id){
				$submited_count=$field->fk_submit_time_id;
				$data_to_export[] = array(
					'name'  => "--------------------",
					'value' => "--------------------",
				);
			}
			if ( ! empty( $field ) ) {
				$data_to_export[] = array(
					'name'  => $field->label,
					'value' => $field->field_value,
				);
			}
		}
		if(!empty($data_to_export)){
			$done = true;
			$export_items[] = array(
				'group_id' => "wpdevart_contact_form",
				'group_label' => 'Contact Form WpDevArt',
				'item_id' => $email_address,
				'data' => $data_to_export,
			);
		}
		return array(
		  'data' => $export_items,
		  'done' => $done,
		);
	}
	
}
$wpdevart_contact_form_gdpr=new wpdevart_contact_form_gdpr();
?>