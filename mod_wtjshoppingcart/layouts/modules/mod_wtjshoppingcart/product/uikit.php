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

$product = $displayData['product'] ?? [];
$productNumber = (string) ($displayData['productNumber'] ?? '');
$params = $displayData['params'];
$config = $displayData['jshopConfig'];
$quantity = (float) ($product['quantity'] ?? 0);
$price = (float) ($product['price'] ?? 0);
$attributes = [];
foreach (array_merge($product['attributes_value'] ?? [], $product['free_attributes_value'] ?? []) as $attribute) {
    $attribute = (array) $attribute;
    $attributes[] = ($attribute['attr'] ?? '') . ': ' . ($attribute['value'] ?? '');
}
?>
<div class="uk-grid-small uk-flex-top uk-margin-medium-bottom" uk-grid
     data-wt-jshop-module-cart-product-number="<?php echo htmlspecialchars($productNumber, ENT_QUOTES, 'UTF-8'); ?>">
    <a class="uk-width-expand uk-flex uk-flex-top uk-link-reset"
       href="<?php echo htmlspecialchars((string) ($product['href'] ?? ''), ENT_QUOTES, 'UTF-8', false); ?>"
       data-wt-jshop-module-cart-product-link target="_blank" rel="noopener">
        <?php if ($params->get('show_image', 1)) : ?>
            <div class="uk-flex-none uk-margin-small-right">
                <img class="uk-border-rounded" data-wt-jshop-module-cart-product-image loading="lazy" width="64"
                     <?php if (!empty($product['thumb_image'])) : ?>
                         src="<?php echo htmlspecialchars(rtrim($config->image_product_live_path, '/') . '/' . $product['thumb_image'], ENT_QUOTES, 'UTF-8'); ?>"
                     <?php endif; ?>
                     alt="<?php echo htmlspecialchars((string) ($product['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        <?php endif; ?>
        <div class="uk-width-expand uk-text-break">
            <div class="uk-text-bold" data-wt-jshop-module-cart-product-name><?php echo htmlspecialchars((string) ($product['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="uk-text-small uk-text-muted">
                <span data-wt-jshop-module-cart-product-quantity><?php echo $quantity; ?></span>
                &times;
                <span data-wt-jshop-module-cart-product-price><?php echo JSHelper::formatprice($price); ?></span>
            </div>
            <?php if ($params->get('show_product_attributes', 0)) : ?>
                <div class="uk-text-meta" data-wt-jshop-module-cart-product-attributes><?php echo htmlspecialchars(implode(', ', $attributes), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div class="uk-text-bold" data-wt-jshop-module-cart-product-price-total><?php echo JSHelper::formatprice($quantity * $price); ?></div>
        </div>
    </a>
    <?php if ($displayData['showControls'] ?? true) : ?>
        <div class="uk-width-auto uk-flex uk-flex-column uk-flex-middle">
            <button type="button" class="uk-icon-button uk-margin-small-bottom" uk-icon="icon: trash"
                    data-product-task="delete" data-product-number="<?php echo htmlspecialchars($productNumber, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_REMOVE_PRODUCT'), ENT_QUOTES, 'UTF-8'); ?>"></button>
            <button type="button" class="uk-button uk-button-default uk-button-small"
                    data-product-task="quantity" data-quantity="up" data-product-number="<?php echo htmlspecialchars($productNumber, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_INCREASE_QUANTITY'), ENT_QUOTES, 'UTF-8'); ?>">+</button>
            <button type="button" class="uk-button uk-button-default uk-button-small"
                    data-product-task="quantity" data-quantity="down" data-product-number="<?php echo htmlspecialchars($productNumber, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_DECREASE_QUANTITY'), ENT_QUOTES, 'UTF-8'); ?>">-</button>
        </div>
    <?php endif; ?>
</div>
