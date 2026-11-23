<?php

/**
 * @package    WT JShopping Cart
 * @version    1.1.1
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 * @since      1.0.0
 */

\defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\Module\Wtjshoppingcart\Site\Helper\WtjshoppingcartHelper;

/**
 * @var \stdClass               $module   The module
 * @var CMSApplicationInterface $app      The application instance
 * @var Input                   $input    The input instance for $_GET, $_POST data etc.
 * @var Registry                $params   Module params
 * @var Registry                $template Template params
 * @var object                  $cart     JoomShopping Cart object
 * @var object                  $jshopConfig JoomShopping config object
 *
 * And your own vars wich you can set in your module displatcher
 * src/Dispatcher/Dispatcher.php in function getLayoutData().
 * When you return $data['your_own_variable_name'] function getLayoutData() here
 * you'll access to it via $your_own_variable_name
 */
/**
 * Module params
 * dump($params);
 *
 * Get module param - $param->get('param_name');
 ************
 * JoomShopping cart data
 * dump($cart);
 *
 * Get cart data
 * echo $cart->param_name
 */

$productLayoutData = [
    'module' => $module,
    'params' => $params,
    'jshopConfig' => $jshopConfig,
    'showControls' => false,
];
?>
<div data-wt-jshop-module-cart="<?php echo $module->id; ?>" id="jshop_module_cart<?php echo $module->id; ?>" class="wt_jshop_module_cart <?php echo $params->get('moduleclass_sfx');?>">
    <div class="list-group list-group-numbered" data-wt-jshop-module-cart-products<?php echo empty($cart->products) ? ' hidden' : ''; ?>>
        <?php foreach ($cart->products as $product_number => $product) : ?>
            <?php echo WtjshoppingcartHelper::renderProduct(array_merge($productLayoutData, [
                'product' => $product,
                'productNumber' => $product_number,
            ])); ?>
        <?php endforeach; ?>
    </div>
    <div class="alert alert-info" data-wt-jshop-module-cart-empty<?php echo empty($cart->products) ? '' : ' hidden'; ?>>
        <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_IS_EMPTY'); ?>
    </div>
    <template id="wt-jshop-cart-product-template-<?php echo $module->id; ?>">
        <?php echo WtjshoppingcartHelper::renderProduct($productLayoutData); ?>
    </template>
        <?php if ($params->get('show_product_weight', 0) == 1) :
            ?>
         <div class="h6 p-2">
                <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_WEIGHT_TOTAL'); ?> <span data-wt-jshop-module-cart-weight-total><?php echo JSHelper::formatweight($cart->getWeightProducts()); ?></span>
         </div>
            <?php
        endif; ?>

        <div class="border-1 p-2 d-flex">
          <div class="ms-2 me-auto h5">
                <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_TOTAL'); ?> <span data-wt-jshop-module-cart-price-total><?php echo JSHelper::formatprice($cart->getSum(0, 1)); ?></span>
           </div>
         <div class="btn-group">
                <a class="wt_jshop_module_cart btn btn-outline-primary btn-sm"
                   href="<?php echo JSHelper::SEFLink('index.php?option=com_jshopping&controller=cart'); ?>"
                   rel="nofollow"
                   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?></a>
             <a class="wt_jshop_module_cart btn btn-primary btn-sm"
                   href="<?php echo JSHelper::SEFLink('index.php?option=com_jshopping&controller=checkout'); ?>"
                   rel="nofollow"
                   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?></a>
         </div>
     </div>

</div>

<?php if ($params->get('show_debug', 0)) :
    ?>
   <details>
        <summary class="btn btn-danger btn-block"><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
        <?php dump($cart); ?>
    </details>
    <?php
endif; ?>
