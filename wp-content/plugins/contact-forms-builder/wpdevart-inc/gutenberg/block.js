(function( blocks,  element ) {
	var el = element.createElement;
	var icon_iamge = el( 'img', {
      width: 24,
      height: 24,
      src: window['wpda_form_gutenberg']["other_data"]["icon_src"],
	  className: "wpdevart_form_icon"
    } );
	blocks.registerBlockType( 'wpdevart-form/form', {
		title: 'WpDevArt Contact Form',
		icon: icon_iamge ,
		category: 'common',
		attributes: {			
			forms: {
				type: 'string',
				selector: 'select',
			}
		},
		edit: function( props ) {
			var attributes = props.attributes;
			var form_options=new Array();
			var selected_option=false;
			
			for(var key in wpda_form_gutenberg["forms"]) {
				selected_option=false;
				if(typeof(attributes.forms)=="undefined"){					
					props.setAttributes( { forms: key })
					attributes.forms=key;
				}else{
					if(props.attributes.forms==key){
						selected_option=true;
					}
				}
				form_options.push(el('option',{value:''+key+'',selected:selected_option},wpda_form_gutenberg["forms"][key]))
			}
			if(form_options.length){
				return (
					el( 'div', { className: props.className },				   
					  el( 'div', { className: "wpdevart_gutenberg_form_main_div"},
						el( 'span', { },"Wpdevart Contact Form"),
						el( 'br'),
						el( 'label', { },"Select a form"),
						el( 'select', { className: "wpdevart_gutenberg_form_css",onChange: function( value ) {var select=value.target; props.setAttributes( { forms: select.options[select.selectedIndex].value })}},form_options),
					  )
					)
				);
			}else{
			return	el( 'div', { className: props.className },
				  el( 'div', { className: "wpdevart_gutenberg_timer_main_div"},
					 el( 'span', { },"Wpdevart Contact Form"),
					 el( 'br'),
					 el( 'label', { },"There is not any form created yet. Please create a form from WpDevArt Forms, then a list of forms will be displayed here.")
				  ))
			}
			
		},
		save: function( props ) {			
			return "[wpdevart_forms id=\""+props.attributes.forms+"\"]";
		}

	} )
} )(
	window.wp.blocks,
	window.wp.element
);

