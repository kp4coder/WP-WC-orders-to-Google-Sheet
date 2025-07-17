<?php
/*
Plugin Name: WC Order To Google Sheet
Plugin URI: https://etzme.org/
Description: Woocommerce orders data send to google sheet.
Version: 1.0.0
Author: kp dev
Author URI: https://wordpress.org/
Domain Path: /languages
Text Domain: WOGS_text_domain
*/

define( 'WOGS_text_domain', 'WOGS_text_domain');
define( 'WOGS_PLUGIN', '/wc_order_to_google_sheet/');
define( 'WOGS_PLUGIN_DIR', WP_PLUGIN_DIR.WOGS_PLUGIN);

define( 'WOGS_PLUGIN_URL', WP_PLUGIN_URL.WOGS_PLUGIN);

if( file_exists( WOGS_PLUGIN_DIR . "wc_setting.php" ) ) {
    include_once( WOGS_PLUGIN_DIR . "wc_setting.php" );
}

if( file_exists( WOGS_PLUGIN_DIR . "wc_category_meta.php" ) ) {
    include_once( WOGS_PLUGIN_DIR . "wc_category_meta.php" );
}

require WOGS_PLUGIN_DIR . 'SpreadsheetSnippets.php';
require WOGS_PLUGIN_DIR . '/vendor/autoload.php';

// add google api details to credentials.json file to validate api
function add_details_to_credentials() {
    $wc_spreadsheet_client_id = get_option('wc_spreadsheet_client_id'); 
    $wc_spreadsheet_project_id = get_option('wc_spreadsheet_project_id'); 
    $wc_spreadsheet_client_secret = get_option('wc_spreadsheet_client_secret'); 

    $file = WOGS_PLUGIN_DIR . 'credentials.json';
    $file_content = json_encode(
        array(
            "web" => array(
                "client_id" => $wc_spreadsheet_client_id,
                "project_id" => $wc_spreadsheet_project_id,
                "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                "token_uri" => "https://oauth2.googleapis.com/token",
                "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                "client_secret" => $wc_spreadsheet_client_secret,
                "redirect_uris" => array( 
                    get_site_url() . "/?wogs=google_auth",
                )
            )
        )
    );
    $file = fopen($file, "w") or die("can't open file");
    fwrite($file, $file_content);
    fclose($file);
}

function myArrayContainsWord(array $myArray, $word) {
    foreach ($myArray as $element) {
        if ($element->title == $word) {
            return true;
        }
    }
    return false;
}

function authenticate_and_get_token(){
    if( isset($_REQUEST['wogs']) && $_REQUEST['wogs'] == 'google_auth' ) {

        if( isset($_REQUEST['close']) && $_REQUEST['close'] == 'no' ) {
            if( file_exists(WOGS_PLUGIN_DIR . 'token.json') ) {
                unlink(WOGS_PLUGIN_DIR . 'token.json');
            }
            add_details_to_credentials();
            $client = getClient();
        } else {
            $client = getClient();
            ?>
            <script>
                window.close();
            </script>    
            <?php 
        }
        die;
    }
}
add_action( 'init', 'authenticate_and_get_token', 1, 1 );

function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API PHP Quickstart');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig(WOGS_PLUGIN_DIR . 'credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $tokenPath = WOGS_PLUGIN_DIR . 'token.json';

    if( isset($_REQUEST['code']) && !empty($_REQUEST['code']) ) {
        $accessToken = $client->fetchAccessTokenWithAuthCode($_REQUEST['code']);
        $client->setAccessToken($accessToken);
        
        $file_content = json_encode($client->getAccessToken());
        $file = fopen($tokenPath, "w") or die("can't open file");
        fwrite($file, $file_content);
        fclose($file);
    }

    // Load previously authorized token from a file, if it exists.
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

            $file_content = json_encode($client->getAccessToken());
            $file = fopen($tokenPath, "w") or die("can't open file");
            fwrite($file, $file_content);
            fclose($file);
        } else {
            $authUrl = $client->createAuthUrl();
            header('Location: ' . $authUrl);
        }
        
    }
    return $client;
}

// new order insert then add to google process
function new_order_custom_email_notification( $order_id ) {
    if ( ! $order_id ) return; 

    $order = wc_get_order( $order_id );
    $order_id = $order->get_id();
    $order_data = $order->get_data();

    $billing = $order->get_address('billing'); 
    $date = date( "Y-m-d", $order_data['date_created']->getTimestamp() );
    $customer_name = $billing['first_name'].' '.$billing['last_name'];
    $billing_address = $billing['company'].PHP_EOL.$billing['address_1'].PHP_EOL.$billing['address_2'].PHP_EOL.$billing['city'].','.$billing['state'].','.$billing['country'].','.$billing['postcode'];

    $myArr = array(
        $date,
        $customer_name,
        $order_id,
        $billing_address
    );
    
    // make action magic happen here...    https://docs.google.com/spreadsheets/d/1LSLJTxamIekcCuf79qy5FVh3yiuKbDj5UMRXKNb4_vM/edit?ts=603d2d18#gid=0
    $client = getClient();
    $service = new Google_Service_Sheets($client);
    $spreadsheetId = get_option('wc_spreadsheet_title'); 
    $SpreadsheetSnippets = New SpreadsheetSnippets($service);

    // fetch all excel sheet
    $sheetInfo = $service->spreadsheets->get($spreadsheetId);
    $allsheet_info = $sheetInfo['sheets'];
    $idCats = array_column($allsheet_info, 'properties');

    $testatt = array();
    $testatt[] = $myArr;

    // Get product category and add order to that category sheet.
    $items = $order_data['line_items'];
    foreach ( $items as $item_key => $item ) {
        $product_id = $item->get_product_id();
        $category_detail = get_the_terms( $product_id, 'product_cat' );
        foreach( $category_detail as $cd ){
            if (myArrayContainsWord($idCats, $cd->name)) {
                $SpreadsheetSnippets->appendValues( $spreadsheetId, $cd->name, 'USER_ENTERED', $testatt );
            }
        }
    }
}; 
         

// new order insert then add to google process
add_action( 'woocommerce_thankyou', 'new_order_custom_email_notification', 1, 1 );

?>
