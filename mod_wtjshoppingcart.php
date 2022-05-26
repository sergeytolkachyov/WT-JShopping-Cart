<?php
/**
 * @package    WT JShopping Cart
 * @author     Sergey Tolkachyov, info@web-tolk.ru https://web-tolk.ru
 * @copyright  Copyright (C) 2022 Sergey Tolkachyov. All rights reserved.
 * @license    GNU General Public License version 3 or later
 * @link 	   https://web-tolk.ru/en/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4.html
 */
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\Module\Wtjshoppingcart\Site\Helper\WtjshoppingcartHelper;


if (!file_exists(JPATH_SITE . '/administrator/components/com_jshopping/jshopping.xml'))
{
	throw new Exception('Please install JoomShopping', 500);
}
if(!class_exists('JSHelper')){
	require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
}

$jshopConfig = \JSFactory::getConfig();
\JSFactory::loadCssFiles();
\JSFactory::loadLanguageFile();

$cart = WtjshoppingcartHelper::getCart($params);

/*
 * Take a css file for tmpl with the same name form media folder
 */
if(!empty($params->get('layout')))
{
	$doc = Factory::getDocument();
	$tmpl_css = explode(':', $params->get('layout'));
	$tmpl_css_file = $tmpl_css[1];
	if(file_exists('media/mod_wtjshoppingcart/css/'.$tmpl_css_file.'.css')){
		$doc->addStyleSheet('media/mod_wtjshoppingcart/css/'.$tmpl_css_file.'.css');
	}
}

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'), ENT_COMPAT, 'UTF-8');

require ModuleHelper::getLayoutPath('mod_wtjshoppingcart', $params->get('layout', 'default'));

?>