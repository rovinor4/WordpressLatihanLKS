<?php 
/**
 * We're going to use $wpda_form_table as global array for our database interactions
 * it is declared as global because local variable will not be 
 * accessible as activation hook to run properly
 *
 * @package WpDevArt Forms
 * @since	1.0
 */
 if ( ! defined( 'ABSPATH' ) ) exit;
// WordPress database's global objects
global $wpdb;

//	Plugin database tables info
global $wpda_form_table; 

//	Represents contact forms individually
$wpda_form_table['wpdevart_forms'] = $wpdb->prefix.'wpda_form_forms';

//	Represents contact form fields
$wpda_form_table['fields'] = $wpdb->prefix.'wpda_form_fields';

//	Represents contact form fields subfields
$wpda_form_table['subfields'] = $wpdb->prefix.'wpda_form_subfields';

//	Submissions time of the contact form
$wpda_form_table['submit_time'] = $wpdb->prefix.'wpda_form_submit_time';

//	Contains contact form submitted values
$wpda_form_table['submissions'] = $wpdb->prefix.'wpda_form_submissions';

?>