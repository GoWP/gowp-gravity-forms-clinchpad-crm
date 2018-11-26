<?php

GFForms::include_addon_framework();

class GFClinchPad extends GFAddOn {

	protected $_version = GF_CLINCHPAD_CRM_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'gravity-forms-clinchpad';
	protected $_path = 'gravity-forms-clinchpad/gravity-forms-clinchpad.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms ClinchPad CRM Add-On';
	protected $_short_title = 'ClinchPad CRM';
	protected $_api_base_url = 'https://www.clinchpad.com/api/v1';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFClinchPad
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFClinchPad();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'fields' => array(
					array(
						'name'              => 'apikey',
						'tooltip'           => esc_html__( 'API key', 'clinchpad' ),
						'label'             => esc_html__( 'API key', 'clinchpad' ),
						'type'              => 'text',
						'class'             => 'small',
					)
				)
			)
		);
	}

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		return array(
			array(
				'title'  => esc_html__( 'ClinchPad CRM Settings', 'clinchpad' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Enabled', 'clinchpad' ),
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'tooltip' => esc_html__( 'Enable this integration?', 'clinchpad' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'clinchpad' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label' => esc_html__( 'Conditional Logic', 'clinchpad' ),
						'type'  => 'custom_logic_type',
						'name'  => 'custom_logic',
					),
					array(
						'label' => esc_html__( 'Name (required)', 'clinchpad' ),
						'type'  => 'field_select',
						'name'  => 'name',
						'tooltip' => esc_html__( 'Name of the contact', 'clinchpad' ),
					),
					array(
						'label' => esc_html__( 'Designation (optional)', 'clinchpad' ),
						'type'  => 'field_select',
						'name'  => 'designation',
						'tooltip' => esc_html__( 'Designation of the contact', 'clinchpad' ),
					),
					array(
						'label' => esc_html__( 'Email (optional)', 'clinchpad' ),
						'type'  => 'field_select',
						'name'  => 'email',
						'tooltip' => esc_html__( 'Email of the contact', 'clinchpad' ),
					),
					array(
						'label' => esc_html__( 'Phone Number (optional)', 'clinchpad' ),
						'type'  => 'field_select',
						'name'  => 'phone',
						'tooltip' => esc_html__( 'Phone number of the contact', 'clinchpad' ),
					),
					array(
						'label' => esc_html__( 'Address (optional)', 'clinchpad' ),
						'type'  => 'field_select',
						'name'  => 'address',
						'tooltip' => esc_html__( 'Address of the contact', 'clinchpad' ),
					),
					array(
						'label' => esc_html__( 'Organization ID (optional)', 'clinchpad' ),
						'type'  => 'field_select',
						'name'  => 'organization_id',
						'tooltip' => esc_html__( 'Unique identifier of the organization the contact belongs to', 'clinchpad' ),
					),
				),
			),
		);
	}

	// # SIMPLE CONDITION EXAMPLE --------------------------------------------------------------------------------------

	/**
	 * Define the markup for the custom_logic_type type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 */
	public function settings_custom_logic_type( $field, $echo = true ) {

		// Get the setting name.
		$name = $field['name'];

		// Define the properties for the checkbox to be used to enable/disable access to the simple condition settings.
		$checkbox_field = array(
			'name'    => $name,
			'type'    => 'checkbox',
			'choices' => array(
				array(
					'label' => esc_html__( 'Enable conditional logic', 'clinchpad' ),
					'name'  => $name . '_enabled',
				),
			),
			'onclick' => "if(this.checked){jQuery('#{$name}_condition_container').show();} else{jQuery('#{$name}_condition_container').hide();}",
		);

		// Determine if the checkbox is checked, if not the simple condition settings should be hidden.
		$is_enabled      = $this->get_setting( $name . '_enabled' ) == '1';
		$container_style = ! $is_enabled ? "style='display:none;'" : '';

		// Put together the field markup.
		$str = sprintf( "%s<div id='%s_condition_container' %s>%s</div>",
			$this->settings_checkbox( $checkbox_field, false ),
			$name,
			$container_style,
			$this->simple_condition( $name )
		);

		echo $str;
	}

	/**
	 * Build an array of choices containing fields which are compatible with conditional logic.
	 *
	 * @return array
	 */
	public function get_conditional_logic_fields() {
		$form   = $this->get_current_form();
		$fields = array();
		foreach ( $form['fields'] as $field ) {
			if ( $field->is_conditional_logic_supported() ) {
				$inputs = $field->get_entry_inputs();

				if ( $inputs ) {
					$choices = array();

					foreach ( $inputs as $input ) {
						if ( rgar( $input, 'isHidden' ) ) {
							continue;
						}
						$choices[] = array(
							'value' => $input['id'],
							'label' => GFCommon::get_label( $field, $input['id'], true )
						);
					}

					if ( ! empty( $choices ) ) {
						$fields[] = array( 'choices' => $choices, 'label' => GFCommon::get_label( $field ) );
					}

				} else {
					$fields[] = array( 'value' => $field->id, 'label' => GFCommon::get_label( $field ) );
				}

			}
		}

		return $fields;
	}

	/**
	 * Evaluate the conditional logic.
	 *
	 * @param array $form The form currently being processed.
	 * @param array $entry The entry currently being processed.
	 *
	 * @return bool
	 */
	public function is_custom_logic_met( $form, $entry ) {
		if ( $this->is_gravityforms_supported( '2.0.7.4' ) ) {
			// Use the helper added in Gravity Forms 2.0.7.4.

			return $this->is_simple_condition_met( 'custom_logic', $form, $entry );
		}

		// Older version of Gravity Forms, use our own method of validating the simple condition.
		$settings = $this->get_form_settings( $form );

		$name       = 'custom_logic';
		$is_enabled = rgar( $settings, $name . '_enabled' );

		if ( ! $is_enabled ) {
			// The setting is not enabled so we handle it as if the rules are met.

			return true;
		}

		// Build the logic array to be used by Gravity Forms when evaluating the rules.
		$logic = array(
			'logicType' => 'all',
			'rules'     => array(
				array(
					'fieldId'  => rgar( $settings, $name . '_field_id' ),
					'operator' => rgar( $settings, $name . '_operator' ),
					'value'    => rgar( $settings, $name . '_value' ),
				),
			)
		);

		return GFCommon::evaluate_conditional_logic( $logic, $form, $entry );
	}

	/**
	 * Performing a custom action at the end of the form submission process.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */
	public function after_submission( $entry, $form ) {
		$settings = $this->get_form_settings( $form );
		$apikey = $this->get_plugin_setting( 'apikey' );
		if (
			( isset( $settings['enabled'] ) && ( true == $settings['enabled'] ) ) && 
			( ! empty( $settings['name'] ) ) &&
			( ! empty( $apikey ) ) &&
			( $this->is_custom_logic_met( $form, $entry ) )
		) {
			if ( ! empty( rgar( $entry, $settings['name'] ) ) ) {
				$fields['name'] = rgar( $entry, $settings['name'] );	
			} else {
				$subs = array(
					'.2', // prefix
					'.3', // first
					'.4', // middle
					'.6', // last
					'.8', // suffix
				);
				foreach ( $subs as $sub ) {
					$name[] = rgar( $entry, $settings['name'].$sub );
				}
				$fields['name'] = implode( " ", array_filter( $name ) );
			}
			$fields['designation'] = ( ! empty( $settings['designation'] ) ) ? rgar( $entry, $settings['designation'] ) : NULL;
			$fields['email'] = ( ! empty( $settings['email'] ) ) ? rgar( $entry, $settings['email'] ) : NULL;
			$fields['phone'] = ( ! empty( $settings['phone'] ) ) ? rgar( $entry, $settings['phone'] ) : NULL;
			if ( ! empty( rgar( $entry, $settings['address'] ) ) ) {
				$fields['address'] = rgar( $entry, $settings['address'] );	
			} else {
				$subs = array(
					'.1', // street1
					'.2', // street2
					'.3', // city
					'.4', // state
					'.5', // zip
					'.6', // country
				);
				foreach ( $subs as $sub ) {
					$address[] = rgar( $entry, $settings['address'].$sub );
				}
				$fields['address'] = implode( " ", array_filter( $address ) );
			}
			$fields['organization_id'] = ( ! empty( $settings['organization_id'] ) ) ? rgar( $entry, $settings['organization_id'] ) : NULL;
			$args = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( "api-key:{$apikey}" ),
				),
			);
			$url = $this->_api_base_url . "/contacts?size=1&email={$fields['email']}";
			$response = wp_remote_get( $url, $args );
			if ( ! is_wp_error( $response ) && is_array( $response ) && ( '200' == wp_remote_retrieve_response_code( $response ) ) ) {
				$args['body'] = $fields;
				$contacts = json_decode( wp_remote_retrieve_body( $response ) );
				$contact = array_shift( $contacts );
				if ( empty( $contact ) ) {
					$url = $this->_api_base_url . "/contacts";
					$response = wp_remote_post( $url, $args );
				} else {
					$url = $this->_api_base_url . "/contacts/{$contact->_id}";
					$args['method'] = 'PUT';
					$response = wp_remote_request( $url, $args );
				}
			}
		}
	}

}