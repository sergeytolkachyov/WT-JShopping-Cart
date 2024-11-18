<?php
/**
 * @package    WT JShopping Cart
 * @version    1.1.1
 * @author     Sergey Tolkachyov
 * @сopyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */

namespace Joomla\Module\Wtjshoppingcart\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Registry\Registry;


\defined('_JEXEC') or die;

/**
 * The helper class of a module
 *
 * @since  1.0
 */
class WtjshoppingcartHelper
{
	public static function getAjax()
	{

		$cart = JSFactory::getModel('cart', 'jshop');
		$cart->load("cart");
		$cart->addLinkToProducts(1, $type = "cart");
		$ajax_cart = [
			'count_products' => $cart->count_product,
			'price_product'  => Helper::formatprice($cart->price_product),
		];

		return json_encode($ajax_cart);

	}

	/**
	 * Get a JoomShopping temp cart
	 *
	 * @param   Registry  &$params  object holding the models parameters
	 *
	 * @return object jshopCart
	 */
	public static function getCart(Registry &$params, CMSApplicationInterface $app)
	{
		$cart = JSFactory::getModel('Cart', 'Site');
		$cart->load("cart");
		$cart->addLinkToProducts(1, $type = "cart");
		$product_qunatities = [];
		foreach ($cart->products as $product_number => $product)
		{
			$product_qunatities[$product_number] = $product['quantity'];
		}
		$currency_code = JSFactory::getConfig()->currency_code;
		$module_options = [
			'currency_code' => $currency_code
		];

		$app->getDocument()->addScriptOptions('wt_jshop_module_cart_module_options', $module_options);
		$app->getDocument()->addScriptOptions('wt_jshop_module_cart_product_qunatities', $product_qunatities);
		return $cart;
	}

}
