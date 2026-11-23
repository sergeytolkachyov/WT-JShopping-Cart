<?php

namespace Joomla\CMS\Application {
    interface CMSApplicationInterface {}
}

namespace Joomla\CMS\Plugin {
    class PluginHelper
    {
        public static $params = [];

        public static function getPlugin($group, $name)
        {
            if ($group !== 'jshoppingproducts' || $name !== 'wtjshoppingproductsajaxaddtocart') {
                throw new \RuntimeException('Unexpected plugin identity');
            }
            return ['type' => $group, 'name' => $name, 'params' => json_encode(self::$params)];
        }
    }
}

namespace Joomla\Component\Jshopping\Site\Lib {
    class JSFactory
    {
        public static $loads = 0;

        public static function getConfig()
        {
            return (object) ['use_decimal_qty' => 1, 'cart_decimal_qty_precision' => 2];
        }

        public static function getModel($name, $client)
        {
            if ($name !== 'Cart' || $client !== 'Site') {
                throw new \RuntimeException('Unexpected cart model');
            }
            return new class () {
                public $products = [['product_id' => 9, 'quantity' => 0.1], ['product_id' => 9, 'quantity' => 0.2]];
                public $count_product = 0.3;
                public $price_product = 30;
                public function load($type) { JSFactory::$loads++; }
            };
        }
    }
}

namespace Joomla\Component\Jshopping\Site\Helper {
    class Helper
    {
        public static function SEFLink($url, $item, $redirect)
        {
            return '/shop/cart/add?Itemid=8';
        }
    }
}

namespace {
    $root = dirname(__DIR__, 3);
    $core = rtrim($argv[1], '/\\');
    $source = $argv[2] ?? $root;
    $pluginRoot = $source . '/plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart';
    $runtime = $root . '/.pf/runtime/plugin-ajax-settings/joomla-' . basename($core);
    if (!is_dir($runtime)) {
        mkdir($runtime, 0777, true);
    }
    define('_JEXEC', 1);
    define('JVERSION', basename($core));
    define('JDEBUG', false);
    define('JPATH_ROOT', $runtime);
    define('JPATH_SITE', $runtime);
    define('JPATH_ADMINISTRATOR', $runtime . '/administrator');
    define('JPATH_PLUGINS', $runtime . '/plugins');
    define('JPATH_PLATFORM', $core . '/libraries');
    define('JPATH_LIBRARIES', $core . '/libraries');
    define('JPATH_BASE', JPATH_ADMINISTRATOR);

    $loader = require $core . '/libraries/vendor/autoload.php';
    $loader->addPsr4('Joomla\\CMS\\', $core . '/libraries/src');
    $loader->addPsr4('Joomla\\Plugin\\Jshoppingproducts\\WtJshoppingProductsAjaxAddToCart\\', $pluginRoot . '/src');
    require_once $core . '/libraries/loader.php';
    \JLoader::setup();

