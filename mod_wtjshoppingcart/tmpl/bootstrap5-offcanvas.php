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
use Joomla\Component\Jshopping\Site\Helper\Helper as JSHelper;
/**
 * @var \stdClass               $module      The module
 * @var CMSApplicationInterface $app         The application instance
 * @var Input                   $input       The input instance for $_GET, $_POST data etc.
 * @var Registry                $params      Module params
 * @var Registry                $template    Template params
 * @var object                  $cart        JoomShopping Cart object
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


/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$productLayoutData = [
    'module' => $module,
    'params' => $params,
    'jshopConfig' => $jshopConfig,
    'showControls' => true,
];
$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('bootstrap.offcanvas')->useScript('mod_wtjshoppingcart.js')
    ->registerAndUseStyle('mod_wtjshoppingcart.offcanvas', 'mod_wtjshoppingcart/wtjshoppingcart.css', ['version' => 'auto']);

?>

<noindex>
<div class="offcanvas <?php echo $params->get('bootstrap5_offcanvas_position', 'offacnvas-end'); ?> <?php echo $params->get('moduleclass_sfx');?>" tabindex="-1" id="wt_jshopping_cart_<?php echo $module->id; ?>" data-wt-jshop-module-cart="<?php echo $module->id; ?>" aria-labelledby="offcanvasLabel">
    <div class="offcanvas-header justify-content-between">
        <h5 class="offcanvas-title" id="offcanvasLabel"><?php echo Text::_('JSHOP_CART'); ?></h5>
       <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body wt_jshop_module_cart" id="jshop_module_cart<?php echo $module->id; ?>_inner">
    <div class="list-group" data-wt-jshop-module-cart-products<?php echo empty($cart->products) ? ' hidden' : ''; ?>>
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
        <div class="list-group-item d-flex justify-content-between align-items-start"
             data-wt-jshop-module-cart-product-number="">
            <a href="" data-wt-jshop-module-cart-product-link
               target="_blank" rel="noopener" class="flex-grow-1 text-decoration-none d-flex">
                <?php if ($params->get('show_image', 1)) : ?>
                    <div class="wt_jshop_module_cart_product_image">
                        <img data-wt-jshop-module-cart-product-image loading="lazy" width="64" height="auto" alt="">
                    </div>
                <?php endif; ?>
                <div class="ms-2 me-auto">
                    <div class="fw-bold" data-wt-jshop-module-cart-product-name></div>
                    <small class="text-muted">
                        <span data-wt-jshop-module-cart-product-quantity>0</span>
                        &times;
                        <span data-wt-jshop-module-cart-product-price><?php echo JSHelper::formatprice(0); ?></span>
                    </small><br>
                    <?php if ($params->get('show_product_attributes', 0)) : ?>
                        <small class="text-muted" data-wt-jshop-module-cart-product-attributes></small>
                    <?php endif; ?>
                    <span class="badge bg-primary rounded-pill" data-wt-jshop-module-cart-product-price-total><?php echo JSHelper::formatprice(0); ?></span>
                </div>
            </a>
            <div class="d-flex flex-column justify-content-start">
                <button type="button" class="btn btn-sm border-1" data-product-task="delete" data-product-number="">&times;</button>
                <button type="button" class="btn btn-sm border-1" data-product-task="quantity" data-quantity="up" data-product-number="">+</button>
                <button type="button" class="btn btn-sm border-1" data-product-task="quantity" data-quantity="down" data-product-number="">-</button>
            </div>
        </div>
    </template>
 </div>
 <div class="offcanvas-footer border-1 mt-2 p-2 d-flex flex-column">

            <?php if ($params->get('show_product_weight', 0) == 1) :
                ?>
               <div class="ms-2 me-auto h6">
                    <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_WEIGHT_TOTAL'); ?> <span data-wt-jshop-module-cart-weight-total><?php echo JSHelper::formatweight($cart->getWeightProducts()); ?></span>
                </div>
                <?php
            endif; ?>
         <div class="ms-2 me-auto h5 fw-bold">
                <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_TOTAL'); ?> <span data-wt-jshop-module-cart-price-total><?php echo JSHelper::formatprice($cart->getSum(0, 1)); ?></span>
            </div>
         <div class="btn-group">
                <a class="wt_jshop_module_cart btn btn-outline-primary btn-sm"
                   href="<?php echo JSHelper::SEFLink('index.php?option=com_jshopping&controller=cart', 1); ?>"
                rel="nofollow"
                   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?></a>
             <a class="wt_jshop_module_cart btn btn-primary btn-sm"
                   href="<?php echo JSHelper::SEFLink('index.php?option=com_jshopping&controller=checkout'); ?>"
                   rel="nofollow"
                   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?></a>
         </div>

        <?php if ($params->get('show_debug', 0)) :
            ?>
          <details>
                <summary class="btn btn-danger btn-block"><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
                <?php dump($cart); ?>
            </details>
            <?php
        endif; ?>
 </div>
</div>
</noindex>
