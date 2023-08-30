<?php
/**
 * Plugin Name: Only Item in WooCommerce Cart
 * Plugin URI: 
 * Description: Forces certain products to be purchased as the only item in cart
 * Version: 1.0.0
 * Author: Kathy Darling
 * Author URI: http://kathyisawesome.com
 * Requires at least: 5.2.0
 * WC requires at least: 3.6.0   
 * Tested up to: 5.2.2
 * WC tested up to: 3.7.0   
 *
 * Text Domain: wc-only-item-in-cart
 * Domain Path: /languages/
 *
 * Copyright: © 2019 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */


/**
 * The Main WC_Only_Item_in_Cart class
 **/
if ( ! class_exists( 'WC_Only_Item_in_Cart' ) ) :

class WC_Only_Item_in_Cart {


    /**
     * WC_Only_Item_in_Cart init
     *
     * @access public
     * @since 1.0
     */

    public static function init() {

        // Declare HPOS compatibility.
		add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_compatibility' ) );

        // Product meta.
        add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_to_wc_metabox' ) );
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'process_wc_meta_box' ), 1, 2 );

        // Validation - ensure product is never in the cart with other products
        add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'maybe_remove_items' ), 10, 3 );

    }

    /**
	 * Declare HPOS (Custom Order tables) compatibility.
	 *
	 * @since 1.0.1
	 */
	public static function declare_hpos_compatibility() {

		if ( ! class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			return;
		}

		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );
	}

    /*-----------------------------------------------------------------------------------*/
    /* Product Write Panels */
    /*-----------------------------------------------------------------------------------*/


    /*
    * Add text inputs to product metabox
    * @since 1.0
    */
    public static function add_to_wc_metabox(){
        global $post;

        echo '<div class="options_group">';

        echo woocommerce_wp_checkbox( array(
            'id' => '_only_item_in_cart',
            'label' => esc_html__( 'Only Item In Cart' ) ,
            'description' => esc_html__( 'For special items that need to be purchased individually.', 'wc-only-item-in-cart' )
            )
        );

        echo '</div>';

    }


    /*
     * Save extra meta info
     * @since 1.0
     */
    public static function process_wc_meta_box( $post_id, $post ) {

        if ( isset( $_POST['_only_item_in_cart'] ) ) {
            update_post_meta( $post_id, '_only_item_in_cart', 'yes' );
        } else {
            update_post_meta( $post_id, '_only_item_in_cart', 'no' );
        }

    }


    /*-----------------------------------------------------------------------------------*/
    /* Check Cart for presence of certain items */
    /*-----------------------------------------------------------------------------------*/


    /**
     * When an item is added to the cart, remove other products
     * based on WooCommerce Subscriptions code
     *
     * @param bool $valid
     * @param int $product_id
     * @param int $quantity
     * @retun bool
     */
    public static function maybe_remove_items( $valid, $product_id, $quantity ) {

        if ( self::is_item_special( $product_id ) && WC()->cart->get_cart_contents_count() > 0 ){
            self::remove_specials_from_cart();
        } 

        return $valid;
    }


    /*-----------------------------------------------------------------------------------*/
    /* Helper methods */
    /*-----------------------------------------------------------------------------------*/


    /*
     * I've added a custom field 'only_item_in_cart' on items on 'special' products
     * check for this field similar to how Subscriptions checks cart for subscription items
     */
    public static function check_cart_for_specials() {

        $contains_special = false;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( self::is_item_special( $cart_item['data'] ) ) {
                $contains_special = true;
                break;
            }
        }

        return $contains_special;
    }

    /**
    * Removes all special products from the shopping cart.
    */
    public static function remove_specials_from_cart(){

        foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

            if ( self::is_item_special( $cart_item['product_id'] ) ) {

                WC()->cart->set_quantity( $cart_item_key, 0 );

                $message = sprintf( esc_html__( '&quot;%s&quot; has been removed from your cart. It cannot be purchased in conjunction with other products.', 'wc-only-item-in-cart' ), $cart_item['data']->get_title() );

                wc_add_notice( $message, 'error' );
            }

        }

    }

    /**
     * Check if an item has custom field
     *
     * @param int $product_id
     * @return bool
     */
    public static function is_item_special( $product_id ){
        $product = wc_get_product( $product_id );
        return $product && $product->get_meta( '_only_item_in_cart' ) == 'yes' ? true : false;
    }

} //end class: do not remove or there will be no more guacamole for you

endif; // end class_exists check

// Launch the whole plugin.
add_action( 'woocommerce_loaded', array( 'WC_Only_Item_in_Cart', 'init' ) );
