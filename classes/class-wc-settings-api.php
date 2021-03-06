<?php
/**
 * Admin Settings API used by Shipping Methods and Payment Gateways
 *
 * @class 		WC_Settings_API
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Settings_API {

	/** @var string The plugin ID. Used for option names. */
	var $plugin_id = 'woocommerce_';

	/** @var array Array of setting values. */
	var $settings = array();

	/** @var array Array of form option fields. */
	var $form_fields = array();

	/** @var array Array of validation errors. */
	var $errors = array();

	/** @var array Sanitized fields after validation. */
	var $sanitized_fields = array();


	/**
	 * Admin Options
	 *
	 * Setup the gateway settings screen.
	 * Override this in your gateway.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	function admin_options() { ?>
		<h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings','woocommerce' ) ; ?></h3>

		<?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><?php
	}

	/**
	 * Initialise Settings Form Fields
	 *
	 * Add an array of fields to be displayed
	 * on the gateway's settings screen.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	function init_form_fields() {
		return __( 'This function needs to be overridden by your payment gateway class.', 'woocommerce' );
	}

	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool
	 */
    public function process_admin_options() {
    	$this->validate_settings_fields();

    	if ( count( $this->errors ) > 0 ) {
    		$this->display_errors();
    		return false;
    	} else {
    		update_option( $this->plugin_id . $this->id . '_settings', $this->sanitized_fields );
    		return true;
    	}
    }

    /**
     * Display admin error messages.
     *
     * @since 1.0.0
	 * @access public
	 * @return void
	 */
    function display_errors() {}

	/**
     * Initialise Gateway Settings
     *
     * Store all settings in a single database entry
     * and make sure the $settings array is either the default
     * or the settings stored in the database.
     *
     * @since 1.0.0
     * @uses get_option(), add_option()
	 * @access public
	 * @return void
	 */
    function init_settings() {

    	// Load form_field settings
    	if ( $this->form_fields ) {

    		$form_field_settings = ( array ) get_option( $this->plugin_id . $this->id . '_settings' );

	    	if ( ! $form_field_settings ) {

	    		// If there are no settings defined, load defaults
	    		foreach ( $this->form_fields as $k => $v )
	    			$form_field_settings[ $k ] = isset( $v['default'] ) ? $v['default'] : '';

	    	} else {

		    	// Prevent "undefined index" errors.
		    	foreach ( $this->form_fields as $k => $v )
    				$form_field_settings[ $k ] = isset( $form_field_settings[ $k ] ) ? $form_field_settings[ $k ] : ( isset( $v['default'] ) ? $v['default'] : '' );

	    	}

	    	// Set and decode escaped values
	    	$this->settings = array_map( array( &$this, 'format_settings' ), $form_field_settings );
    	}

    	if ( isset( $this->settings['enabled'] ) && ( $this->settings['enabled'] == 'yes' ) )
    		$this->enabled = 'yes';

    }


    /**
     * Decode values for settings.
     *
     * @access public
     * @param mixed $value
     * @return array
     */
    function format_settings( $value ) {
    	return ( is_array( $value ) ) ? $value : html_entity_decode( $value );
    }


    /**
     * Generate Settings HTML.
     *
     * Generate the HTML for the fields on the "settings" screen.
     *
     * @access public
     * @param bool $form_fields (default: false)
     * @since 1.0.0
     * @uses method_exists()
	 * @access public
	 * @return string the html for the settings
     */
    function generate_settings_html ( $form_fields = false ) {

    	if ( ! $form_fields )
    		$form_fields = $this->form_fields;

    	$html = '';
    	foreach ( $form_fields as $k => $v ) {
    		if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) { $v['type'] == 'text'; } // Default to "text" field type.

    		if ( method_exists( $this, 'generate_' . $v['type'] . '_html' ) ) {
    			$html .= $this->{'generate_' . $v['type'] . '_html'}( $k, $v );
    		} else {
	    		$html .= $this->{'generate_text_html'}( $k, $v );
    		}
    	}

    	echo $html;
    }


    /**
     * Generate Text Input HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    function generate_text_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	$data['placeholder'] 	= isset( $data['placeholder'] ) ? $data['placeholder'] : '';
    	$data['type'] 			= isset( $data['type'] ) ? $data['type'] : 'text';
    	
    	// Custom attribute handling
		$custom_attributes = array();
		
		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';
			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
                $value = ( isset( $this->settings[ $key ] ) ) ? esc_attr( $this->settings[ $key ] ) : '';
				$html .= '<input class="input-text regular-input ' . esc_attr( $data['class'] ) . '" type="' . esc_attr( $data['type'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" value="' . $value . '" placeholder="' . esc_attr( $data['placeholder'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' />';
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= ' <p class="description">' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Password Input HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    function generate_password_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	
    	// Custom attribute handling
		$custom_attributes = array();
		
		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';
			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
                $value = ( isset( $this->settings[ $key ] ) ) ? esc_attr( $this->settings[ $key ] ) : '';
				$html .= '<input class="input-text regular-input ' . esc_attr( $data['class'] ) . '" type="password" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" value="' . $value . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' />';
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= ' <p class="description">' . esc_attr( $data['description'] ) . '</p>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Textarea HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    function generate_textarea_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	if ( ! isset( $this->settings[$key] ) ) $this->settings[$key] = '';
    	$data['class']			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	
    	// Custom attribute handling
		$custom_attributes = array();
		
		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';
			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
                $value = ( isset( $this->settings[ $key ] ) ) ? esc_textarea( $this->settings[ $key ] ) : '';
				$html .= '<textarea rows="3" cols="20" class="input-text wide-input ' . esc_attr( $data['class'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . '>' . $value . '</textarea>';
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= ' <p class="description">' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Checkbox HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    function generate_checkbox_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['label']			= isset( $data['label'] ) ? $data['label'] : $data['title'];
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class'] 		= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 		= isset( $data['css'] ) ? $data['css'] : '';
    	
    	// Custom attribute handling
		$custom_attributes = array();
		
		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">' . $data['title'] . '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">';
				$html .= '<input style="' . esc_attr( $data['css'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" type="checkbox" value="1" ' . checked( $this->settings[$key], 'yes', false ) . ' class="' . esc_attr( $data['class'] ).'" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' /> ' . wp_kses_post( $data['label'] ) . '</label><br />' . "\n";
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= ' <p class="description">' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Select HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    function generate_select_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['options'] 		= isset( $data['options'] ) ? (array) $data['options'] : array();
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	
    	// Custom attribute handling
		$custom_attributes = array();
		
		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';
			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<select name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" class="select ' .esc_attr( $data['class'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . '>';

				foreach ($data['options'] as $option_key => $option_value) :
					$html .= '<option value="' . esc_attr( $option_key ) . '" '.selected($option_key, esc_attr($this->settings[$key]), false).'>' . esc_attr( $option_value ) . '</option>';
				endforeach;

				$html .= '</select>';
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= ' <p class="description">' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Multiselect HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    function generate_multiselect_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['options'] 		= isset( $data['options'] ) ? (array) $data['options'] : array();
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	
    	// Custom attribute handling
		$custom_attributes = array();
		
		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';
			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<select multiple="multiple" style="' . esc_attr( $data['css'] ) . '" class="multiselect ' . esc_attr( $data['class'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '[]" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . '>';

				foreach ( $data['options'] as $option_key => $option_value) {
					$html .= '<option value="' . esc_attr( $option_key ) . '" ';
					if ( isset( $this->settings[ $key ] ) && in_array( $option_key, (array) $this->settings[ $key ] ) ) $html .= 'selected="selected"';
					$html .= '>' . esc_attr( $option_value ) . '</option>';
				}

				$html .= '</select>';
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= '<p class="description">' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

	/**
     * Generate Title HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.6.2
     * @return string
     */
	function generate_title_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';

		$html .= '</table>' . "\n";
			$html .= '<h4 class="' . esc_attr( $data['class'] ) . '">' . wp_kses_post( $data['title'] ) . '</h4>' . "\n";
			if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= '<p>' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
		$html .= '<table class="form-table">' . "\n";

    	return $html;
    }


    /**
     * Validate Settings Field Data.
     *
     * Validate the data on the "Settings" form.
     *
     * @since 1.0.0
     * @uses method_exists()
     * @param bool $form_fields (default: false)
     * @return void
     */
    function validate_settings_fields( $form_fields = false ) {

    	if ( ! $form_fields )
    		$form_fields = $this->form_fields;

    	$this->sanitized_fields = array();

    	foreach ( $form_fields as $k => $v ) {
    		if ( empty( $v['type'] ) ) 
    			$v['type'] == 'text'; // Default to "text" field type.

    		if ( method_exists( $this, 'validate_' . $v['type'] . '_field' ) ) {
    			$field = $this->{'validate_' . $v['type'] . '_field'}( $k );
    			$this->sanitized_fields[ $k ] = $field;
    		} else {
    			$this->sanitized_fields[ $k ] = $this->settings[ $k ];
    		}
    	}
    }


    /**
     * Validate Checkbox Field.
     *
     * If not set, return "no", otherwise return "yes".
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    function validate_checkbox_field( $key ) {
    	$status = 'no';
    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) && ( 1 == $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$status = 'yes';
    	}

    	return $status;
    }

    /**
     * Validate Text Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    function validate_text_field( $key ) {
    	$text = ( isset( $this->settings[ $key ] ) ) ? $this->settings[ $key ] : '';

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$text = esc_attr( trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) );
    	}

    	return $text;
    }


    /**
     * Validate Password Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    function validate_password_field( $key ) {
    	$text = (isset($this->settings[$key])) ? $this->settings[$key] : '';

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$text = esc_attr( woocommerce_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
    	}

    	return $text;
    }


    /**
     * Validate Textarea Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    function validate_textarea_field( $key ) {
    	$text = ( isset( $this->settings[ $key ] ) ) ? $this->settings[ $key ] : '';

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$text = esc_attr( trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) );
    	}

    	return $text;
    }


    /**
     * Validate Select Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    function validate_select_field( $key ) {
    	$value = ( isset( $this->settings[ $key ] ) ) ? $this->settings[ $key ] : '';

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$value = esc_attr( woocommerce_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
    	}

    	return $value;
    }

    /**
     * Validate Multiselect Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    function validate_multiselect_field( $key ) {
    	$value = ( isset( $this->settings[ $key ] ) ) ? $this->settings[ $key ] : '';

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$value = array_map('esc_attr', array_map('woocommerce_clean', (array) $_POST[ $this->plugin_id . $this->id . '_' . $key ] ));
    	} else {
	    	$value = '';
    	}

    	return $value;
    }

}