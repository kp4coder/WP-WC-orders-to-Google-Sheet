<?php 
class WC_Settings_SpreadSheet {

    function __construct() {

        // add a tab in woocommerce setting page.
        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );

        // add and update fields
        add_action( 'woocommerce_settings_tabs_spreadsheet', array( $this, 'settings_tab' ) );
        add_action( 'woocommerce_update_options_spreadsheet', array( $this, 'update_settings' ) );

        // include script or style
        add_action( 'admin_enqueue_scripts',  array( $this, 'custom_reordering_product_tabs_action' ) );
    }
    
    // add a tab in woocommerce setting page.
    function add_settings_tab( $settings_tabs ) {
        $settings_tabs['spreadsheet'] = __( 'SpreadSheet', WOGS_text_domain );
        return $settings_tabs;
    }

    function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    function get_settings() {

        $settings = array(
            'section_title' => array(
                'name'     => __( 'SpreadSheet', WOGS_text_domain ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_spreadsheet_section_title'
            ),
            'client_id' => array(
                'name' => __( 'Client ID', WOGS_text_domain ),
                'type' => 'text',
                'desc' => '',
                'id'   => 'wc_spreadsheet_client_id'
            ),
            'project_id' => array(
                'name' => __( 'Project ID', WOGS_text_domain ),
                'type' => 'text',
                'desc' => '',
                'id'   => 'wc_spreadsheet_project_id'
            ),
            'wc_spreadsheet_client_secret' => array(
                'name' => __( 'Client Secret', WOGS_text_domain ),
                'type' => 'text',
                'desc' => __( 'Add redirect URIs -', WOGS_text_domain ) . get_site_url() . '/?wogs=google_auth 
                        <br/><br/><button id="authenticate" data-url="'.get_site_url() . '/?wogs=google_auth&close=no">'.__( 'Google Authenticate', WOGS_text_domain ).'</button>',
                'id'   => 'wc_spreadsheet_client_secret'
            ),
            'title' => array(
                'name' => __( 'SpreadSheet ID', WOGS_text_domain ),
                'type' => 'text',
                'desc' => __( 'You will find spreadsheet ID in your spreadsheet url.<br/>
                Like : https://docs.google.com/spreadsheets/d/<b>1LswLxasIekcSuf12qy8FVh3sdfWhSj5SDFWER4_vM</b>/edit?ts=603d2d18<br/>
                On above URL <b>1LswLxasIekcSuf12qy8FVh3sdfWhSj5SDFWER4_vM</b> is Spreadsheet ID.', WOGS_text_domain ),
                'id'   => 'wc_spreadsheet_title'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_spreadsheet_section_end'
            )
        );

        return apply_filters( 'wc_spreadsheet_settings', $settings );
    }

    function custom_reordering_product_tabs_action() {
         // Only in single product pages and a specific url (using GET method) 
        if( isset( $_GET['page'] ) && $_GET['page'] == "wc-settings" && isset( $_GET['tab'] ) && $_GET['tab'] = "spreadsheet" ) :
            wp_register_script( 'wogs_script', WOGS_PLUGIN_URL.'wogs_script.js?rand='.rand(1,9), 'jQuery', '1.0', true );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wogs_script' );
        endif;
    }

}

$test = new WC_Settings_SpreadSheet();