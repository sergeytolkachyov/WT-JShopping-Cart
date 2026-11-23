<?php

/**
 * @package    WT JShopping Cart
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */

namespace Joomla\Module\Wtjshoppingcart\Site\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Language\Text;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Module\Wtjshoppingcart\Site\Helper\WtjshoppingcartHelper;

/**
 * Dispatcher class for mod_wtjshoppingcart
 *
 * @since  1.1.0
 */
class Dispatcher extends AbstractModuleDispatcher
{
    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   1.1.0
     */
    protected function getLayoutData(): array
    {
        if (!file_exists(JPATH_SITE . '/administrator/components/com_jshopping/jshopping.xml')) {
            throw new Exception('Please install JoomShopping', 500);
        }
        require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
        $data = parent::getLayoutData();
        $app = $this->getApplication();
        JSFactory::loadLanguageFile();
        $data['jshopConfig'] = JSFactory::getConfig();
        $data['cart'] = WtjshoppingcartHelper::getCart($data['params'], $app);

        $params = $data['params'];
        $document = $app->getDocument();
        $moduleId = (string) $data['module']->id;
        $document->addScriptOptions('wt_jshop_module_cart_module_options', [
            'image_path' => rtrim($data['jshopConfig']->image_product_live_path, '/') . '/',
            'modules' => [
                $moduleId => [
                    'template_id' => 'wt-jshop-cart-product-template-' . $moduleId,
                    'show_product_attributes' => (bool) $params->get('show_product_attributes', 0),
                    'show_image' => (bool) $params->get('show_image', 1),
                ],
            ],
        ]);
        $assets = $document->getWebAssetManager();
        $assets->registerAndUseScript('mod_wtjshoppingcart.js', 'mod_wtjshoppingcart/wtjshoppingcart.js', ['version' => 'auto'], ['defer' => true], ['core', 'messages']);
        Text::script('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST');
        Text::script('JSHOP_BUY');
        Text::script('JSHOP_ADD_TO_CART');

        return $data;
    }
}
