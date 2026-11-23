<?php

/**
 * @package       WT JShopping Cart
 * @version       __DEPLOY_VERSION__
 * @author        Sergey Tolkachyov
 * @copyright     Copyright (c) 2026 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link          https://web-tolk.ru
 * @since         __DEPLOY_VERSION__
 */

declare(strict_types=1);

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    /** @since 1.2.0 */
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
                /** @since 1.2.0 */
                private AdministratorApplication $app;
                /** @since 1.2.0 */
                private string $minimumJoomla = '4.2.0';
                /** @since 1.2.0 */
                private string $minimumPhp = '7.4';

                /** @since 1.2.0 */
                public function __construct(AdministratorApplication $app)
                {
                    $this->app = $app;
                }

                /** @since 1.2.0 */
                public function install(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                /** @since 1.2.0 */
                public function uninstall(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                /** @since 1.2.0 */
                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                /** @since 1.2.0 */
                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    return $this->checkCompatible();
                }

                /** @since 1.2.0 */
                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    $smile = '';

                    if ($type !== 'uninstall') {
                        $smiles = ['&#9786;', '&#128512;', '&#128521;', '&#128525;', '&#128526;', '&#128522;', '&#128591;'];
                        $smile  = $smiles[array_rand($smiles)];
                    }

                    $typeUpper = strtoupper($type);
                    $html      = '
					<div class="row m-0">
						<div class="col-12 col-md-8 p-0 pe-2">
							<h2>' . $smile . ' ' . Text::_('PKG_WTJSHOPPINGCART_AFTER_' . $typeUpper) . ' <br/>' . Text::_('PKG_WTJSHOPPINGCART') . '</h2>
							' . Text::_('PKG_WTJSHOPPINGCART_XML_DESCRIPTION') . '
							' . Text::_('PKG_WTJSHOPPINGCART_WHATS_NEW') . '
						</div>
						<div class="col-12 col-md-4 p-0 d-flex flex-column justify-content-start">
							<img width="180" src="https://web-tolk.ru/web_tolk_logo_wide.png" alt="WebTolk">
							<p>Joomla Extensions</p>
							<p class="btn-group">
								<a class="btn btn-sm btn-outline-primary" href="https://web-tolk.ru" target="_blank" rel="noopener noreferrer">https://web-tolk.ru</a>
								<a class="btn btn-sm btn-outline-primary" href="mailto:info@web-tolk.ru"><i class="icon-envelope"></i> info@web-tolk.ru</a>
							</p>
							<div class="btn-group-vertical mb-3 web-tolk-btn-links" role="group" aria-label="WebTolk community links">
								<a class="btn btn-danger text-white w-100" href="https://t.me/joomlaru" target="_blank" rel="noopener noreferrer">' . Text::_('PKG_WTJSHOPPINGCART_JOOMLARU_TELEGRAM_CHAT') . '</a>
								<a class="btn btn-primary text-white w-100" href="https://t.me/webtolkru" target="_blank" rel="noopener noreferrer">' . Text::_('PKG_WTJSHOPPINGCART_WEBTOLK_TELEGRAM_CHANNEL') . '</a>
								<a class="btn btn-success text-white w-100" href="https://max.ru/join/LChBfwGDmArJpK6--oS0qVAJA1WdRk0OPXciwryF4ZY" target="_blank" rel="noopener noreferrer">' . Text::_('PKG_WTJSHOPPINGCART_MAX_CHANNEL') . '</a>
							</div>
							' . Text::_('PKG_WTJSHOPPINGCART_MAYBE_INTERESTING') . '
						</div>
					</div>';

                    $this->app->enqueueMessage($html, 'info');

                    return true;
                }

                /** @since 1.2.0 */
                private function checkCompatible(): bool
                {
                    if (!(new Version())->isCompatible($this->minimumJoomla)) {
                        $this->app->enqueueMessage(
                            Text::sprintf('PKG_WTJSHOPPINGCART_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
                            'error'
                        );

                        return false;
                    }

                    if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0)) {
                        $this->app->enqueueMessage(
                            Text::sprintf('PKG_WTJSHOPPINGCART_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
                            'error'
                        );

                        return false;
                    }

                    return true;
                }
            }
        );
    }
};
