<?php
/**
 * @package    WT JShopping Cart
 * @version    1.1.1
 * @author     Sergey Tolkachyov
 * @сopyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */
\defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
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
 * echo '<pre>';
 * print_r($params);
 * echo '</pre';
 *
 * Get module param - $param->get('param_name');
 ************
 * JoomShopping cart data
 * * echo '<pre>';
 * print_r($cart);
 * echo '</pre';
 *
 * Get cart data
 * echo $cart->param_name
 */


/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $app->getDocument()->getWebAssetManager();
$wa->useScript('bootstrap.offcanvas')
    ->registerAndUseScript('mod_wtjshoppingcart.js','mod_wtjshoppingcart/wtjshoppingcart.js', [], ['defer' => true]);

?>

<noindex>
<div class="offcanvas <?php echo $params->get('bootstrap5_offcanvas_position','offacnvas-end'); ?> <?php echo $params->get('moduleclass_sfx');?>" tabindex="-1" id="wt_jshopping_cart_<?php echo $module->id; ?>" data-wt-jshop-module-cart="<?php echo $module->id; ?>" aria-labelledby="offcanvasLabel">
	<div class="offcanvas-header justify-content-between">
		<h5 class="offcanvas-title" id="offcanvasLabel"><?php echo Text::_('JSHOP_CART'); ?></h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body wt_jshop_module_cart" id="jshop_module_cart<?php echo $module->id; ?>_inner">
		<?php if (count((array)$cart->products) > 0): ?>
			<div class="list-group">
				<?php foreach ($cart->products as $product_number => $product): ?>
					<div class="list-group-item d-flex justify-content-between align-items-start" data-wt-jshop-module-cart-product-number="<?php echo $product_number;?>">
                        <a href="<?php echo $product['href']; ?>" target="_blank" class="flex-grow-1 text-decoration-none d-flex">
                            <div class="wt_jshop_module_cart_product_image">

                                <?php echo HTMLHelper::image($jshopConfig->image_product_live_path . '/' . $product['thumb_image'], $product['product_name'], ['loading' => 'lazy', 'title' => $product['product_name'], 'width' => '64', 'height' => 'auto']); ?>

                            </div>
                            <div class="ms-2 me-auto">
                                <div class="fw-bold"><?php echo $product['product_name']; ?></div>
                                <small class="text-muted"><span data-wt-jshop-module-cart-product-quantity><?php echo $product['quantity'];?></span> &times; <span data-wt-jshop-module-cart-product-price><?php echo JSHelper::formatprice($product['price']); ?></span></small><br/>
                                <?php // Атрибуты товара
                                if ($product['attributes_value'] && count($product['attributes_value']) > 0 && $params->get('show_product_attributes', 0) == 1):
                                    $attributes = [];
                                    foreach ($product['attributes_value'] as $attribute)
                                    {
                                        $attributes[] = $attribute->attr . ': ' . strtolower($attribute->value);
                                    }
                                    ?>
                                    <small class="text-muted"><?php echo implode(', ', $attributes); ?></small>
                                <?php endif; ?>
                                <span class="badge bg-primary rounded-pill" data-wt-jshop-module-cart-product-price-total><?php echo JSHelper::formatprice($product['quantity'] * $product['price']); ?></span>
                            </div>
                        </a>
                        <div class="d-flex flex-column justify-content-start">
                            <button type="button" class="btn btn-sm border-1" data-product-task="delete" data-product-number="<?php echo $product_number;?>">&times;</button>
                            <button type="button" class="btn btn-sm border-1" data-product-task="quantity" data-quantity="up" data-product-number="<?php echo $product_number;?>">+</button>
                            <button type="button" class="btn btn-sm border-1" data-product-task="quantity" data-quantity="down" data-product-number="<?php echo $product_number;?>">-</button>
                        </div>
					</div>
				<?php endforeach; ?>
			</div>

		<?php else: ?>
			<div class="alert alert-info">
				<?php echo Text::_('MOD_WTJSHOPPINGCART_CART_IS_EMPTY'); ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="offcanvas-footer border-1 mt-2 p-2 d-flex flex-column">

			<?php if ($params->get('show_product_weight', 0) == 1): ?>
				<div class="ms-2 me-auto h6">
					<?php echo Text::_('MOD_WTJSHOPPINGCART_CART_WEIGHT_TOTAL'); ?> <?php echo JSHelper::formatweight($cart->getWeightProducts()); ?>
				</div>
			<?php endif; ?>
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

		<?php if ($params->get('show_debug', 0)): ?>
			<details>
				<summary class="btn btn-danger btn-block"><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
				<?php echo '<pre>';
				print_r($cart);
				echo '</pre>'; ?>
			</details>
		<?php endif; ?>
	</div>
</div>
</noindex>
