<?php
/**
 * @package    WT JShopping Cart
 * @version    1.0.5
 * @author Sergey Tolkachyov <https://web-tolk.ru>
 * @сopyright (c) 2022 - April 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link https://web-tolk.ru
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The WT JShopping Cart module service provider.
 *
 * @since  1.0.0
 */
return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		// Основной namespace модуля
		$container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\Wtjshoppingcart'));
		// Namespace модуля для хелпера
		$container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\Wtjshoppingcart\\Site\\Helper'));
		// Namespace модуля для своих типов полей
		$container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\Wtjshoppingcart\\Site\\Fields'));
		$container->registerServiceProvider(new Module);
	}
};