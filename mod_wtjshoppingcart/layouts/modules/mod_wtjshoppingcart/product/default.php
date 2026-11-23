<?php

/**
 * @package    WT JShopping Cart
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2026 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 * @since      1.2.0
 */

defined('_JEXEC') or die;

use Joomla\Component\Jshopping\Site\Helper\Helper as JSHelper;

$product = $displayData['product'] ?? [];
$productNumber = $displayData['productNumber'] ?? '';
$params = $displayData['params'];
$config = $displayData['jshopConfig'];
$quantity = $product['quantity'] ?? 0;
$price = $product['price'] ?? 0;
$attributes = [];
foreach (array_merge($product['attributes_value'] ?? [], $product['free_attributes_value'] ?? []) as $attribute) {
    $attributes[] = $attribute->attr . ': ' . $attribute->value;
}
?>
<div class="list-group-item d-flex justify-content-between align-items-start"
     data-wt-jshop-module-cart-product-number="<?php echo htmlspecialchars((string) $productNumber, ENT_QUOTES, 'UTF-8'); ?>">
    <a href="<?php echo htmlspecialchars($product['href'] ?? '', ENT_QUOTES, 'UTF-8', false); ?>"
       data-wt-jshop-module-cart-product-link
       target="_blank" rel="noopener" class="flex-grow-1 text-decoration-none d-flex">
        <?php if ($params->get('show_image', 1)) : ?>
            <div class="wt_jshop_module_cart_product_image">
                <img data-wt-jshop-module-cart-product-image loading="lazy" width="64" height="auto"
                     <?php if (!empty($product['thumb_image'])) : ?>
                         src="<?php echo htmlspecialchars(rtrim($config->image_product_live_path, '/') . '/' . $product['thumb_image'], ENT_QUOTES, 'UTF-8'); ?>"
                     <?php endif; ?>
                     alt="<?php echo htmlspecialchars($product['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        <?php endif; ?>
        <div class="ms-2 me-auto">
            <div class="fw-bold" data-wt-jshop-module-cart-product-name><?php echo htmlspecialchars($product['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
            <small class="text-muted">
                <span data-wt-jshop-module-cart-product-quantity><?php echo $quantity; ?></span>
                &times;
                <span data-wt-jshop-module-cart-product-price><?php echo JSHelper::formatprice($price); ?></span>
            </small><br>
            <?php if ($params->get('show_product_attributes', 0)) : ?>
                <small class="text-muted" data-wt-jshop-module-cart-product-attributes><?php echo htmlspecialchars(implode(', ', $attributes), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
            <span class="badge bg-primary rounded-pill" data-wt-jshop-module-cart-product-price-total><?php echo JSHelper::formatprice($quantity * $price); ?></span>
        </div>
    </a>
    <?php if ($displayData['showControls'] ?? true) : ?>
        <div class="d-flex flex-column justify-content-start">
            <button type="button" class="btn btn-sm border-1" data-product-task="delete"
                    data-product-number="<?php echo htmlspecialchars((string) $productNumber, ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
            <button type="button" class="btn btn-sm border-1" data-product-task="quantity" data-quantity="up"
                    data-product-number="<?php echo htmlspecialchars((string) $productNumber, ENT_QUOTES, 'UTF-8'); ?>">+</button>
            <button type="button" class="btn btn-sm border-1" data-product-task="quantity" data-quantity="down"
                    data-product-number="<?php echo htmlspecialchars((string) $productNumber, ENT_QUOTES, 'UTF-8'); ?>">-</button>
        </div>
    <?php endif; ?>
</div>
