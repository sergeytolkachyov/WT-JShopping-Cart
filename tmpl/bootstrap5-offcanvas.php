<?php
/**
 * @package    WT JShopping Cart
 * @author     Sergey Tolkachyov, info@web-tolk.ru https://web-tolk.ru
 * @copyright  Copyright (C) 2022 Sergey Tolkachyov. All rights reserved.
 * @license    GNU General Public License version 3 or later
 * @link 	   https://web-tolk.ru/en/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4.html
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
$jshopConfig = \JSFactory::getConfig();
/**
 *  Use Bootstrap 5 Offcanvas
 * @link https://getbootstrap.com/docs/5.1/components/offcanvas/
 */

/**
 * Module params
 * echo '<pre';
 * print_r($params);
 * echo '</pre';
 *
 * Get module param - $param->get('param_name');
 ************
 * JoomShopping cart data
 * * echo '<pre';
 * print_r($cart);
 * echo '</pre';
 *
 * Get cart data
 * echo $cart->param_name
 */


/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('bootstrap.offcanvas');

?>
<noindex>
<div class="offcanvas <?php echo $params->get('bootstrap5_offcanvas_position','offacnvas-end'); ?> <?php echo $moduleclass_sfx;?>" tabindex="-1" id="wt_jshopping_cart_<?php echo $module->id; ?>"
	 aria-labelledby="offcanvasLabel">
	<div class="offcanvas-header">
		<h5 class="offcanvas-title" id="offcanvasLabel"><?php echo Text::_('JSHOP_CART'); ?></h5>
		<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body wt_jshop_module_cart" id="jshop_module_cart<?php echo $module->id; ?>_inner">
		<?php if ($cart->getSum(0, 1) > 0): ?>
			<div class="list-group">

				<?php foreach ($cart->products as $product): ?>
					<a class="list-group-item d-flex justify-content-between align-items-start"
					   href="<?php echo $product['href']; ?>" target="_blank">
						<div class="wt_jshop_module_cart_product_image">

							<?php echo HTMLHelper::image($jshopConfig->image_product_live_path . '/' . $product['thumb_image'], $product['product_name'], ['loading' => 'lazy', 'title' => $product['product_name'], 'width' => '64', 'height' => 'auto']); ?>

						</div>
						<div class="ms-2 me-auto">
							<div class="fw-bold"><?php echo $product['product_name']; ?></div>
							<small class="text-muted"><?php echo $product['quantity'], ' x ', JSHelper::formatprice($product['price']); ?></small><br/>
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
						</div>
						<span class="badge bg-primary rounded-pill"><?php echo JSHelper::formatprice($product['quantity'] * $product['price']); ?></span>
					</a>
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
				<?php echo Text::_('MOD_WTJSHOPPINGCART_CART_TOTAL'); ?> <?php echo JSHelper::formatprice($cart->getSum(0, 1)); ?>
			</div>
			<div class="btn-group">
				<a class="wt_jshop_module_cart btn btn-outline-primary btn-sm"
				   href="<?php echo \JSHelper::SEFLink('index.php?option=com_jshopping&view=cart', 1); ?>"
				   rel="nofollow"
				   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?></a>
				<a class="wt_jshop_module_cart btn btn-primary btn-sm"
				   href="<?php echo \JSHelper::SEFLink('index.php?option=com_jshopping&view=checkout'); ?>"
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
