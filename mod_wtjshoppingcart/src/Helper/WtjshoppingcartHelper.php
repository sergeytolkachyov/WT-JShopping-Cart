<?php

/**
 * @package    WT JShopping Cart
 * @version    1.1.1
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */

namespace Joomla\Module\Wtjshoppingcart\Site\Helper;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Layout\FileLayout;
use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The helper class of a module
 *
 * @since  1.0.0
 */
class WtjshoppingcartHelper
{
    /**
     * Return the cart summary for the AJAX entry point.
     *
     * @return  string|false  The JSON summary or false on encoding failure.
     *
     * @since   1.0.1
     */
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
     * @param   Registry                 &$params  The module parameters.
     * @param   CMSApplicationInterface  $app      The application instance.
     *
     * @return  \Joomla\Component\Jshopping\Site\Model\CartModel  The session cart.
     *
     * @since   1.0.0
     */
    public static function getCart(Registry &$params, CMSApplicationInterface $app)
    {
        $cart = JSFactory::getModel('Cart', 'Site');
        $cart->load("cart");
        $cart->addLinkToProducts(1, $type = "cart");
        $product_qunatities = [];
        foreach ($cart->products as $product_number => $product) {
            $product_qunatities[$product_number] = $product['quantity'];
        }
        $jshopConfig = JSFactory::getConfig();
        $currency_code = $jshopConfig->currency_code;
        $cartRoute = 'index.php?option=com_jshopping&controller=cart&task=';
        $module_options = [
            'cart_urls' => [
                'view' => Helper::SEFLink($cartRoute . 'view', 1, 1),
                'refresh' => Helper::SEFLink($cartRoute . 'refresh', 1, 1),
                'delete' => Helper::SEFLink($cartRoute . 'delete', 1, 1),
            ],
            'currency_code' => $currency_code,
            'price_format' => [
                'decimal_count' => $jshopConfig->decimal_count,
                'decimal_symbol' => $jshopConfig->decimal_symbol,
                'thousand_separator' => $jshopConfig->thousand_separator,
                'currency_format' => $jshopConfig->format_currency[$jshopConfig->currency_format],
            ],
        ];
        $app->getDocument()->addScriptOptions('wt_jshop_module_cart_module_options', $module_options);
        $app->getDocument()->addScriptOptions('wt_jshop_module_cart_product_qunatities', $product_qunatities);
        $app->getDocument()->addScriptOptions('wt_jshop_module_cart', [
            'cart' => [
                'products' => $cart->products,
                'count_product' => $cart->count_product,
                'price_product' => $cart->price_product,
            ],
        ]);
        return $cart;
    }

    /**
     * Render the selected product layout with Joomla template overrides.
     *
     * @param   array  $data  Product, module and display settings.
     *
     * @return  string
     * @since   1.2.0
     */
    public static function renderProduct(array $data): string
    {
        $defaultName = (string) ($data['defaultProductLayout'] ?? 'default');
        if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $defaultName)) {
            $defaultName = 'default';
        }

        $name = (string) $data['params']->get('cart_product_layout', 'default');
        if ($name === 'default' || !preg_match('/^[a-zA-Z0-9_-]+$/D', $name)) {
            $name = $defaultName;
        }

        $productLayouts = new FileLayout('modules.mod_wtjshoppingcart.product');
        $paths = $productLayouts->getIncludePaths();
        // Keep template overrides first and the old shared layout directory last.
        array_splice($paths, -1, 0, [dirname(__DIR__, 2) . '/layouts']);
        $productLayouts->setIncludePaths($paths);

        foreach (array_unique([$name, $defaultName, 'default']) as $layout) {
            $html = $productLayouts->sublayout($layout, $data);
            if (trim($html) !== '') {
                return $html;
            }
        }

        return '';
    }
}