    $assets = new \Joomla\CMS\WebAsset\WebAssetManager(new \Joomla\CMS\WebAsset\WebAssetRegistry());
    $assets->registerScript('core', 'system/core.js')->registerScript('messages', 'system/messages.js');
    $document = new \Joomla\CMS\Document\HtmlDocument(['webAssetManager' => $assets]);
    $siteLanguageDir = JPATH_ROOT . '/language/en-GB';
    if (!is_dir($siteLanguageDir)) {
        mkdir($siteLanguageDir, 0777, true);
    }
    foreach (['langmetadata.xml', 'localise.php'] as $languageFile) {
        if (is_file($core . '/language/en-GB/' . $languageFile)) {
            copy($core . '/language/en-GB/' . $languageFile, $siteLanguageDir . '/' . $languageFile);
        }
    }
    $language = new \Joomla\CMS\Language\Language('en-GB');
    file_put_contents($siteLanguageDir . '/com_jshopping.ini', "JSHOP_BUY=\"Buy\"\nJSHOP_ADD_TO_CART=\"Add to cart\"\n");
    $language->load('com_jshopping', JPATH_ROOT);
    $app = new class ($document, $language) implements \Joomla\CMS\Application\CMSApplicationInterface {
        public $client = 'site';
        private $document;
        private $language;
        public function __construct($document, $language) { $this->document = $document; $this->language = $language; }
        public function getDocument() { return $this->document; }
        public function getLanguage() { return $this->language; }
        public function isClient($name) { return $this->client === $name; }
        public function getInput() { return $this; }
        public function getCmd($name) { return 'com_jshopping'; }
        public function get($name, $default = null) { return $default; }
    };
    \Joomla\CMS\Factory::$application = $app;
    \Joomla\CMS\Factory::$document = $document;
    \Joomla\CMS\Factory::$language = $language;
    \Joomla\CMS\Factory::$config = new \Joomla\Registry\Registry(['debug_lang' => false]);
    $languageDir = JPATH_ADMINISTRATOR . '/language/en-GB';
    if (!is_dir($languageDir)) {
        mkdir($languageDir, 0777, true);
    }
    $extension = 'plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart';
    copy($pluginRoot . '/language/en-GB/' . $extension . '.ini', $languageDir . '/' . $extension . '.ini');

    $results = [];
    $check = static function ($condition, $description) use (&$results) {
        if (!$condition) {
            throw new \RuntimeException($description);
        }
        $results[] = ['check' => $description, 'passed' => true];
    };
    $manifest = simplexml_load_file($pluginRoot . '/wtjshoppingproductsajaxaddtocart.xml');
    $installer = new class ($manifest, $pluginRoot) extends \Joomla\CMS\Installer\Installer {
        public function __construct($manifest, $source)
        {
            $this->manifest = $manifest;
            $this->setPath('source', $source);
            $this->setOverwrite(true);
        }
    };
    $check($installer->parseMedia($manifest->media, 0) !== false, 'Native Joomla installer installs plugin media');
    $media = JPATH_ROOT . '/media/' . $extension . '/js/wtjshopping-add-to-cart.js';
    $check(is_file($media) && hash_file('sha256', $media) === hash_file('sha256', $pluginRoot . '/media/js/wtjshopping-add-to-cart.js'), 'Installed script belongs to plugin media');
    $check(!is_file(JPATH_ROOT . '/media/mod_wtjshoppingcart/js/wtjshopping-add-to-cart.js'), 'Plugin does not depend on an old module copy');

