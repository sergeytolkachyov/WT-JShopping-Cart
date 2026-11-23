<?php

/**
 * @package    WT JShopping Products AJAX Add to Cart
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (C) 2021 - 2026 Sergey Tolkachyov
 * @license    GNU General Public License version 3 or later
 * @link       https://web-tolk.ru
 * @since      1.2.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Jshoppingproducts\WtJshoppingProductsAjaxAddToCart\Extension\Wtjshoppingproductsajaxaddtocart;

return new class () implements ServiceProviderInterface {
    /**
     * Register the JoomShopping AJAX plugin.
     *
     * @param   Container  $container  The plugin child container.
     *
     * @return  void
     * @since   1.2.0
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $config = (array) PluginHelper::getPlugin('jshoppingproducts', 'wtjshoppingproductsajaxaddtocart');
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin = new Wtjshoppingproductsajaxaddtocart($dispatcher, $config);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
