<?php

/**
 * @package    WT JShopping Cart
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 * @since      1.2.0
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\Component\Jshopping\Site\Helper\Helper as JSHelper;
use Joomla\Module\Wtjshoppingcart\Site\Helper\WtjshoppingcartHelper;

/** @var object $module */
/** @var \Joomla\Registry\Registry $params */
/** @var object $cart */
/** @var object $jshopConfig */
$moduleId = (int) $module->id;
$flip = $params->get('bootstrap5_offcanvas_position', 'offcanvas-end') !== 'offcanvas-start';
$productLayoutData = [
    'module' => $module,
    'params' => $params,
    'jshopConfig' => $jshopConfig,
    'showControls' => true,
    'defaultProductLayout' => 'uikit',
];
?>
<div id="wt_jshopping_cart_<?php echo $moduleId; ?>"
     data-wt-jshop-module-cart="<?php echo $moduleId; ?>"
     class="<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>"
     uk-offcanvas="overlay: true; flip: <?php echo $flip ? 'true' : 'false'; ?>">
    <div class="uk-offcanvas-bar uk-flex uk-flex-column"
         aria-labelledby="wt_jshopping_cart_title_<?php echo $moduleId; ?>">
        <button class="uk-offcanvas-close" type="button" uk-close
                aria-label="<?php echo htmlspecialchars(Text::_('JCLOSE'), ENT_QUOTES, 'UTF-8'); ?>"></button>
        <h3 id="wt_jshopping_cart_title_<?php echo $moduleId; ?>" class="uk-margin-remove-top"><?php echo Text::_('JSHOP_CART'); ?></h3>
        <div class="wt_jshop_module_cart uk-margin-medium-top" id="jshop_module_cart<?php echo $moduleId; ?>_inner">
            <div data-wt-jshop-module-cart-products<?php echo empty($cart->products) ? ' hidden' : ''; ?>>
                <?php foreach ($cart->products as $product_number => $product) : ?>
                    <?php echo WtjshoppingcartHelper::renderProduct(array_merge($productLayoutData, [
                        'product' => $product,
                        'productNumber' => $product_number,
                    ])); ?>
                <?php endforeach; ?>
            </div>
            <div class="uk-alert-primary" uk-alert data-wt-jshop-module-cart-empty<?php echo empty($cart->products) ? '' : ' hidden'; ?>>
                <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_IS_EMPTY'); ?>
            </div>
            <template id="wt-jshop-cart-product-template-<?php echo $moduleId; ?>">
                <div class="uk-grid-small uk-flex-top uk-margin-medium-bottom" uk-grid
                     data-wt-jshop-module-cart-product-number="">
                    <a class="uk-width-expand uk-flex uk-flex-top uk-link-reset" href=""
                       data-wt-jshop-module-cart-product-link target="_blank" rel="noopener">
                        <?php if ($params->get('show_image', 1)) : ?>
                            <div class="uk-flex-none uk-margin-small-right">
                                <img class="uk-border-rounded" data-wt-jshop-module-cart-product-image
                                     loading="lazy" width="64" alt="">
                            </div>
                        <?php endif; ?>
                        <div class="uk-width-expand uk-text-break">
                            <div class="uk-text-bold" data-wt-jshop-module-cart-product-name></div>
                            <div class="uk-text-small uk-text-muted">
                                <span data-wt-jshop-module-cart-product-quantity>0</span>
                                &times;
                                <span data-wt-jshop-module-cart-product-price><?php echo JSHelper::formatprice(0); ?></span>
                            </div>
                            <?php if ($params->get('show_product_attributes', 0)) : ?>
                                <div class="uk-text-meta" data-wt-jshop-module-cart-product-attributes></div>
                            <?php endif; ?>
                            <div class="uk-text-bold" data-wt-jshop-module-cart-product-price-total><?php echo JSHelper::formatprice(0); ?></div>
                        </div>
                    </a>
                    <div class="uk-width-auto uk-flex uk-flex-column uk-flex-middle">
                        <button type="button" class="uk-icon-button uk-margin-small-bottom" uk-icon="icon: trash"
                                data-product-task="delete" data-product-number=""
                                aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_REMOVE_PRODUCT'), ENT_QUOTES, 'UTF-8'); ?>"></button>
                        <button type="button" class="uk-button uk-button-default uk-button-small"
                                data-product-task="quantity" data-quantity="up" data-product-number=""
                                aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_INCREASE_QUANTITY'), ENT_QUOTES, 'UTF-8'); ?>">+</button>
                        <button type="button" class="uk-button uk-button-default uk-button-small"
                                data-product-task="quantity" data-quantity="down" data-product-number=""
                                aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_DECREASE_QUANTITY'), ENT_QUOTES, 'UTF-8'); ?>">-</button>
                    </div>
                </div>
            </template>
        </div>
        <div class="uk-margin-auto-top uk-padding-small uk-padding-remove-horizontal">
            <?php if ($params->get('show_product_weight', 0)) : ?>
                <p class="uk-text-small">
                    <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_WEIGHT_TOTAL'); ?>
                    <span data-wt-jshop-module-cart-weight-total><?php echo JSHelper::formatweight($cart->getWeightProducts()); ?></span>
                </p>
            <?php endif; ?>
            <p class="uk-text-bold">
                <?php echo Text::_('MOD_WTJSHOPPINGCART_CART_TOTAL'); ?>
                <span data-wt-jshop-module-cart-price-total><?php echo JSHelper::formatprice($cart->getSum(0, 1)); ?></span>
            </p>
            <a class="uk-button uk-button-default uk-width-1-1 uk-margin-small-bottom"
               href="<?php echo htmlspecialchars(JSHelper::SEFLink('index.php?option=com_jshopping&controller=cart', 1), ENT_QUOTES, 'UTF-8', false); ?>"
               rel="nofollow"><?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?></a>
            <a class="uk-button uk-button-primary uk-width-1-1"
               href="<?php echo htmlspecialchars(JSHelper::SEFLink('index.php?option=com_jshopping&controller=checkout'), ENT_QUOTES, 'UTF-8', false); ?>"
               rel="nofollow"><?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?></a>
            <?php if ($params->get('show_debug', 0)) : ?>
                <details class="uk-margin-small-top">
                    <summary><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
                    <?php dump($cart); ?>
                </details>
            <?php endif; ?>
        </div>
    </div>
</div>
