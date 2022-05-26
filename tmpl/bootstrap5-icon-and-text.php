<?php
/**
 * @package    WT JShopping Cart
 * @author     Sergey Tolkachyov, info@web-tolk.ru https://web-tolk.ru
 * @copyright  Copyright (C) 2022 Sergey Tolkachyov. All rights reserved.
 * @license    GNU General Public License version 3 or later
 * @link 	   https://web-tolk.ru/en/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4.html
 */
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Language\Text;

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

<a id="jshop_module_cart<?php echo $module->id;?>"  role="button" class="btn btn-light btn-sm position-relative d-inline-flex flex-column p-1 wt_jshop_module_cart wt-jshopping-cart-module-icon <?php echo $moduleclass_sfx;?>" href="<?php print \JSHelper::SEFLink('index.php?option=com_jshopping&view=cart', 1);?>" rel="nofollow" title="<?php print Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART');?>">
	<i class="<?php echo $params->get('icon_css_class', 'fas fa-shopping-cart');?>"></i> <?php print Text::_('MOD_WTJSHOPPINGCART_CART');?>
	<span class="position-absolute top-0 start-100 translate-middle badge border-light rounded-pill bg-danger digit">
		<?php echo $cart->count_product;?>
		<span class="visually-hidden"><?php print Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART');?></span>
	</span>
	<?php if($cart->getSum(0,1) > 0):?>

		<span class="font-weight-bold summ"><?php print JSHelper::formatprice($cart->getSum(0,1));?></span>

	<?php else:?>
		<span class="summ"><?php print Text::_('MOD_WTJSHOPPINGCART_CART');?></span>
	<?php endif;?>
</a>
<?php if ($params->get('show_debug', 0)): ?>
	<details>
		<summary class="btn btn-danger btn-block"><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
		<?php echo '<pre>';
		print_r($cart);
		echo '</pre>'; ?>
	</details>
<?php endif; ?>