    $selectors = [
        'ajax_list_button_selector' => 'body > div.product > :is(button#list-buy, button#custom-buy)',
        'ajax_product_button_selector' => 'body > form#product-form > :is(button[type="submit"], button.alternate)',
        'ajax_list_quantity_selector' => 'body > div.product > :is(input[site-qty], input[other-qty])',
        'ajax_product_quantity_selector' => 'body > form#product-form > input[name="quantity"]',
    ];
    \Joomla\CMS\Plugin\PluginHelper::$params = $selectors;
    $document->addScriptOptions('wt_jshop_module_cart_module_options', ['modules' => [
        11 => ['list_button_selector' => '[obsolete-first]'], 12 => ['list_button_selector' => '[obsolete-second]'],
    ]]);
    $dispatcher = new \Joomla\Event\Dispatcher();
    $container = new \Joomla\DI\Container();
    $container->share(\Joomla\Event\DispatcherInterface::class, static function () use ($dispatcher) { return $dispatcher; });
    $provider = require $pluginRoot . '/services/provider.php';
    $provider->register($container);
    $plugin = $container->get(\Joomla\CMS\Extension\PluginInterface::class);
    $plugin->registerListeners();
    $product = (object) ['product_id' => 9];
    $related = (object) ['product_id' => 9];
    $view = (object) ['product' => $product, 'related_prod' => [$related]];
    $list = (object) ['products' => [$product]];
    $dispatcher->dispatch('onBeforeDisplayProductListView', new \Joomla\Event\Event('onBeforeDisplayProductListView', [$view, $list]));
    $dispatcher->dispatch('onBeforeDisplayProductView', new \Joomla\Event\Event('onBeforeDisplayProductView', [$view]));
    $options = $document->getScriptOptions('wt_jshop_add_to_cart');
    if (JVERSION === '5.4.8') {
        file_put_contents(dirname($runtime) . '/plugin-options.json', json_encode($options, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    foreach ($selectors as $key => $value) {
        $check($options[substr($key, 5)] === $value, 'Publish full plugin selector unchanged: ' . $key);
    }
    $check($product->wtAjaxCartQuantity === 0.3 && $related->wtAjaxCartQuantity === 0.3, 'Product and related quantities retain variant aggregation');
    $check(\Joomla\Component\Jshopping\Site\Lib\JSFactory::$loads === 1, 'Read cart once for all product view events');
    $name = $extension . '.add-to-cart';
    $check($assets->getAsset('script', $name)->getUri(false) === $extension . '/wtjshopping-add-to-cart.js', 'WebAssetManager registers the plugin URI');
    $registered = $assets->getAssets('script', true);
    $check(count(array_filter($registered, static function ($asset) use ($name) { return $asset->getName() === $name; })) === 1, 'One plugin script for repeated events and two module instances');
    $check(in_array('mod_wtjshoppingcart.js', $assets->getAsset('script', $name)->getDependencies(), true), 'Preserve the shared cart state and mutation queue dependency');
    $check($options['added_button_text'] === 'In cart', 'Native CMSPlugin loads its own translations');

    $moduleManifest = simplexml_load_file($source . '/mod_wtjshoppingcart/mod_wtjshoppingcart.xml');
    foreach ($selectors as $key => $value) {
        $fields = $manifest->xpath('//field[@name="' . $key . '"]');
        $check(count($fields) === 1 && (string) $fields[0]['filter'] === 'raw' && !$moduleManifest->xpath('//field[@name="' . $key . '"]'), 'A selector exists only in plugin configuration: ' . $key);
    }
    foreach (['en-GB', 'ru-RU'] as $locale) {
        $strings = parse_ini_file($pluginRoot . '/language/' . $locale . '/' . $extension . '.ini', false, INI_SCANNER_RAW);
        foreach ($manifest->xpath('//field[@label] | //fieldset[@label]') as $field) {
            $check(isset($strings[(string) $field['label']]), 'Translated plugin form label: ' . $locale . '/' . (string) $field['name']);
        }
        foreach (['LIST', 'PRODUCT'] as $type) {
            $key = strtoupper($extension) . '_FIELD_AJAX_' . $type . '_TEMPLATE_HELP_DESC';
            $dom = new \DOMDocument();
            $dom->loadHTML('<meta charset="UTF-8">' . $strings[$key], LIBXML_NOERROR | LIBXML_NOWARNING);
            foreach ((new \DOMXPath($dom))->query('//pre/code') as $code) {
                $php = $code->textContent;
                token_get_all(strpos($php, '<?php') === false ? '<?php ' . $php : $php, TOKEN_PARSE);
            }
            $check(true, 'Moved template examples parse: ' . $locale . '/' . $type);
        }
    }
    $app->client = 'administrator';
    $isolated = (object) ['product_id' => 9];
    $dispatcher->dispatch('onBeforeDisplayProductView', new \Joomla\Event\Event('onBeforeDisplayProductView', [(object) ['product' => $isolated]]));
    $check(!isset($isolated->wtAjaxCartInCart), 'Do not run shop behavior in the administrator');
    $report = ['joomla' => JVERSION, 'php' => PHP_VERSION, 'passed' => count($results), 'failed' => 0, 'results' => $results];
    file_put_contents($runtime . '/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
