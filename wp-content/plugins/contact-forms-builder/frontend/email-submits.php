<?php 
/**
 * wpda_form_email_submit() function is used to send email ([containing frontend form data]
 * to the recepient) AND saving form submission data in the database .
 * This function is called when user  clicks on submit button of the form from front-end
 *
 * @package WpDevArt Forms
 * @since	1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/*############  Submit Function ################*/

function wpda_form_email_submit($atts) {
	global $wpdb;
	global $wpda_form_table;
	
	$form_submited_via_ajax = 0;
	$maxsize = wpda_form_return_bytes(ini_get('upload_max_filesize'));
	$acceptable =  get_allowed_mime_types();
	$allowed_html_tags = array('span' => array('class' => array()),
								'br' => array(),
								'strong' => array('class' => array()),
								'b' => array('class' => array()),
								'i' => array('class' => array()),
								'h1' => array('class' => array()),
								'h2' => array('class' => array()),
								'h3' => array('class' => array()),
								'h4' => array('class' => array()),
								'h5' => array('class' => array()),
								'h6' => array('class' => array()),
							);
	$success_flag = 0;
	$errors = array();
	
    
	if(isset($_POST['btn_send_form_email'])) { 
	
        
		//	Check whether the contact form was submited using ajax call 
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	 		$atts = json_decode(stripslashes($_POST['atts'])) ;
			$atts = wpda_form_object_to_array($atts);
			$form_submited_via_ajax = 1;
		} 
		if(!is_array($atts)){$atts =json_decode(stripslashes($atts),true);}
		$form_id = $atts['id'];	// e.g [wpdevart_forms id=1]
		$form_meta = $wpdb->get_row($wpdb->prepare( "SELECT * FROM ".$wpda_form_table['wpdevart_forms']." WHERE id=%d ",$form_id));
		$params = (array) json_decode($form_meta->params);
		
        
         $form_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpda_form_table['fields']." WHERE fk_form_id =%d AND position < 9999 ORDER BY position ASC",$form_id));
    
        
		//	Extra options
		$error_mgs_heading = stripslashes_deep(esc_html($params['error_mgs_heading']));
		$field_required_msg = stripslashes_deep(esc_html($params['field_required_msg']));
		$recaptcha_mismatch_error = stripslashes_deep(esc_html($params['recaptcha_mismatch_error']));
		$upload_btn_label = stripslashes_deep(esc_html($params['upload_btn_label']));
		$upload_file_size_error_msg = stripslashes_deep(esc_html($params['upload_file_size_error_msg']));
		$upload_file_extension_error_msg = stripslashes_deep(esc_html($params['upload_file_extension_error_msg']));
		$sending_mail_via 		= isset($params['sending_mail_via'])?stripslashes_deep(esc_html($params['sending_mail_via'])):"phpmailer";
		$smtp_host 				= isset($params['smtp_host'])?stripslashes_deep(esc_html($params['smtp_host'])):"";
		$smtp_port 				= isset($params['smtp_port'])?stripslashes_deep(esc_html($params['smtp_port'])):"";
		$smtp_auth_method 		= isset($params['smtp_auth_method'])?stripslashes_deep(esc_html($params['smtp_auth_method'])):"none";
		$smtp_username 			= isset($params['smtp_username'])?stripslashes_deep(esc_html($params['smtp_username'])):"";
		$smtp_password 			= isset($params['smtp_password'])?stripslashes_deep(esc_html($params['smtp_password'])):'';
		$email_from_name 		= isset($params['email_from_name'])?stripslashes_deep(esc_html($params['email_from_name'])):'';
		$email_from_email 		= isset($params['email_from_email'])?stripslashes_deep(esc_html($params['email_from_email'])):'';
		
		// Check if user has attached file 
		if(isset($_FILES) and !empty($_FILES)) {
			foreach($_FILES as $key => $file) {	
				$tmp_name	= $file['tmp_name'];
				$type	 	= $file['type'];
				$name 		= $file['name'];
				$size		= $file['size'];
				if($size > $maxsize ) {
					//$errors[] = " $size > $maxsize , upload size limit(".ini_get('upload_max_filesize').") over for file $name ";
					$errors[] = "[$name] ". $upload_file_size_error_msg ." ($size > $maxsize) allowed size = ".ini_get('upload_max_filesize')." "; 
				}
				if(!in_array($type, $acceptable)) {
					//$errors[] = "Extension ".$type ." not allowed for file $name";
					$errors[] = "[$name] ". $type.' '.$upload_file_extension_error_msg;
			    }
			}
		}
		
		// Detect if submitted contact form was empty then don't process
		$all_fields_empty = 0;
		
		foreach($_POST as $key => $value) {
			if($key == "btn_send_form_email" || $key == "process_ajax" || $key == "atts" || $key == "action" || $key == "form_id") {
				 $all_fields_empty = $all_fields_empty+1;
			} else {
				if(is_array($value)) {
					if(empty($value)){
						$all_fields_empty = $all_fields_empty + 1;
					}
				} else {
					$value = trim($value);
					if($value == "") {
						 $all_fields_empty = $all_fields_empty + 1;
					}
				}
			}
		}
		
		if(count($_POST) == $all_fields_empty) {
			if(isset($_FILES) && !empty($_FILES)) {
				// continue;
			} else {
				if($form_submited_via_ajax == 1 ){
					return "<div class='failure_message failure_message".$form_id." reply_msg reply_msg".$form_id."'> Empty form can not be submitted. </div> ";
				} else {
					echo "Empty form can not be submitted.";
					die();
				}
			}
		}
 		
		//	Check if radio or checkbox field is enabled in form, in that case user should provide at least 1 radio/checkbox otherwise print Error Message
		$radio_checkbox_fields = $wpdb->get_results($wpdb->prepare( "SELECT * FROM ".$wpda_form_table['fields']." WHERE fk_form_id=%d and (fieldtype='radio' || fieldtype='checkbox')",$form_id));
		if($radio_checkbox_fields) {
			foreach($radio_checkbox_fields as $radio_checkbox_field) {
				//	$radio_checkbox_field->label = stripslashes_deep(wp_kses($radio_checkbox_field->label, $allowed_html_tags));
				$radio_checkbox_field->label = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $radio_checkbox_field->label )));
				
				
				if($radio_checkbox_field->is_required ==1 ) {
					$post_var = "sublabel_".$radio_checkbox_field->id."";
					if(!isset($_POST[$post_var])) {
						//	$errors[] = "Field with label {$radio_checkbox_field->label} is required  ";
						$errors[] = $field_required_msg.' '.$radio_checkbox_field->label;
					}
					unset($post_var);
				}
			}
		}
		
		$recaptcha_flag = 0;       
	 	
		//	Check whether ReCaptcha was set in the contact form
        
       //   Make sure that users can't remove recaptha by inspect element on browser
        foreach($form_fields as $fkey => $fvalue) {
            if($form_fields[$fkey]->fieldtype == 'recaptcha' && !isset($_POST['recaptchaSumValue']) )
                $recaptcha_flag = 2;
        }
        if($recaptcha_flag == 2) $errors[] = 'You must provide a valid value for reCaptcha';
        
		if( isset($_POST['recaptchaSumValue']) ) {
            
			foreach($_POST['recaptchaSumValue'] as $key=>$val) {
				if( ($_POST['recaptchaSumValue'][$key] == $_POST['submitedRecaptchaValue'][$key]) && ($_POST['isCaptchaRequired'][$key] == 1) ){
					$recaptcha_flag = 0;
				} else {
					if($_POST['isCaptchaRequired'][$key] == 1) {
						$recaptcha_flag = 1;
						//	$errors[] = "reCaptcha value mis-matched";
						$errors[] = $recaptcha_mismatch_error;
					}
				}
			}
		}
		
		$email_sender = "";
		$email_receiver = "";
		$email_subject = "";
		$message_body = ""; 
		$attachment_urls = array();
		$field_ids_arr = array();
		$field_values_arr = array();
	  
		
			
		$fieldtype_email_exists = 0; 
		$email_receiver = $params['email_receiver'];
		$email_subject = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $params['email_subject'] ) )); 
		$email_body_bottom_msg = stripslashes_deep(wp_kses($params['email_body_bottom_msg'],$allowed_html_tags));
		$success_msg = stripslashes_deep( $params['success_msg'] );
		$failure_msg = stripslashes_deep( $params['failure_msg'] );
		
		$isRequiredEmailSubmitUrlRedirect = isset($params['isRequiredEmailSubmitUrlRedirect'])?$params['isRequiredEmailSubmitUrlRedirect']:0;
		//	Redirect user to a specific page as FORM from frontend is submited
		$email_submit_redirect_url = isset($params['email_submit_redirect_url'])?$params['email_submit_redirect_url']:'';
		$after_submit_hide_form = isset($params['after_submit_hide_form'])?$params['after_submit_hide_form']:0;
		
		// Send auto email
		$isRequiredAutoResponder = isset($params['isRequiredAutoResponder'])?$params['isRequiredAutoResponder']:'';
		$autoResponderSubject 	 = isset($params['autoResponderSubject'])?$params['autoResponderSubject']:'';
		$autoResponderMessage 	 = isset($params['autoResponderMessage'])?$params['autoResponderMessage']:'';	
		
		//	Move attachment to server(1) or email(0)
		$get_submissions_on  = $params['get_submissions_on'];
		
		$save_form_submission = 1;	
		$send_email_required = 1;
		
		
		 
		if($get_submissions_on == 1 || $get_submissions_on == 2 ) {
			$save_form_submission = 1;	
		} else {
			$save_form_submission = 0;
		}
		
		if($get_submissions_on == 1 || $get_submissions_on == 3 ) {
			$send_email_required = 1;
			
			if($get_submissions_on == 1) {
				$save_form_submission = 1;
			}
			
			if($get_submissions_on == 3) {
				$save_form_submission = 0;
			}
		} else {
			$send_email_required = 0;
		}
		 
		 
		
 		array_pop($_POST);  // Remove last element[submit button] 
		
		// First email in the form will be used to  auto responder  email to
   		$user_email_found = '';
		
		foreach($_POST as $key => $value) {	
				
			$ary = explode("_", $key);
			
			//	Ary[1] must be set as it is dabatase id of the field
			if(!isset($ary[1])){
				continue; // continue to next loop iteration
			}
			
			//	Ary[1] must be numeric as it is dabatase id of the field
			if(!is_numeric($ary[1])){
				continue;
			}
			
			$field_id = $ary[1];
			$field_ids_arr[] = $field_id;
			$fields_record = $wpdb->get_results( $wpdb->prepare("SELECT * FROM ".$wpda_form_table['fields']." WHERE id=%d",$field_id));
			foreach($fields_record as $field_record) {
				
				//$field_label = stripslashes_deep(wp_kses($field_label, $allowed_html_tags));
				$field_record->label =  wp_strip_all_tags(html_entity_decode(stripslashes_deep( $field_record->label)));
				$field_label = $field_record->label;
				
				$is_required = $field_record->is_required;
				$fieldtype = $field_record->fieldtype;
				
				//	Check if field type was email, if so then we'll use it as sender email for mail headers
				// we have set this flag above
				if($fieldtype == "email" && $fieldtype_email_exists == 0) {
					$user_email_found = $value;
					$fieldtype_email_exists = 1;
				}
			}
			
			if(!is_array($value)) {
				$value = trim($value);
			}
			if( ($is_required == 1) && ($value=="") ) {
				//$errors[] = "Field with label {$field_label} is required  ";
				
				$errors[] = $field_required_msg.' '.$field_label;
				
			} else {	
				// i.e. field type is radio, checkbox or dropdown
				if(is_array($value)) {
					$message_body .= $field_label.": "; 
					$field_val = "";
					foreach($value as $sub_val) {
						// do not add comma, if last iteration  
						if ($sub_val === end($value)) {	
							$message_body .=  wp_strip_all_tags(html_entity_decode(stripslashes_deep($sub_val)));
							$field_val .=  wp_strip_all_tags(html_entity_decode(stripslashes_deep($sub_val)));
						} else {
							$message_body .=  wp_strip_all_tags(html_entity_decode(stripslashes_deep($sub_val))) .", ";
							$field_val .=  wp_strip_all_tags(html_entity_decode(stripslashes_deep($sub_val))) .", ";
						}
					}
					
					//	Store value in field_values array for later use in storing form submited data
					$field_values_arr[] = $field_val;
					if( ($is_required == 1) && $field_val=="" ) {	 
						//	$errors[] = "Field with label {$field_label} is required  ";
						 
						$errors[] = $field_required_msg.' '.$field_label;
					}					
					unset($field_val);
					
				} else  {
					if(filter_var($value, FILTER_VALIDATE_URL)) {
						$value = "<a href=".$value.">".$value."</a>";
						$message_body .= $field_label.": " .wp_strip_all_tags(html_entity_decode(stripslashes_deep($value)));
					} else {
						$message_body .= $field_label.": " .wp_strip_all_tags(html_entity_decode(stripslashes_deep($value)));
					}
					
					
					//	Store value in field_values array for later use in storing form submited data
					if( ($is_required == 1) && ($value == "") ) {	 
						//	$errors[] = "Field with label {$field_label} is required  ";
						 
						$errors[] = $field_required_msg.' '.$field_label;
					}
					$field_values_arr[] = wp_strip_all_tags(html_entity_decode(stripslashes_deep($value)));
				} 
			}
			
			$message_body .= "<br />"; // insert next record on new line
			
		}//foreach($_POST as $key=>$value)
		
		$email_sender = ($fieldtype_email_exists == 1) ? $user_email_found : "no-reply@".$_SERVER['SERVER_NAME']."" ;
		
		//	Move files to the server
		if($save_form_submission == 1) {
			foreach($_FILES as $key => $file) {
				$temp = explode("_", $key);
				$attachment_field = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpda_form_table['fields']." WHERE id=%d",$temp[1]));
				
				//$attachment_field->label = stripslashes_deep(wp_kses($attachment_field->label, $allowed_html_tags));
				$attachment_field->label = wp_strip_all_tags(html_entity_decode(stripslashes_deep( $attachment_field->label)));
				 
				if($attachment_field->is_required == 1 && empty($file['tmp_name'])) {
					//	$errors[] = "Field with label $attachment_field->label is required";
					 
					$errors[] = $field_required_msg.' '.$attachment_field->label;
					continue;
				}
				$field_ids_arr[] = $temp[1];
				unset($temp);
				
				// Store the file information to variables for easier access
				$tmp_name	= $file['tmp_name'];
				$type	 	= $file['type'];
				$name 		= $file['name'];
				$size		= $file['size'];
				
				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}
			
				$uploadedfile = $file;
				
				// If you don’t pass 'test_form' => FALSE the upload will be rejected.
				$upload_overrides = array( 'test_form' => false ); //'test_form' => false
				
				// If the upload succeeded, then the file will exist
				if (file_exists($tmp_name)) {
					if(is_uploaded_file($tmp_name)) {
						$file = fopen($tmp_name,'rb');
						$data = fread($file,filesize($tmp_name));
						fclose($file);
						$data = chunk_split(base64_encode($data));
					}
				}
				add_filter( 'upload_dir', 'wpda_form_upload_dir_func', 10, 1 );
				$movefile = wp_handle_upload( $uploadedfile, $upload_overrides);
				remove_filter( 'upload_dir', 'wpda_form_upload_dir_func', 10, 1 );				
				if ( $movefile && !isset( $movefile['error'] ) ) {
					$field_values_arr[] = $movefile['url'];
					if(isset($attachment_urls[$attachment_field->label])) {
						$append_int = $key+1;
						$attachment_urls[$attachment_field->label.' '.$append_int] = $movefile['url']; 
					} else {
						$attachment_urls[$attachment_field->label] = $movefile['url']; 
					}
				 } else  {
					/**
					 * Error generated by _wp_handle_upload()
					 * @see _wp_handle_upload() in wp-admin/includes/file.php
					 */
					 
					// $errors[] = $movefile['error'];
				}
			}
		}
		
		// Files were moved to server 
		if(!empty($attachment_urls)) {
			$message_body .= "<br />";
			foreach($attachment_urls as $key => $attachment_url):
				$label = $key;
				$url = $attachment_url;
				$label_url = $label.": ".$url;
				$message_body .= $label.": ". $url ."<br />";
			endforeach;
		}
		if(empty($errors)) {  
			if($save_form_submission == 1) {
				/**
				 *	Save the record in database
				 */
				
				$wpdb->insert( $wpda_form_table['submit_time'], array(  'id'  =>  '','fk_form_id' => $form_id, 'submit_time' => time() ));
				$newly_created_submit_time_id = $wpdb->insert_id;
				if($newly_created_submit_time_id) {
					foreach($field_ids_arr as $key => $field_id_val) {
						$inserted = $wpdb->insert( $wpda_form_table['submissions'], array(  'id'  =>  '',
																	 'fk_submit_time_id' => $newly_created_submit_time_id, 
																	 'fk_field_id' => $field_id_val,
																	 'field_value' => $field_values_arr[$key]
																	 ),
															 array ('%d','%d','%d','%s')
																	 );
						if($inserted) {
							$success_flag = 1;
						} 
					}
				} else {
					echo "Error , please referesh the page and try again Thanks!";
					exit;
				}
			}
			
			//	Send email if it is required
			if($send_email_required == 1) {
				// from mail from name
				
				if($email_sender=="no-reply@".$_SERVER['SERVER_NAME'] && $email_from_name!='' && $email_from_email!=''){
					$from_name=$email_from_name;
					$from_email=$email_from_email;
				}else{
					$from_name=$email_sender;
					$from_email=$email_sender;
				}	
				// body of the message
				$Body 	= $message_body."<br />" ;
				$Body    .= "<br />".$email_body_bottom_msg; 
				$Body     = stripslashes_deep(wp_kses($Body, $allowed_html_tags));
				// if the user has required auto responder but doesn't specified email in the form, then notify the user
				if($isRequiredAutoResponder == "1") {
					if($user_email_found =='') {
						$Body .= "<br />"."NOTE: You have enabled auto responder but hasn't specified email field type in form"."<br />";
						$Body .= "Please specify email field type or turn off auto responder Thanks!"."<br />";
					}
				}				
				if(!$Body) {	
					$Body = $from_name ." sent you this email";
				}
				$attachment=array();
				$cur_counter=0;
				if($save_form_submission == 0) {
					foreach($_FILES as $key => $file) {						
						$attachment[$cur_counter]["tmp_name"]=$file['tmp_name'];
						$attachment[$cur_counter]["name"]=$file['name'];						
					}
				}
				$mail_params=array(
					"html"=>true,
					"email_receiver" => $email_receiver,
					"from_name" => $from_name,
					"from_email" => $from_email,
					"subject" => wp_strip_all_tags(html_entity_decode(stripslashes_deep( $params['email_subject'] ) )),
					"body"=>$Body,
					"attachment"=>$attachment,
					"sending_mail_via" => $sending_mail_via,
					"smtp_host" => $smtp_host,
					"smtp_port" => $smtp_port,
					"smtp_auth_method" => $smtp_auth_method,
					"smtp_username" => $smtp_username,
					"smtp_password" => $smtp_password,

				);				
				$success_flag = wpdevart_form_send_mail($mail_params);
				if($success_flag===1 || $success_flag===true){
					
				}else{
					$errors[]="We have error for sending mail please try again later";
				}
			}
			
			//	Processing ok
			//	processing ok
			if($success_flag == 1) {
				// if the auto responder is enabled, send email to recipient
				if($isRequiredAutoResponder == "1") {
					if($user_email_found) {
						if(!empty($autoResponderSubject) && !empty($autoResponderMessage)) {
							$mail_params["subject"] = html_entity_decode(stripslashes_deep(esc_attr($autoResponderSubject)));
							$mail_params["body"] =	html_entity_decode(stripslashes_deep(esc_attr($autoResponderMessage)));
							$mail_params["email_receiver"]=$user_email_found; //contact form destination e-mail								
							if($email_from_name!='' && $email_from_email!=''){
								$from_name=$email_from_name;
								$from_email=$email_from_email;
							}else{
								$from_name="no-reply@".$_SERVER['SERVER_NAME'];
								$from_email="no-reply@".$_SERVER['SERVER_NAME'];
							}	
							$succses_send = wpdevart_form_send_mail($mail_params);	
							if($succses_send==1 || $succses_send==true){					
							}else{
								$errors[]="We have error for sending mail please try again later";
							}
							
						}
					}
				} 
				
				//	in case user has enabled setting to redirect on form submission
				if( isset($isRequiredEmailSubmitUrlRedirect) &&  $isRequiredEmailSubmitUrlRedirect=="1") {
					if(!empty($email_submit_redirect_url)) {
						$location = $email_submit_redirect_url;
						echo "<script>location.href='$location';</script>";
						exit;
					}
				}  
			
				
				if($form_submited_via_ajax == 1) {
					$temp = "successmsg_";
				}				
				//	return represents whether email was sent successfully or not, if email sent, hide the form	
				
				echo wpdevart_from_sucsses_message($success_msg,$form_id);
			}else{
				wpdevart_from_print_errors($errors,$form_id);
			}
			
		} else {
			wpdevart_from_print_errors($errors,$form_id);
		} // if(empty($errors))
		
 	 unset($_POST['btn_send_form_email']);
  }	// isset($_POST['btn_form_data'])
}// wpda_form_email_submit()
function wpdevart_form_send_mail($params=array()){
	$defaults=array(
		"html"=>true,
		"CharSet" => get_option('blog_charset'),
		"email_receiver" => "",
		"from_name" => "no-reply@".$_SERVER['SERVER_NAME'],
		"from_email" => "no-reply@".$_SERVER['SERVER_NAME'],
		"subject" => "",
		"body"=>"",
		"attachment"=>array(),
		"sending_mail_via" => "phpmailer",
		"smtp_host" => "",
		"smtp_port" => "",
		"smtp_auth_method" => "none",
		"smtp_username" => "",
		"smtp_password" => "",
		
	);
	$marged_array=array();
	foreach($defaults as $key=>$value){
		if(isset($params[$key])){
			$marged_array[$key]=$params[$key];	
		}else{
			$marged_array[$key]=$value;	
		}
	}
	if($params['sending_mail_via']=="phpmailer"){
		if(file_exists(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php')){
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			if (!class_exists( '\PHPMailer\PHPMailer\SMTP',false)){
				require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			}
			$mail_to_send = new \PHPMailer\PHPMailer\PHPMailer();
		}else{
			require_once ABSPATH . WPINC . '/class-phpmailer.php';	
			$mail_to_send = new PHPMailer();			
		}
		if($marged_array['smtp_host'])
			$mail_to_send->Host=$marged_array['smtp_host'];
		if($marged_array['smtp_port'])
			$mail_to_send->Port=(int)$marged_array['smtp_port'];
		if($marged_array['smtp_auth_method']!="none"){
			$mail_to_send->SMTPAuth = true;
			$mail_to_send->SMTPSecure = $marged_array['smtp_auth_method'];
			$mail_to_send->Username = $marged_array['smtp_username'];
			$mail_to_send->Password = $marged_array['smtp_password'];
			$mail_to_send->isSMTP();
		}
		if($marged_array['html'])
			$mail_to_send->isHTML(true);
		
		
		if(strpos($marged_array["email_receiver"],',')!==FALSE){
			$emails=explode(',',$marged_array["email_receiver"]);
			foreach($emails as $email){
				$mail_to_send->AddAddress($email);
			}
		}else{
			$mail_to_send->AddAddress($marged_array["email_receiver"]);
		}
		
		$mail_to_send->From = $marged_array['from_email'];
		$mail_to_send->FromName = $marged_array['from_name'];                    
		$mail_to_send->Subject = $marged_array['subject'];
		$mail_to_send->Body    = $marged_array['body'];
		foreach($marged_array['attachment'] as $value){
			if(isset($value['tmp_name']) && isset($value['name']))
			$mail_to_send->AddAttachment($value['tmp_name'],$value['name']);
		}
		if ($mail_to_send->Send() ) {		
			return 1;
		}else{			
			return "We have error for sending mail please try again later";			
		}		
	}else{
		$from = "From: '" . $marged_array['from_name'] . "' <" . $marged_array['from_email'] . ">" . "\r\n";
		$headers = "MIME-Version: 1.0\n" . $from . " Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
		$attachment=array();
		foreach($marged_array['attachment'] as $value){
			if(isset($value['tmp_name']) && isset($value['name']))
				array_push($attachment,$value['tmp_name'].$value['name']);			
		}
		if(strpos($marged_array["email_receiver"],',')!==FALSE){
			$emails=explode(',',$marged_array["email_receiver"]);
		}
		else{
			$emails=$marged_array["email_receiver"];
		}
		return wp_mail($emails, $marged_array['subject'], $marged_array['body'], $headers,$attachment);		
		
	}	
}

/*############ Print error function ################*/

function wpdevart_from_print_errors($errors,$form_id){
	?><div class="failure_message failure_message<?php echo $form_id;?> reply_msg reply_msg<?php echo $form_id;?>"><?php
	echo "<h3>Please fix the Following Error(s) </h3> ";
	foreach ($errors as $key => $error_msg) {
		echo $key+1;echo " = ";echo $error_msg;
		echo "<br />";
	}
	echo "</div>";			
}

/*############  Function for the success message ################*/

function wpdevart_from_sucsses_message($message,$form_id){
	return '<div class="success_message successmsg_ success_message' .$form_id .' '.'reply_msg reply_msg' .$form_id .'">' .$message.'</div>';	
}



?>