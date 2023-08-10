<?php
/**
 * @package    WT JShopping Cart
 * @version    1.0.3
 * @author Sergey Tolkachyov <https://web-tolk.ru>
 * @сopyright (c) 2022 - August 2023 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link https://web-tolk.ru
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;

/**
 * @var \stdClass               $module   The module
 * @var CMSApplicationInterface $app      The application instance
 * @var Input                   $input    The input instance for $_GET, $_POST data etc.
 * @var Registry                $params   Module params
 * @var Registry                $template Template params
 * @var object                  $cart     JoomShopping Cart object
 *
 * And your own vars wich you can set in your module displatcher
 * src/Dispatcher/Dispatcher.php in function getLayoutData().
 * When you return $data['your_own_variable_name'] function getLayoutData() here
 * you'll access to it via $your_own_variable_name
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

?>
<div id="jshop_module_cart<?php echo $module->id; ?>" class="wt_jshop_module_cart <?php echo $params->get('moduleclass_sfx');?>">
	<?php if ($cart->getSum(0, 1) > 0): ?>
		<ol class="list-group list-group-numbered">

			<?php foreach ($cart->products as $product): ?>
				<a class="list-group-item d-flex justify-content-between align-items-start"
				   href="<?php echo $product['href']; ?>" target="_blank">
					<div class="wt_jshop_module_cart_product_image">

						<?php echo HTMLHelper::image($jshopConfig->image_product_live_path . '/' . $product['thumb_image'], $product['product_name'], ['loading' => 'lazy', 'title' => $product['product_name'], 'width' => '64', 'height' => 'auto']); ?>

					</div>
					<div class="ms-2 me-auto">
						<div class="fw-bold"><?php echo $product['product_name']; ?></div>
						<small class="text-muted"><?php echo $product['quantity'], ' x ', \JSHelper::formatprice($product['price']); ?></small>
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
					<span class="badge bg-primary rounded-pill"><?php echo \JSHelper::formatprice($product['quantity'] * $product['price']); ?></span>
				</a>
			<?php endforeach; ?>
		</ol>

		<?php if ($params->get('show_product_weight', 0) == 1): ?>
			<div class="h6 p-2">
				<?php echo Text::_('MOD_WTJSHOPPINGCART_CART_WEIGHT_TOTAL'); ?> <?php echo JSHelper::formatweight($cart->getWeightProducts()); ?>
			</div>
		<?php endif; ?>

		<div class="border-1 p-2 d-flex">
			<div class="ms-2 me-auto h5">
				<?php echo Text::_('MOD_WTJSHOPPINGCART_CART_TOTAL'); ?> <?php echo \JSHelper::formatprice($cart->getSum(0, 1)); ?>
			</div>
			<div class="btn-group">
				<a class="wt_jshop_module_cart btn btn-outline-primary btn-sm"
				   href="<?php echo \JSHelper::SEFLink('index.php?option=com_jshopping&controller=cart'); ?>"
				   rel="nofollow"
				   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?></a>
				<a class="wt_jshop_module_cart btn btn-primary btn-sm"
				   href="<?php echo \JSHelper::SEFLink('index.php?option=com_jshopping&controller=checkout'); ?>"
				   rel="nofollow"
				   title="<?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?>"><?php echo Text::_('MOD_WTJSHOPPINGCART_CHECKOUT'); ?></a>
			</div>
		</div>
	<?php else: ?>
		<div class="text-info">
			<?php echo Text::_('MOD_WTJSHOPPINGCART_CART_IS_EMPTY'); ?>
		</div>
	<?php endif; ?>
</div>

<?php if ($params->get('show_debug', 0)): ?>
	<details>
		<summary class="btn btn-danger btn-block"><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
		<?php echo '<pre>';
		print_r($cart);
		echo '</pre>'; ?>
	</details>
<?php endif; ?>