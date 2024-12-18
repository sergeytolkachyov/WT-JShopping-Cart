<?php
/**
 * @package    WT JShopping Cart
 * @version    1.1.1
 * @author     Sergey Tolkachyov
 * @сopyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */

namespace Joomla\Module\Wtjshoppingcart\Site\Dispatcher;

\defined('JPATH_PLATFORM') or die;

use \Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Extension\ModuleInterface;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Input\Input;
use Joomla\Module\Wtjshoppingcart\Site\Helper\WtjshoppingcartHelper;

/**
 * Dispatcher class for mod_wtyandexmapitems
 *
 * @since  1.1.0
 */
class Dispatcher extends AbstractModuleDispatcher
{

	/**
	 * The module extension. Used to fetch the module helper.
	 *
	 * @var   ModuleInterface|null
	 * @since 1.1.0
	 */
	private $moduleExtension;


	public function __construct(\stdClass $module, CMSApplicationInterface $app, Input $input)
	{
		parent::__construct($module, $app, $input);

		$this->moduleExtension = $this->app->bootModule('mod_wtjshoppingcart', 'site');
	}

	/**
	 * Returns the layout data.
	 *
	 * @return  array
	 *
	 * @since   1.1.0
	 */
	protected function getLayoutData() : array
	{
		if (!file_exists(JPATH_SITE . '/administrator/components/com_jshopping/jshopping.xml'))
		{
			throw new Exception('Please install JoomShopping', 500);
		}
		require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');

		$data = parent::getLayoutData();

		JSFactory::loadLanguageFile();

		$data['jshopConfig'] = JSFactory::getConfig();
		$data['cart'] = WtjshoppingcartHelper::getCart($data['params'], $this->getApplication());

		return $data;
	}
}
