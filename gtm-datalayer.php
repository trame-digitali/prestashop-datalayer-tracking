<?php   
if (!defined('_PS_VERSION_'))
	exit;

class DataLayer extends Module
{
	public function __construct()
	{
		$this->name = 'gtm-datalayer';
		$this->tab = 'analytics_stats';
		$this->version = '1.0';
		$this->author = 'Trame Digitali';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Data Layer Module');
		$this->description = $this->l('Adds data layer data for use by the Google Tag Manager.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

		if (!Configuration::get('MYMODULE_NAME'))
			$this->warning = $this->l('No name provided');
	}

	public function install()
	{
		if (!parent::install() ||
			!$this->registerHook('orderConfirmation'))
			return false;
		if (!Db::getInstance()->Execute('
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'data_layer` (
				`id_data_layer` int(11) NOT NULL AUTO_INCREMENT,
				`id_order` int(11) NOT NULL,
				`sent` tinyint(1) DEFAULT NULL,
				`date_add` datetime DEFAULT NULL,
				PRIMARY KEY (`id_data_layer`),
				KEY `id_order` (`id_order`),
				KEY `sent` (`sent`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'))
			return $this->uninstall();
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;

		return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'data_layer`');
	}


	/**
	* To track transactions
	*/
	public function hookOrderConfirmation($params)
	{
		$order = $params['objOrder'];
		if (Validate::isLoadedObject($order))
		{
			$gtm_order_sent = Db::getInstance()->getValue('SELECT sent FROM `'._DB_PREFIX_.'data_layer` WHERE id_order = '.(int)$order->id);
			if ($gtm_order_sent === false)
			{
				$order_products = array();
				$order_products_facebook = array();

				$cart = new Cart($order->id_cart);
				foreach ($cart->getProducts() as $order_product)
					$order_products[] = "{
								'sku': '{$order_product['id_product']}',
								'name': '".str_replace ("'", '"', $order_product['name'])."',
								'price': {$order_product['price']},
								'quantity': {$order_product['cart_quantity']}
							}";
					// facebook data		
					$order_products_facebook[] = "{
								id: '{$order_product['id_product']}',
								quantity: {$order_product['cart_quantity']}
							}";

				$products_string = "'transactionProducts': [".implode(',', $order_products).']';
				// facebook implode
				$products_fabebook_string = "contents: [".implode(',', $order_products_facebook)."],
						    content_type: 'product'";


				Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'data_layer` (id_order, sent, date_add) VALUES ('.(int)$order->id.', 1, NOW())');

				//ALWAYS USE “PUSH” WITH THE DATA LAYER! http://www.simoahava.com/gtm-tips/datalayer-declaration-vs-push/
				$data_layer = "
					<script>
						window.dataLayer = window.dataLayer || [];
						dataLayer.push ({
							'transactionId': '$order->id',
							'transactionTotal': $order->total_paid,
							'transactionTax': ".($order->total_paid_tax_incl - $order->total_paid_tax_excl).",
							'transactionShipping': $order->total_shipping_tax_excl,
							$products_string
						});
						dataLayer.push({'event': 'prestashop_order_confirmation'});
					</script>";
				//  facebook purchase
				$data_layer .= "
					<script>
						fbq('track', 'Purchase',
						  {
						    value: $order->total_paid,
						    currency: 'EUR',
						    $products_fabebook_string
						  }
						);
					</script>
				";

				return $data_layer;
			}
		}
	}
}