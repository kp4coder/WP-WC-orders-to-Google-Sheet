<?php 

/**
 * Plugin class
 **/
if ( ! class_exists( 'WOGS_Category_Meta' ) ) {

    class WOGS_Category_Meta {
    
        /*
         * Initialize the class and start calling our hooks and filters
         * @since 1.0.0
        */
        public function __construct() {
            add_action( 'product_cat_add_form_fields', array ( $this, 'wogs_add_product_cat' ), 10, 2 );

            add_action( 'created_product_cat', array ( $this, 'wogs_save_product_cat' ), 10, 2 );
            
            add_action( 'product_cat_edit_form_fields', array ( $this, 'wogs_update_product_cat' ), 10, 2 );
            
            add_action( 'edited_product_cat', array ( $this, 'wogs_updated_product_cat' ), 10, 2 );
        }
             
        /*
        * Add a form field in the new brand page
        * @since 1.0.0
        */
        public function wogs_add_product_cat ( $taxonomy ) { 
            $wogs_google_sheet_name = '';
            ?>
            <div class="form-field term-group">
                <label for="wogs_google_sheet_name"><?php _e( 'Google Sheet Name', WOGS_text_domain ); ?></label>
                <p>
                    <input type="text" name="wogs_google_sheet_name" id="wogs_google_sheet_name" value="<?php echo $wogs_google_sheet_name; ?>" />
                </p>
            </div>
            <?php
        }
    
        /*
        * Save the form field
        * @since 1.0.0
        */
        public function wogs_save_product_cat ( $term_id, $tt_id ) {
            if( isset( $_POST['wogs_google_sheet_name'] ) && '' !== $_POST['wogs_google_sheet_name'] ){
                add_term_meta( $term_id, 'wogs_google_sheet_name', $_POST['wogs_google_sheet_name'], true );
            }
        }
    
        /*
        * Edit the form field
        * @since 1.0.0
        */
        public function wogs_update_product_cat ( $term, $taxonomy ) { 
            $term_id = $term->term_id;
            $wogs_google_sheet_name = get_term_meta( $term_id, 'wogs_google_sheet_name', true );
            ?>
            <tr class="form-field term-group-wrap">
                <th scope="row">
                    <label for="wogs_google_sheet_name"><?php _e( 'Google Sheet Name', WOGS_text_domain ); ?></label>
                </th>
                <td>
                    <input type="text" name="wogs_google_sheet_name" id="wogs_google_sheet_name" value="<?php echo $wogs_google_sheet_name; ?>" />
                </td>
            </tr>
            <?php
        }
    
        /*
        * Update the form field value
        * @since 1.0.0
        */
        public function wogs_updated_product_cat ( $term_id, $tt_id ) {
            if( isset( $_POST['wogs_google_sheet_name'] ) ){
                update_term_meta( $term_id, 'wogs_google_sheet_name', $_POST['wogs_google_sheet_name'] );
            }
        }
    
    }

    global $wogs_category_meta;
    $wogs_category_meta = new WOGS_Category_Meta();

}
