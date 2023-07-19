<?php
/**
 * Plugin Name: Hbc Payment Woocommerce
 * Version: 0.1.0
 * Author: HyperBC
 * Author URI: https://www.hyperbc.com/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package extension
 */

defined('ABSPATH') || exit;

if (!defined('MAIN_PLUGIN_FILE')) {
	define('MAIN_PLUGIN_FILE', __FILE__);
}

/**
 * WooCommerce fallback notice.
 *
 * @since 0.1.0
 */
function hbc_payment_woocommerce_missing_wc_notice() {
	/* translators: %s WC download URL link. */
	echo '<div class="error"><p><strong>' . sprintf(esc_html__('Hbc Payment Woocommerce requires WooCommerce to be installed and active. You can download %s here.', 'hbc_payment_woocommerce'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

register_activation_hook(__FILE__, 'hbc_payment_woocommerce_activate');

/**
 * Activation hook.
 *
 * @since 0.1.0
 */
function hbc_payment_woocommerce_activate() {
	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'hbc_payment_woocommerce_missing_wc_notice');
		return;
	}
}

add_action('plugins_loaded', 'hbc_payment_woocommerce_init', 10);

define('HYPERBC_WOOCOMMERCE_VERSION', '0.1.0');
define('HYPERBC_APP_ID', '');
define('HYPERBC_PUBLIC_KEY', '');
define('HYPERBC_PRIVATE_KEY', '');
define('HYPERBC_ENVIRONMENT', 'Sandbox');
/**
 * Initialize the plugin.
 *
 * @since 0.1.0
 */
function hbc_payment_woocommerce_init() {
	// load_plugin_textdomain( 'hbc_payment_woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

	if (!class_exists('WooCommerce')) {
		add_action('admin_notices', 'hbc_payment_woocommerce_missing_wc_notice');
		return;
	}

	if (!defined('PLUGIN_DIR')) {
		define('PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)) . '/');
	}

	require_once(__DIR__ . '/lib/hyperbc/init.php');

	class WC_Gateway_HyperBC extends WC_Payment_Gateway {
		public function __construct() {
			global $woocommerce;

			$this->id = 'hyperbc';
			$this->has_fields = false;
			$this->method_title = 'HyperBC';
			$this->title = 'Pay with HyperBC';

			$this->init_form_fields();
			$this->init_settings();

			$this->app_id = $this->get_option('app_id');
			$this->public_key = $this->get_option('public_key');
			$this->private_key = $this->get_option('private_key');
			$this->environment = $this->get_option('environment');
			;

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('woocommerce_thankyou_hyperbc', array($this, 'thankyou'));
			add_action('woocommerce_api_wc_gateway_hyperbc', array($this, 'payment_callback'));
		}

		public function admin_options() {
			?>
			<h3>
				<?php esc_html_e('HyperBC', 'woothemes'); ?>
			</h3>
			<p>
				<?php esc_html_e('Accept Bitcoin instantly through hyperbc.com.', 'woothemes'); ?>
			</p>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
			<?php

		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled' => array(
					'title' => __('Enable HyperBC', 'woocommerce'),
					'label' => __('Enable Bitcoin payments via HyperBC', 'woocommerce'),
					'type' => 'checkbox',
					'description' => '',
					'default' => 'no',
				),
				'environment' => array(
					'title' => __('Environment', 'woocommerce'),
					'description' => __('Application production/testing', 'woocommerce'),
					'type' => 'text',
					'default' => HYPERBC_ENVIRONMENT,
				),
				'app_id' => array(
					'title' => __('App ID', 'woocommerce'),
					'description' => __('Application id from vendor', 'woocommerce'),
					'type' => 'text',
					'default' => HYPERBC_APP_ID,
				),
				'public_key' => array(
					'title' => __('Public Key', 'woocommerce'),
					'description' => __('Vendor\'s public key', 'woocommerce'),
					'type' => 'textarea',
					'default' => HYPERBC_PUBLIC_KEY,
				),
				'private_key' => array(
					'title' => __('Private Key', 'woocommerce'),
					'description' => __('Your private key', 'woocommerce'),
					'type' => 'textarea',
					'default' => HYPERBC_PRIVATE_KEY,
				)
			);
		}

		public function thankyou() {
			$description = $this->get_description();
			if ($description) {
				echo esc_html_e(wpautop(wptexturize($description)));
			}
		}

		public function process_payment( $order_id ) {
			$order = wc_get_order($order_id);

			$this->init_hyperbc();

			$hyperbc_order_id = get_post_meta($order->get_id(), 'hyperbc_order_id', true);
			$hyperbc_checkout_url = get_post_meta($order->get_id(), 'hyperbc_checkout_url', true);

			if (empty($hyperbc_order_id)) {
				$params = array(
					'merchant_order_id' => strval($order->get_id()),
					'amount' => $order->get_total(),
					'currency' => 'usd',
					'return_url' => $order->get_checkout_order_received_url(),
				);

				$hyperbc_order = \HyperBC\Merchant\Order::create($params);

				if (!$hyperbc_order || !$hyperbc_order->order_no) {
					throw new Exception('Order #' . $hyperbc_order_id . ' does not exists');
				}

				update_post_meta($order_id, 'hyperbc_order_id', $hyperbc_order->order_no);
				update_post_meta($order_id, 'hyperbc_checkout_url', $hyperbc_order->checkout_url);

				return array(
					'result' => 'success',
					'redirect' => $hyperbc_order->checkout_url,
				);
			} else {
				return array(
					'result' => 'success',
					'redirect' => $hyperbc_checkout_url,
				);
			}
		}

		public function payment_callback( $response ) {
			$this->init_hyperbc();
			$_REQUEST = json_decode(esc_url_raw( wp_unslash(file_get_contents('php://input'), true)));
			
			if ( !isset($_REQUEST['data']) ) {
				return;
			}
			if (!isset($_REQUEST['sign'])) {
				return;
			}

			$request = esc_url_raw($_REQUEST['data']);
			error_log('payment_callback');
			$order = wc_get_order($request['merchant_order_id']);
			$hyperbc_order_id = get_post_meta($order->get_id(), 'hyperbc_order_id', true);
			try {
				if ( !$order || !$order->get_id() ) {
					throw new Exception('Order #' . $request['merchant_order_id'] . ' does not exists');
				}

				// check for signature
				if ( !\HyperBC\HyperBC::check_sign(esc_url_raw($_REQUEST['sign']), esc_url_raw($_REQUEST['data'])) ) {
					error_log('sign no match');
					\HyperBC\Exception::throwException(422, array('error' => 'SignatureError'));
					return;
				}

				// $cgOrder = \HyperBC\Merchant\Order::find(Array('order_no'=>$hyperbc_order_id));

				// error_log(print_r($cgOrder, true));

				// if (!$cgOrder) {
				//     throw new Exception('HyperBC Order #' . $order->get_id() . ' does not exists');
				// }
				if ($order->get_status() == 'completed') {
					return;
				}
				switch ($request['status']) {
					// switch ($cgOrder->status) {
					case 0: // 待⽀付
						break;
					case 1: // 已完成
						$statusWas = 'wc-' . $order->get_status();
						$order->add_order_note(__('Payment is settled and has been credited to your HyperBC account. Purchased goods/services can be securely delivered to the customer.', 'hyperbc'));
						$order->payment_complete();
						if ( 'processing' === $order->get_status() && ( 'wc-expired' === $statusWas || 'wc-canceled' === $statusWas ) ) {
							WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
						}
						if ( ( 'processing'  === $order->get_status() || 'completed' === $order->get_status() ) && ( 'wc-expired' === $statusWas || 'wc-canceled' === $statusWas ) ) {
							WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());
						}
						break;
					case 2: // 异常支付
						$receive_amt = 0;
						foreach ($request['payments'] as $x => $val) {
							$receive_amt += $val['amount'];
						}
						$missing_amt = $receive_amt - $order->get_total();
						$order->add_order_note(__('Customer has paid via standard on-Chain, but has ' . ( $missing_amt > 0 ? 'overpaid' : 'underpaid' ) . ' by ' . $missing_amt . '. Please contact vendor for further action.', 'hyperbc'));
						break;
					case 10: // 已取消
						$order->add_order_note(__('Payment expired', 'hyperbc'));
						$order->update_status('cancelled');
						break;
				}

				$response = array(
					'status' => 200,
					'data' => array(
						'success_data' => 'success'
					),
					'sign' => ''
				);
				$response = \HyperBC\HyperBC::sign($response, true);

				return esc_html_e($response);
			} catch (Exception $e) {
				die(esc_html_e(get_class(esc_html($e)) . ': ' . $e->getMessage()));
			}
		}

		private function init_hyperbc() {
			\HyperBC\HyperBC::config(
				array(
					'app_id' => $this->app_id,
					'public_key' => $this->public_key,
					'private_key' => $this->private_key,
					'environment' => $this->environment,
					'user_agent' => ( 'HyperBC - WooCommerce v' . WOOCOMMERCE_VERSION . ' Plugin v' . HYPERBC_WOOCOMMERCE_VERSION ) 
				)
			);
		}
	}

	function add_hyperbc_gateway( $methods ) {
		$methods[] = 'WC_Gateway_HyperBC';

		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_hyperbc_gateway');
}
