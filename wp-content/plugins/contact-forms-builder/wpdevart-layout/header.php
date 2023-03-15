<?php 
if ( ! defined( 'ABSPATH' ) ) exit;
$header_title = "";
	if( isset($_GET['page']) ) {
		$page = sanitize_text_field($_GET['page']);
		$chunks = explode( '-', $page);
		if( $chunks[0] == 'wpdevart' ) {
			if($chunks[1] == "options") $header_title = "Theme";
			if($chunks[1] == "demo") $header_title = "Theme";
			if($chunks[1] == "system") $header_title = "Theme";
			if($chunks[1] == "forms") $header_title = "Forms";
			if($chunks[1] == "sliders") $header_title = "Sliders";				
		}
	}
?>
<div class="wpdevart_plugins_header div-for-clear">
	<div class="wpdevart_plugins_get_pro div-for-clear">
		<div class="wpdevart_plugins_get_pro_info">
			<h3>WpDevArt Contact Form Premium</h3>
			<p>Powerful and Customizable Contact Form</p>
		</div>
			<a target="blank" href="https://wpdevart.com/wordpress-contact-form-plugin/" class="wpdevart_upgrade">Upgrade</a>
	</div>
	<a target="blank" href="<?php echo wpdevart_contactform_support_url; ?>" class="wpdevart_support">Have any Questions? Get a quick support!</a>
</div>
<header class="header">
    <div class="main-bar">
        <div class="col-sm-5">
            <div class="logo"><a href="https://wpdevart.com/" target="_blank"><img src=<?php echo wpda_form_PLUGIN_URI . "assets/images/logo.png"; ?> alt="wpdevart Forms"></a></div>
        </div>
        <div class="col-sm-7">
            <div class="theme-info">
                
            </div><!-- .theme-info -->
        </div>
        <div class="clearfix"></div>
    </div>
	<?php
		if( wp_get_theme() == 'wpdevart' || wp_get_theme() == 'wpdevart Child' || wp_get_theme() == 'wpdevart Theme' || wp_get_theme() == 'wpdevart Child Theme' ) {
				//	If the WpDevArt contact form theme is active, then it will not add a sidebar menu
				//	WpDevArt form theme automatically adds sidebar menu items under the WpDevArt tab
	?>
    <div class="lower-bar">
        <ul class="options-links-tabs">
            <li <?php echo $active_class = ($chunks[1] == "options") ? "class='active'" : ""; ?>><a href="<?php echo admin_url() .'admin.php?page=wpdevart-options' ?>">THEME Options</a></li>
            <?php if (class_exists('wpdevartForms')) { ?>
            <li <?php echo $active_class = ($chunks[1] == "forms") ? "class='active'" : ""; ?>><a href="<?php echo admin_url() .'admin.php?page=wpdevart-forms-list' ?>">wpdevart Forms</a></li>
			<?php } if (class_exists('wpdevartSliders')) { ?>
            <li <?php echo $active_class = ($chunks[1] == "sliders") ? "class='active'" : ""; ?>><a href="<?php echo admin_url() .'admin.php?page=wpdevart-sliders-list' ?>">wpdevart Sliders</a></li>
			<?php } ?>
            <li <?php echo $active_class = ($chunks[1] == "demo") ? "class='active'" : ""; ?>><a href="<?php echo admin_url() .'admin.php?page=wpdevart-demo-importer' ?>">DEMO Importer</a></li>
            <li <?php echo $active_class = ($chunks[1] == "system") ? "class='active'" : ""; ?>><a href="<?php echo admin_url() .'admin.php?page=wpdevart-system-status' ?>#">SYSTEM Status</a></li>
        </ul>
        <div class="clearfix"></div>
    </div>
	<?php } ?>
</header><!-- / .WpDevArt-header -->