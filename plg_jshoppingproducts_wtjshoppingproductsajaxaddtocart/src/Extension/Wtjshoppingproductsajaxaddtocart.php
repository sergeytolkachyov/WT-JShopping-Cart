<?php

/**
 * @package    WT JShopping Products AJAX Add to Cart
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (C) 2021 - 2026 Sergey Tolkachyov
 * @license    GNU General Public License version 3 or later
 * @link       https://web-tolk.ru
 * @since      1.2.0
 */

namespace Joomla\Plugin\Jshoppingproducts\WtJshoppingProductsAjaxAddToCart\Extension;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Prepare product purchase buttons and their AJAX asset on shop pages.
 *
 * @since  1.2.0
 */
final class Wtjshoppingproductsajaxaddtocart extends CMSPlugin implements SubscriberInterface
{
    /**
     * Load the translations for the plugin's configured button text.
     *
     * @var    bool
     * @since  1.2.0
     */
    protected $autoloadLanguage = true;

    /**
     * Product quantities from the current request's cart.
     *
     * @var    array|null
     * @since  1.2.0
     */
    private ?array $quantities = null;

    /**
     * Whether the page has received the plugin's script options.
     *
     * @var    bool
     * @since  1.2.0
     */
    private bool $assetPrepared = false;

    /**
     * Subscribe to the JoomShopping product view and list events.
     *
     * @return  array
     * @since   1.2.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeDisplayProductView' => 'prepareProductView',
            'onBeforeDisplayProductListView' => 'prepareProductList',
        ];
    }

    /**
     * Prepare the product and related products before the template runs.
     *
     * @param   Event  $event  The event with the product view as its first argument.
     *
     * @return  void
     * @since   1.2.0
     */
    public function prepareProductView(Event $event): void
    {
        $view = $event->getArguments()[0] ?? null;
        $products = (array) ($view->related_prod ?? []);
        $products[] = $view->product ?? null;
        $this->prepareProducts($products);
    }

    /**
     * Prepare the products before a catalogue list is rendered.
     *
     * @param   Event  $event  The event with the list model as its second argument.
     *
     * @return  void
     * @since   1.2.0
     */
    public function prepareProductList(Event $event): void
    {
        $list = $event->getArguments()[1] ?? null;
        $this->prepareProducts((array) ($list->products ?? []));
    }

    /**
     * Set the template fields for each eligible product.
     *
     * @param   array  $products  The products about to be rendered.
     *
     * @return  void
     * @since   1.2.0
     */
    private function prepareProducts(array $products): void
    {
        $app = $this->getApplication();
        if (!$app->isClient('site') || $app->getInput()->getCmd('option') !== 'com_jshopping') {
            return;
        }

        $document = $app->getDocument();
        if (!$document instanceof HtmlDocument || !is_file(JPATH_ROOT . '/media/plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart/js/wtjshopping-add-to-cart.js')) {
            return;
        }

        $config = JSFactory::getConfig();
        $precision = empty($config->use_decimal_qty) ? 0 : max(0, min(20, (int) $config->cart_decimal_qty_precision));
        $text = Text::_((string) $this->params->get('btn_text_for_product_added', 'PLG_JSHOPPINGPRODUCTS_WTJSHOPPINGPRODUCTSAJAXADDTOCART_BTN_TEXT_IN_CART'));
        $class = (string) $this->params->get('btn_css_class_for_product_added', 'btn-success');
        $quantities = $this->getQuantities();

        $this->prepareAsset($document, $text, $class, $precision);

        foreach ($products as $product) {
            if (!is_object($product) || empty($product->product_id)) {
                continue;
            }

            $quantity = round($quantities[(int) $product->product_id] ?? 0.0, $precision);
            $product->wtAjaxCartInCart = $quantity > 0;
            $product->wtAjaxCartQuantity = $quantity;
            $product->wtAjaxCartButtonText = $text;
            $product->wtAjaxCartButtonClass = $class;
        }
    }

    /**
     * Read quantities by product from the current JoomShopping cart.
     *
     * @return  array
     * @since   1.2.0
     */
    private function getQuantities(): array
    {
        if ($this->quantities === null) {
            $cart = JSFactory::getModel('Cart', 'Site');
            $cart->load('cart');
            $this->getApplication()->getDocument()->addScriptOptions('wt_jshop_module_cart', [
                'cart' => [
                    'products' => $cart->products,
                    'count_product' => $cart->count_product,
                    'price_product' => $cart->price_product,
                ],
            ]);
            $this->quantities = [];

            foreach ($cart->products as $row) {
                $id = (int) $row['product_id'];
                $this->quantities[$id] = ($this->quantities[$id] ?? 0.0) + (float) $row['quantity'];
            }
        }

        return $this->quantities;
    }

    /**
     * Publish the configured button state and enable the shared Web Asset.
     *
     * @param   HtmlDocument  $document   The shop page document.
     * @param   string        $text       The translated text for an added product.
     * @param   string        $class      The CSS classes for an added product.
     * @param   int           $precision  The quantity precision of the shop.
     *
     * @return  void
     * @since   1.2.0
     */
    private function prepareAsset(HtmlDocument $document, string $text, string $class, int $precision): void
    {
        if ($this->assetPrepared) {
            return;
        }

        $name = 'plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart.add-to-cart';
        $assets = $document->getWebAssetManager();
        if (!$assets->assetExists('script', 'mod_wtjshoppingcart.js')) {
            $assets->registerScript(
                'mod_wtjshoppingcart.js',
                'mod_wtjshoppingcart/wtjshoppingcart.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core', 'messages']
            );
        }

        if (!$assets->assetExists('script', $name)) {
            $assets->registerScript(
                $name,
                'plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart/wtjshopping-add-to-cart.js',
                ['version' => 'auto'],
                ['defer' => true],
                ['core', 'messages', 'mod_wtjshoppingcart.js']
            );
        }

        $assets->useScript($name);
        $document->addScriptOptions('wt_jshop_add_to_cart', [
            'url' => Helper::SEFLink('index.php?option=com_jshopping&controller=cart&task=add', 1, 1),
            'added_button_text' => $text,
            'added_button_class' => $class,
            'quantity_precision' => $precision,
            'button_text' => Text::_('JSHOP_BUY'),
            'product_button_text' => Text::_('JSHOP_ADD_TO_CART'),
            'list_button_selector' => (string) $this->params->get('ajax_list_button_selector', '[wt-jshopping-add-to-cart-btn]'),
            'product_button_selector' => (string) $this->params->get('ajax_product_button_selector', '[wt-jshopping-add-to-cart-btn]'),
            'list_quantity_selector' => (string) $this->params->get('ajax_list_quantity_selector', '[wt-jshopping-add-to-cart-qty]'),
            'product_quantity_selector' => (string) $this->params->get('ajax_product_quantity_selector', '[wt-jshopping-add-to-cart-qty]'),
        ]);
        Text::script('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST');
        Text::script('JSHOP_BUY');
        Text::script('JSHOP_ADD_TO_CART');
        $this->assetPrepared = true;
    }
}
