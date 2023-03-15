<?php


class wpda_form_gutenberg{	
	
	function __construct(){
		$this->hooks_for_gutenberg();
	}

	/*###################### Hooks for Gutenberg ##################*/	
	
	private function hooks_for_gutenberg(){
		add_action( 'init', array($this,'guthenberg_init') );
	}

	/*###################### Gutenberg function ##################*/		
	
	public function guthenberg_init(){
		if ( ! function_exists( 'register_block_type' ) ) {
		// Gutenberg is not active.
		return;
		}
		register_block_type( 'wpdevart-form/form', array(
			'style' => 'wpda_form_gutenberg_css',
			'editor_script' => 'wpda_form_gutenberg_js',
		) );
		wp_add_inline_script(
			'wpda_form_gutenberg_js',
			sprintf('var wpda_form_gutenberg = { forms: %s, other_data: %s};', json_encode($this->get_forms(),JSON_PRETTY_PRINT),  json_encode($this->other_dates(),JSON_PRETTY_PRINT)),
			'before'
		);
	}
	
	/*###################### Get forms function ##################*/
	
	private function get_forms(){
		
		global $wpdb;
		global $wpda_form_table;      
		$wpdevart_forms = $wpdb->get_results("SELECT * FROM ".$wpda_form_table['wpdevart_forms']);
		$array=array();
		foreach ($wpdevart_forms as $key => $form ){
			$array[$form->id]=$form->name;	
		}
		return $array;
	}
	
	/*###################### Function for the other dates ##################*/		
	
	private function other_dates(){
		$array=array('icon_src'=>wpda_form_PLUGIN_URI."assets/images/tinmce_content.png");
		return $array;
	}
	
}

