<?php
/*
* Plugin Name: WP Responsive Jquery Slider
* Plugin URI: http://www.vivacityinfotech.net
* Description: WP Responsive Jquery Slider is world renowned as the most beautiful and easy to use slider on the market.Create dynamic slideshows that adapt to any screen in just few clicks. WP Responsive Jquery Slider one of the best ways to display lots of information in a relatively small space while adding cool functionality to a web page.The jQuery plugin is completely free and totally open source, and there is literally no better way to make your website look totally stunning.
* Version: 1.7
* Author: Team Startbit
* Author URI: http://www.vivacityinfotech.net
* Author Email: support@vivacityinfotech.net
* Requires at least: 3.8
* Text Domain: wp-responsive-jquery-slider
* Domain Path: /languages/
* @license    http://www.gnu.org/licenses/gpl-2.0.html
*/
/* Copyright 2014,2015,2016,2017 Vivacity InfoTech Pvt. Ltd.  (email : support@vivacityinfotech.net)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//error_reporting(0);

add_filter('plugin_row_meta', 'wrjs_registerspluginlinks',10, 2);
function wrjs_registerspluginlinks($links, $file) {
	if ( strpos( $file, 'wp-responsive-jquery-slider.php' ) !== false ) {
		$links[] = '<a href="https://wordpress.org/plugins/wp-responsive-jquery-slider/faq/">FAQ</a>';
		$links[] = '<a href="http://www.vivacityinfotech.net/support/">'. __( "Support", "wp-responsive-jquery-slider" ).'</a>';
		$links[] = '<a href="http://vivacityinfotech.net/paypal-donation/">'
		. __( "Donate", "wp-responsive-jquery-slider" ).'</a>';
	}
	return $links;
}
 
//language support
add_action('init', 'wrjs_load_languagetrans');
function wrjs_load_languagetrans() {
 load_plugin_textdomain('wp-responsive-jquery-slider', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
}

// setting in plugin page
function wrjs_settings_page( $links ) {
	$settings_block = '<a href="' . admin_url('edit.php?post_type=vslides&page=settings_section_slider') .'">'. __("Settings", "wp-responsive-jquery-slider").'</a>';
	array_unshift( $links, $settings_block);
	return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'wrjs_settings_page');

// when activate the slider  function call custom post and default settings 
register_activation_hook( __FILE__, 'wrjs_activate_slider' );
function  wrjs_activate_slider() {
    wrjs_custom_post_type_slider();
   flush_rewrite_rules();
	 wrjs_defult_setting_slider();
}

// deactivate slider
register_deactivation_hook( __FILE__, 'wrjs_deactivate_slider' );
function wrjs_deactivate_slider() {
   flush_rewrite_rules();
}

register_uninstall_hook( __FILE__, 'wrjs_uninstall_slider' );
function wrjs_uninstall_slider() {
	delete_option( 'get_settings_option' );	
}

// create custom post type Vslide
function wrjs_custom_post_type_slider() {	
	$labels = array(
		'name'                 =>  'Vslider', 
		'singular_name'        => 	'Vslide', 
		'all_items'            => 	'All Vslides', 
		'add_new'              =>	__( "Add New", "wp-responsive-jquery-slider").' Vslide', 
		'add_new_item'         => 	__( "Add New", "wp-responsive-jquery-slider").' Vslide', 
		'edit_item'            => 	__( "Edit", "wp-responsive-jquery-slider").' Vslide',
		'new_item'             => 	__( "New", "wp-responsive-jquery-slider").' Vslide', 
		'view_item'            => 	__( "View", "wp-responsive-jquery-slider").' Vslide', 
		'search_items'         =>  __( "Search", "wp-responsive-jquery-slider").' Vslide',
		'not_found'            => 	__( "No Slide found", "wp-responsive-jquery-slider"), 
		'not_found_in_trash'   => 	__( "No Slide found in Trash", "wp-responsive-jquery-slider"),
		'parent_item_colon'    => ''
	);
	
	$args = array(
		'labels'               => $labels,
		'public'               => true,
		'publicly_queryable'   => true,
		'_builtin'             => false,
		'show_ui'              => true, 
		'query_var'            => true,
		'rewrite'              => array( "slug" => "vslides" ),
		'capability_type'      => 'post',
		'hierarchical'         => false,
		'menu_position'        => 20,
		'supports'             => array( 'title','thumbnail', 'page-attributes' ),
		'taxonomies'           => array(),
		'has_archive'          => true,
		'show_in_nav_menus'    => false
	);	
	register_post_type( 'vslides', $args );
}

add_action( 'init', 'wrjs_custom_post_type_slider' );
add_action( 'init', 'wrjs_create_slider_taxonomies', 0 );
function wrjs_create_slider_taxonomies() {
    register_taxonomy(
        'multiple_slider',
        'vslides',
        array(
            'labels' => array(
                'name' => __("Create new slider", "wp-responsive-jquery-slider"),
                'add_new_item' => __("Add New Slider Name", "wp-responsive-jquery-slider"),
                'new_item_name' => __("New Slider", "wp-responsive-jquery-slider"),
                'edit_item' => __("Edit Slider Name", "wp-responsive-jquery-slider"),
                'update_item' => __("Update Slider Name", "wp-responsive-jquery-slider"),
                'all_items' => __("All Slider", "wp-responsive-jquery-slider"),
                'search_items' => __("Search Slider", "wp-responsive-jquery-slider"),
                'not_found' => __("No Slider Found", "wp-responsive-jquery-slider"),
                'parent_item' => __("Parent Slider", "wp-responsive-jquery-slider")
            ),
            'show_ui' => true,
            'show_tagcloud' => false,
            'hierarchical' => true
        )
    );
}

add_action('admin_head-edit-tags.php', 'wrjs_remove_parent_category');
function wrjs_remove_parent_category()
{          
    if ( 'vslides' != $_GET['post_type'] )
     return;

    $parent = 'parent()';
    if (isset($_GET['action']))
        $parent = 'parent().parent()';
?>
<script type="text/javascript">
	jQuery(document).ready(function($) {     
	    $('label[for=parent]').<?php echo $parent; ?>.remove(); 
	    //  $('#tag-description').parent().remove();
	    var ctxt = '<?php _e("Slider Shortcode is", "wp-responsive-jquery-slider") ?>';
	    $('#tag-description').val(ctxt+': [wrjs_post_slider="slug name"]');                  
	}); 
</script>        
<?php
}
 
// linked with css and jquery
add_action( 'template_redirect', 'wrjs_enqueue_scripts_slider' );
function wrjs_enqueue_scripts_slider() {
	global $post_type;	
	if ( ( isset( $post_type ) && $post_type == 'vslides' ) || ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'vslides' ) ) {	
		wp_enqueue_style( 'main_wrjs', plugins_url( 'css/main.css' , __FILE__ ) );
	}
	
	wp_enqueue_style( 'style_wrjs', plugins_url( 'css/style.css' , __FILE__ ) );
	wp_enqueue_script( 'slider_script', plugins_url( 'script.js' , __FILE__ ) , array( 'jquery' ), 0.1, true );	
	$options = get_option( 'get_settings_option' );
  	wp_localize_script( 'slider_script', 'wrjs', array(
		'effect' => $options['effect_wrjs'],
      'show_panel_nav' => $options['show_panel_nav_wrjs'],
      'change_post' => $options['post_wrjs'],
		'delay' => $options['delay_wrjs'],
		'duration' => $options['duration_wrjs'],
		'direction' => $options['direction_wrjs'],
		'start' => $options['start_wrjs'],
      'pauseOnHover' => $options['pauseOnHover_wrjs']		
	));
} 


// Width and Height for slider
add_action( 'after_setup_theme', 'wrjs_dimension_slider' );
function wrjs_dimension_slider() {
	$options = get_option( 'get_settings_option' );
	//print_r($options['height_wrjs']);
	$options = get_option( 'get_settings_option' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'slide-thumbnail', $options['width_wrjs'], $options['height_wrjs'], true );	
}

// shortcode for slider [post_slider]
add_action( 'init', 'wrjs_shortcode_slider' );
function wrjs_shortcode_slider() {
	add_shortcode( 'wrjs_post_slider', 'wrjs_post_slider' );
}

// get slide at front from custom post type
function wrjs_post_slider($slug) {
  $term = get_term_by('slug',$slug[0], 'multiple_slider','OBJECT');  
  $options = get_option( 'get_settings_option' );
	if ($options['post_wrjs'] == 'vslide') {
		if($slug != '')
		{
			$slides = new WP_Query( 
		    array(
		       'post_type' => 'vslides',  
		       'tax_query' => array(
		          array (
		            'taxonomy' => 'multiple_slider',
		            'field' => 'slug',
		            'terms' => $slug,
		          )
		       ),
		       'posts_per_page' => '-1',
		       'order' => 'ASC', 
		       'orderby' => 'menu_order'
		    )
			);	
		} else {				
					$slides = new WP_Query( 
				    array(
				       'post_type' => 'vslides',				      
				       'posts_per_page' => '-1',
				       'order' => 'ASC', 
				       'orderby' => 'menu_order'
				    )
					);				
		}
	} else { 	
		$slides = new WP_Query( array( 'post_type' => 'post', 'order' => 'ASC', 'orderby' => 'menu_order' ) );		
	}
      // print_r($slides);
        if ($options['show_panel_nav_wrjs'] == 0){
           $show_panel_nav = 'remove';
        }
        else { $show_panel_nav = '';
        }
        
	$slider = '';	
	if ( $slides->have_posts() ) :
   
		$slider = '<div id="wrjs_'.$term->term_id.'" class="contain_slider wrjs custome_slider">';
	// enqueue_script_slider_multiple($term->term_id);
		 	$slider .= '<ul class="slides">';
				
			while ( $slides->have_posts() ) : $slides->the_post(); 	 
		
				$slider .= '<li>';				   
					$slider .= '<div id="slide-' . get_the_ID() . '" class="slide">';						
						global $post;
						
							if ( has_post_thumbnail() ) {
								if ( get_post_meta( $post->ID, "_slide_link_url", true ) ) 
									$slider .= '<a href="' . get_post_meta( $post->ID, "_slide_link_url", true ) . '" title="' .  the_title_attribute ( array( 'echo' => 0 ) ) . '" >';
									$slider .= get_the_post_thumbnail( $post->ID, 'slide-thumbnail', array( 'class' => 'slide-thumbnail' ) );
									$caption_wrjs = $options['caption_wrjs'];
									if($caption_wrjs == '1') {
       								$slider .='<p class="flex-caption">'.get_the_title().'</p>'; }							
								if ( get_post_meta( $post->ID, "_slide_link_url", true ) ) 
									$slider .= '</a>';
							}
						
						$slider .= '<h2 class="slide-title"><a href="' . get_post_meta( $post->ID, "_slide_link_url", true ) . '" title="' . the_title_attribute ( array( 'echo' => 0 ) ) . '" >' . get_the_title() . '</a></h2>';
					
					$slider .= '</div>';				
				$slider .= '</li>';
								
			endwhile;			
			$slider .= '</ul>';			
		$slider .= '</div>'; 
	endif;
	wp_reset_query();
	return $slider;
}

// metabox created for link
add_action( 'add_meta_boxes', 'wrjs_metabox_slider' );
function wrjs_metabox_slider() {
  add_meta_box( 'wrjs_metabox_link_slider', __( "Slide Link", "wp-responsive-jquery-slider"), 'wrjs_metabox_link_slider', 'vslides', 'normal', 'default' );
}

// get link from metabox            
function wrjs_metabox_link_slider() {	
	global $post; 
	$slide_link_url = get_post_meta( $post->ID, '_slide_link_url', true ); ?>	
	<p><?php _e("URL", "wp-responsive-jquery-slider") ?>: <input type="text" style="width: 90%;" name="slide_link_url" value="<?php echo esc_attr( $slide_link_url ); ?>" /></p>
	<span class="description"><?php _e("The URL link to this slide.","wp-responsive-jquery-slider") ?></span>	
<?php }

// upadte metabox link
add_action( 'save_post', 'wrjs_update_metabox_slider', 1, 2 );
function wrjs_update_metabox_slider( $post_id, $post ) {	
	if ( isset( $_POST['slide_link_url'] ) ) {
		update_post_meta( $post_id, '_slide_link_url', esc_url( $_POST['slide_link_url'] ) );
	}	
}

add_action('do_meta_boxes', 'wrjs_edit_metabox_slider');
function wrjs_edit_metabox_slider() {
   remove_meta_box( 'postimagediv', 'vslides', 'side' );
	remove_meta_box( 'pageparentdiv', 'vslides', 'side' );
	remove_meta_box( 'hybrid-core-post-template', 'vslides', 'side' );
	remove_meta_box( 'theme-layouts-post-meta-box', 'vslides', 'side' );
   add_meta_box('postimagediv', __( "Slide Image", "wp-responsive-jquery-slider"), 'post_thumbnail_meta_box', 'vslides', 'side', 'low');
	add_meta_box('pageparentdiv', __( "Slide Order", "wp-responsive-jquery-slider"), 'page_attributes_meta_box', 'vslides', 'side', 'low');
}

add_filter( 'manage_edit-slides_columns', 'wrjs_data_slider' );
function wrjs_data_slider( $columns ) {
	$columns = array(
		'cb'       => '<input type="checkbox" />',
		'image'    => __('Image', "wp-responsive-jquery-slider"), 
		'title'    => __('Title', "wp-responsive-jquery-slider"), 
		'order'    => __('Order', "wp-responsive-jquery-slider"), 
		'link'     => __('Link', "wp-responsive-jquery-slider"), 
		'date'     => __('Date', "wp-responsive-jquery-slider")
	);
	return $columns;
}

add_action( 'manage_posts_custom_column', 'wrjs_add_data_slider' );
function wrjs_add_data_slider( $column ) {
	global $post;
	$edit_link = get_edit_post_link( $post->ID );
	if ( $column == 'image' )		
		echo '<a href="' . $edit_link . '" title="' . $post->post_title . '">' . get_the_post_thumbnail( $post->ID, array( 60, 60 ), array( 'title' => trim( strip_tags(  $post->post_title ) ) ) ) . '</a>';
	if ( $column == 'order' )		
		echo '<a href="' . $edit_link . '">' . $post->menu_order . '</a>';
	if ( $column == 'link' )		
		echo '<a href="' . get_post_meta( $post->ID, "_slide_link_url", true ) . '" target="_blank" >' . get_post_meta( $post->ID, "_slide_link_url", true ) . '</a>';		
}

// set order for slide
add_filter( 'pre_get_posts', 'wrjs_order_data_slider' );
function wrjs_order_data_slider($wp_query) {	
	if( is_admin() ) {		
		$post_type = $wp_query->query['post_type'];
		
		if( $post_type == 'vslides' ) {
			$wp_query->set( 'orderby', 'menu_order' );
			$wp_query->set( 'order', 'ASC' );
		}
	}	
}

// settings slider link in plugin option
add_action('admin_menu', 'wrjs_settings_slider');
function wrjs_settings_slider() {
	add_submenu_page( 'edit.php?post_type=vslides', __( "Slider Settings", "wp-responsive-jquery-slider"), __( "Settings", "wp-responsive-jquery-slider"),  'manage_options', 'settings_section_slider', 'wrjs_settings_box_slider' );
}

// wp-admin settings box
function wrjs_settings_box_slider() { ?>
	<div class="wrap">		
		<?php screen_icon( 'plugins' ); ?>
		<h2><?php 'Wrjs Slider Options'; ?></h2>
		<div class="settings-banner">   <h3 class="title">WP Responsive Jquery Slider by Vivacity InfoTech</h3>   </div>
		<form method="post" action="options.php">
			<?php settings_fields( 'get_settings_option' ); ?>
			<?php do_settings_sections( 'settings_section_slider' ); ?>
			<br /><p><input type='submit' name='Submit' value='<?php _e( "Update Settings", "wp-responsive-jquery-slider") ?>' class='button-primary' /></p>
			<br />
		</form>		
	</div>	
<?php }

add_action( 'admin_init', 'wrjs_settings_start' );
function wrjs_settings_start() {
	register_setting( 'get_settings_option', 'get_settings_option', 'wrjs_check_slider' );
	add_settings_section( 'get_option_change_value', ' ', '', 'settings_section_slider' );
	add_settings_field( 'width_wrjs', __( "Slide width:", "wp-responsive-jquery-slider"), 'width_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'caption_wrjs', __( "Slider caption:", "wp-responsive-jquery-slider"), 'caption_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'height_wrjs', __( "Slide height:", "wp-responsive-jquery-slider"), 'height_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'delay_wrjs', __( "Slide Delay:", "wp-responsive-jquery-slider"), 'delay_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'duration_wrjs',  __( "Slide duration:", "wp-responsive-jquery-slider"), 'duration_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'post_wrjs', __( "Choose post:", "wp-responsive-jquery-slider"), 'post_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'effect_wrjs', __( "Slide Effect:", "wp-responsive-jquery-slider"), 'effect_wrjs', 'settings_section_slider', 'get_option_change_value' ); 
        add_settings_field( 'show_panel_nav_wrjs', __( "Show Slider Navigation Arrows:", "wp-responsive-jquery-slider"), 'show_panel_nav_wrjs', 'settings_section_slider', 'get_option_change_value' );
	add_settings_field( 'start_wrjs', __( "Start Automatically:", "wp-responsive-jquery-slider"), 'start_wrjs', 'settings_section_slider', 'get_option_change_value' );
        add_settings_field( 'pauseOnHover_wrjs', __( "Pause On Hover:", "wp-responsive-jquery-slider"), 'pauseOnHover_wrjs', 'settings_section_slider', 'get_option_change_value' );
}

function width_wrjs() {
	$options = get_option( 'get_settings_option' );
	$width_wrjs = $options['width_wrjs'];
 ?>
	<input type="text" id="width_wrjs" name="get_settings_option[width_wrjs]" value="<?php echo $width_wrjs; ?>" /> <span class="description"><?php  'px'; ?></span>	
<?php }

function caption_wrjs() {
 $options = get_option( 'get_settings_option' );
 $caption_wrjs = $options['caption_wrjs'];  
 ?>
<input type="radio" name="get_settings_option[caption_wrjs]" <?php if($caption_wrjs=='1'){?> checked=checked <?php }?>  value="1"><?php _e( "Yes", "wp-responsive-jquery-slider"); ?> <br>
<input type="radio" name="get_settings_option[caption_wrjs]" <?php if($caption_wrjs=='0'){?> checked=checked <?php }?>  value="0"><?php _e( "No", "wp-responsive-jquery-slider"); ?> 
<span class="description"><?php  'px'; ?></span>	
<?php }

function height_wrjs() {
	$options = get_option( 'get_settings_option' );
	$height_wrjs = $options['height_wrjs'];
?>
	<input type="text" id="height_wrjs" name="get_settings_option[height_wrjs]" value="<?php echo $height_wrjs; ?>" /> <span class="description"><?php  'px'; ?></span>
<?php }

function delay_wrjs() {
	$options = get_option( 'get_settings_option' );
	$delay_wrjs = $options['delay_wrjs'];
?>
	<input type="text" id="delay_wrjs" name="get_settings_option[delay_wrjs]" value="<?php echo $delay_wrjs; ?>" /> <span class="description"><?php 'milliseconds'; ?></span>	
<?php }

function duration_wrjs() {
	$options = get_option( 'get_settings_option' );
	$duration_wrjs = $options['duration_wrjs'];
 ?>
	<input type="text" id="duration_wrjs" name="get_settings_option[duration_wrjs]" value="<?php echo $duration_wrjs; ?>" /> <span class="description"><?php 'milliseconds'; ?></span>	
<?php }

function post_wrjs() {
	$options = get_option( 'get_settings_option' );
	$post_wrjs = $options['post_wrjs'];
	echo "<select id='post_wrjs' name='get_settings_option[post_wrjs]'>";
	echo '<option value="post" ' . selected( $post_wrjs, 'post', false ) . ' >' .'post'. '</option>';
	echo '<option value="vslide" ' . selected( $post_wrjs, 'vslide', false ) . ' >' .'vslide'. '</option>';
	echo '</select>';	
}

function effect_wrjs() {
	$options = get_option( 'get_settings_option' );
	$effect_wrjs = $options['effect_wrjs'];
	echo "<select id='effect_wrjs' name='get_settings_option[effect_wrjs]'>";
	echo '<option value="fade" ' . selected( $effect_wrjs, 'fade', false ) . ' >' .'fade'. '</option>';
	echo '<option value="slide" ' . selected( $effect_wrjs, 'slide', false ) . ' >' .'slide'. '</option>';
	echo '</select>';	
}
 
function show_panel_nav_wrjs() {
	$options = get_option( 'get_settings_option' );
	$show_panel_nav_wrjs = $options['show_panel_nav_wrjs'];
	echo "<select id='show_panel_nav_wrjs' name='get_settings_option[show_panel_nav_wrjs]'>";
	echo '<option value="1" ' . selected( $show_panel_nav_wrjs, '1', false ) . ' >' . __( "Yes", "wp-responsive-jquery-slider" ). '</option>';
	echo '<option value="0" ' . selected( $show_panel_nav_wrjs, '0', false ) . ' >' . __( "No", "wp-responsive-jquery-slider" ) . '</option>';
	echo '</select>';	
}

function pauseOnHover_wrjs() {
	$options = get_option( 'get_settings_option' );
	$pauseOnHover_wrjs = $options['pauseOnHover_wrjs']; 
	echo "<select id='pauseOnHover_wrjs' name='get_settings_option[pauseOnHover_wrjs]'>";
	echo '<option value="1" ' . selected( $pauseOnHover_wrjs, '1', false ) . ' >' . __( "Yes", "wp-responsive-jquery-slider" ). '</option>';
	echo '<option value="0" ' . selected( $pauseOnHover_wrjs, '0', false ) . ' >' . __( "No", "wp-responsive-jquery-slider" ) . '</option>';
	echo '</select>';	
 }

function start_wrjs() {
	$options = get_option( 'get_settings_option' );
	$start_wrjs = $options['start_wrjs'];
	echo "<input type='checkbox' id='start_wrjs' name='get_settings_option[start_wrjs]' value='1' " . checked( $start_wrjs, 1, false ) . " />";	
}

function wrjs_check_slider( $input ) {	
	$options = get_option( 'get_settings_option' );	
	$options['width_wrjs'] = wp_filter_nohtml_kses( intval( $input['width_wrjs'] ) );
$options['caption_wrjs'] = wp_filter_nohtml_kses( intval( $input['caption_wrjs'] ) );	
	
	$options['height_wrjs'] = wp_filter_nohtml_kses( intval( $input['height_wrjs'] ) );
	$options['effect_wrjs'] = wp_filter_nohtml_kses( $input['effect_wrjs'] );
	$options['direction_wrjs'] = wp_filter_nohtml_kses( $input['direction_wrjs'] );
	$options['post_wrjs'] = wp_filter_nohtml_kses( $input['post_wrjs'] );
        $options['show_panel_nav_wrjs'] = wp_filter_nohtml_kses( $input['show_panel_nav_wrjs'] );
	$options['delay_wrjs'] = wp_filter_nohtml_kses( intval( $input['delay_wrjs'] ) );
	$options['duration_wrjs'] = wp_filter_nohtml_kses( intval( $input['duration_wrjs'] ) );
	$options['start_wrjs'] = isset( $input['start_wrjs'] ) ? 1 : 0;
        $options['pauseOnHover_wrjs'] = wp_filter_nohtml_kses( $input['pauseOnHover_wrjs'] );	
	
	return $options;
}
// default option for slider
function wrjs_defult_setting_slider() {

	$ex_options = get_option( 'get_settings_option' );

	if ( !is_array( $ex_options ) || $ex_options['duration_wrjs'] == '' ) {

		$default_options = array(	
			'width_wrjs'     => '960',
			'caption_wrjs'  => 'yes',
			'height_wrjs'    => '350',
			'effect_wrjs'    => 'slide',
			'direction_wrjs'    => 'vertical',
			'post_wrjs'    => 'vslide',
			'delay_wrjs'     => '5000',
			'duration_wrjs'  => '600',
         'show_panel_nav_wrjs' => '1',
			'start_wrjs'     => 1,
         'pauseOnHover_wrjs' => '1'		
		);	

		update_option( 'get_settings_option', $default_options );
	}	
}
?>