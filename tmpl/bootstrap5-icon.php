<?php
/**
 * @package    WT JShopping Cart
 * @version    1.0.5
 * @author Sergey Tolkachyov <https://web-tolk.ru>
 * @сopyright (c) 2022 - April 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link https://web-tolk.ru
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
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

	<a id="jshop_module_cart<?php echo $module->id; ?>" role="button"
	   class="btn position-relative wt_jshop_module_cart wt-jshopping-cart-module-icon <?php echo $params->get('moduleclass_sfx'); ?>"
	   href="<?php echo \JSHelper::SEFLink('index.php?option=com_jshopping&controller=cart', 1); ?>" rel="nofollow"
	   title="<?php print Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?>">
		<i class="<?php echo $params->get('icon_css_class', 'fas fa-shopping-cart'); ?>"></i>
		<span class="position-absolute top-0 start-100 translate-middle badge border-light rounded-pill bg-danger digit">
			<?php echo $cart->count_product; ?>
			<span class="visually-hidden"><?php print Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'); ?></span>
		</span>
	</a>
<?php if ($params->get('show_debug', 0)): ?>
	<details>
		<summary class="btn btn-danger btn-block"><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
		<?php echo '<pre>';
		print_r($cart);
		echo '</pre>'; ?>
	</details>
<?php endif; ?>