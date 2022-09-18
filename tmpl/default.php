<?php
/**
 * @package    WT JShopping Cart
 * @author     Sergey Tolkachyov, info@web-tolk.ru https://web-tolk.ru
 * @copyright  Copyright (C) 2022 Sergey Tolkachyov. All rights reserved.
 * @license    GNU General Public License version 3 or later
 * @link       https://web-tolk.ru/en/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4.html
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
<div id = "wt_jshop_module_cart<?php echo $module->id;?>" class="wt_jshop_module_cart">
<table width = "100%" >
<tr>
    <td>
      <span id = "jshop_quantity_products" class="digit"><?php echo $cart->count_product?></span>&nbsp;<?php print Text::_('PRODUCTS')?>
    </td>
    <td>-</td>
    <td>
      <span id = "jshop_summ_product" class="summ"><?php echo JSHelper::formatprice($cart->getSum(0,1))?></span>
    </td>
</tr>
<tr>
    <td colspan="3" align="right">
      <a href = "<?php echo \JSHelper::SEFLink('index.php?option=com_jshopping&controller=cart&task=view', 1)?>"><?php print Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART')?></a>
    </td>
</tr>
</table>
</div>