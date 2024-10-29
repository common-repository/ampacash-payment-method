<?php
/*
 * Plugin Name: AmpaCash Veprap Payment
 * Description: AmpaCash Veprap Payment Method for user to pay for merchandise.
 * Version: 2.5
 * Author: AmpaCash
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Accept AmpaCash payments on your WordPress site seamlessly.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

add_action('plugins_loaded', 'ampacash_veprap_gateway_init');

function ampacash_veprap_gateway_init() {

    class AmpaCash_Veprap_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'ampacash_veprap';
            $this->icon = plugin_dir_url(__FILE__) . 'imgs/logo.png';
            $this->has_fields = false;
            $this->method_title = 'AmpaCash Gateway';
            $this->method_description = 'Pay with AmpaCash';

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Actions.
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Process the payment when the order is placed.
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'process_payment'));

            // Add the payment method to WooCommerce.
            add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));

            // Enqueue script only if Veprap payment method is selected.
            add_action('wp_enqueue_scripts', array($this, 'enqueue_veprap_script'));
        }

        // Add the payment method to WooCommerce.
        public function add_gateway_class($gateways) {
            $gateways[] = 'AmpaCash_Veprap_Gateway';
            return $gateways;
        }

        // Set up payment gateway fields in WooCommerce settings.
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable AmpaCash Payment',
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => 'Title',
                    'type' => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default' => 'Pay securely with AmpaCash:',
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => 'Description',
                    'type' => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default' => 'Pay securely with AmpaCash Payment.',
                ),
                'order_status' => array(
                    'title' => 'Default Order Status',
                    'type' => 'select',
                    'options' => wc_get_order_statuses(),
                    'default' => 'pending',
                    'desc_tip' => true,
                    'description' => 'Select the default order status when the payment is made.',
                ),
                'merchant_id' => array(
                    'title' => 'Merchant ID',
                    'type' => 'text',
                    'description' => 'Sign up as a merchant at ampacash.com, and enter your merchant ID here.',
                    'default' => '',
                ),
                'merchant_email' => array(
                    'title' => 'Email Address',
                    'type' => 'text',
                    'description' => 'Type your email address here.',
                    'default' => '',
                ),
            );
        }

        // Display the payment method on the checkout page.
        public function payment_fields() {
            $merchant_id = esc_attr($this->get_option('merchant_id'));
            $merchant_email = esc_attr($this->get_option('merchant_email'));
            $website_url = esc_url(home_url('/'));
            // $repurchasing_allowed = $this->get_option('repurchasing_allowed') === 'yes' ? true : false;
            $repurchasing_allowed = false;

            // Get product details (names and IDs)
            $product_details = $this->get_product_details();
            $product_names = array_column($product_details, 'name');
            $product_ids = array_column($product_details, 'id');

            // Display AmpaCash payment fields
            echo '<ampa-veprap id="def" paybyampaproduct=\'{"Amount":"' . esc_html(WC()->cart->total) . '","Name":"' . esc_attr(implode(', ', $product_names)) . '","itemID":"' . esc_attr(implode(', ', $product_ids)) . '","merchantUrl":"' . esc_url($website_url) . '","email":"' . esc_attr($merchant_email) . '","isRepurchasingAllowed":' . ($repurchasing_allowed ? 'true' : 'false') . '}\'> </ampa-veprap>';
            echo '<span id="error_def" style="color:red; display:block; margin-top: 2px;"></span>';
            echo '<div id="ampa-cash-errors" class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"></div>';
            echo '<span id="success_def" style=" font-size: 1px; color:transparent; display:block; margin-top: 2px;"></span>';
            if ($repurchasing_allowed === 'false') {
                $inline_css = '#place_order { display: none; }';
                wp_add_inline_style('ampacash-veprap-styles', $inline_css);
            }
        }

        // Get product names from the cart.
        private function get_product_details() {
            $product_details = array();
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                $product_details[] = array(
                    'name' => $product->get_name(),
                    'id' => $product->get_id()
                );
            }
            return $product_details;
        }

        // Process the payment when the order is placed.
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Process the payment here and update the order status accordingly.
            // ...

            // Set the order status based on the user's selection.
            $order->update_status($this->get_option('order_status'));

            // Mark the order as paid.
            $order->payment_complete();

            // Reduce stock levels.
            $order->reduce_order_stock();

            // Return success redirect.
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }

        // Enqueue Veprap script if Veprap payment method is selected.
        public function enqueue_veprap_script() {
            // Enqueue Veprap script only if Veprap payment method is selected.
            wp_enqueue_script('ampacash-veprap', plugin_dir_url(__FILE__) . 'js/veprap.js', array(), '1.0', true);
            
            // Enqueue the external JavaScript file
            wp_enqueue_script('ampacash-veprap-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '2.0', true);

            // Enqueue the local CSS file
            wp_enqueue_style('ampacash-veprap-styles', plugins_url('css/ampacash-styles.css', __FILE__));
            
            // Pass PHP variables to JavaScript file
            wp_localize_script('ampacash-veprap-script', 'ampaCashVars', array(
                'isUserLoggedInVar' => is_user_logged_in(),
                'paymentMethodVar' => 'veprap',
                'merchantIdVar' => esc_js($this->get_option('merchant_id')),
            ));
        }
    }

    // function enqueue_ampacash_script() {
    //     if (is_checkout()) { // Ensure script loads only on the checkout page
    //         wp_register_script(
    //             'ampa-cash-js', // Handle for the script
    //             plugin_dir_url(__FILE__) . 'js/script.js', // Path to your script
    //             array('jquery'), // Dependencies, if any
    //             '1.0', // Version number
    //             true // Load in footer
    //         );

    //         wp_enqueue_script('ampa-cash-js');
    //     }
    // }

    // Register the gateway.
    function ampacash_add_veprap_gateway_class($gateways) {
        $gateways[] = 'AmpaCash_Veprap_Gateway';
        return $gateways;
    }

    add_filter('woocommerce_payment_gateways', 'ampacash_add_veprap_gateway_class');
}
?>
