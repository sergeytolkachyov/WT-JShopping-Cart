<?php

namespace Joomla\Component\Jshopping\Site\Helper {
    class Helper
    {
        public static function formatprice($value)
        {
            return number_format((float) $value, 2, '.', '') . ' RUB';
        }

        public static function SEFLink($url, $item = 0)
        {
            return '/shop/' . (strpos($url, 'checkout') !== false ? 'checkout' : 'cart');
        }
    }
}

namespace {
    $root = dirname(__DIR__, 3);
    $moduleRoot = $argv[2] ?? $root . '/mod_wtjshoppingcart';
    $core = rtrim($argv[1], '/\\');
    $reportRoot = $argv[3] ?? $root . '/.pf/runtime/cart-template-sync';
    $runtime = rtrim($reportRoot, '/\\') . '/joomla-' . basename($core);
    if (!is_dir($runtime)) {
        mkdir($runtime, 0777, true);
    }
    define('_JEXEC', 1);
    define('JVERSION', basename($core));
    define('JDEBUG', false);
    define('JPATH_ROOT', $runtime);
    define('JPATH_SITE', $runtime);
    define('JPATH_ADMINISTRATOR', $runtime . '/administrator');
    define('JPATH_INSTALLATION', $runtime . '/installation');
    define('JPATH_API', $runtime . '/api');
    define('JPATH_CLI', $runtime . '/cli');
    define('JPATH_THEMES', $runtime . '/templates');
    define('JPATH_PLATFORM', $core . '/libraries');
    define('JPATH_LIBRARIES', $core . '/libraries');
    define('JPATH_BASE', JPATH_SITE);

    $loader = require $core . '/libraries/vendor/autoload.php';
    $loader->addPsr4('Joomla\\CMS\\', $core . '/libraries/src');
    $installedModule = JPATH_SITE . '/modules/mod_wtjshoppingcart';
    $loader->addPsr4('Joomla\\Module\\Wtjshoppingcart\\Site\\', $installedModule . '/src');
    require_once $core . '/libraries/loader.php';
    \JLoader::setup();

    $languageDir = $runtime . '/language/en-GB';
    if (!is_dir($languageDir)) {
        mkdir($languageDir, 0777, true);
    }
    copy($core . '/language/en-GB/langmetadata.xml', $languageDir . '/langmetadata.xml');
    \Joomla\CMS\Factory::$config = new \Joomla\Registry\Registry(['debug_lang' => false]);
    \Joomla\CMS\Factory::$language = new \Joomla\CMS\Language\Language('en-GB');
    \Joomla\CMS\Factory::$application = new class () {
        public $input;
        public function __construct() { $this->input = new \Joomla\Input\Input(['option' => '']); }
        public function getInput() { return $this->input; }
        public function getLanguage() { return \Joomla\CMS\Factory::$language; }
        public function get($name, $default = null) { return $default; }
        public function isClient($name) { return $name === 'site'; }
        public function getTemplate($params = false) { return $params ? (object) ['template' => 'fixture', 'parent' => ''] : 'fixture'; }
    };
    \Joomla\CMS\Factory::$language->load('mod_wtjshoppingcart', $moduleRoot);
    class FixtureLayoutCache extends \Joomla\CMS\Layout\FileLayout
    {
        public static function reset() { static::$cache = []; }
    }
    $uikitOverride = JPATH_THEMES . '/fixture/html/layouts/modules/mod_wtjshoppingcart/product/uikit.php';
    if (is_file($uikitOverride)) {
        unlink($uikitOverride);
    }
    $results = [];
    $check = static function ($condition, $description) use (&$results) {
        if (!$condition) {
            throw new \RuntimeException($description);
        }
        $results[] = ['check' => $description, 'passed' => true];
    };
    $manifest = simplexml_load_file($moduleRoot . '/mod_wtjshoppingcart.xml');
    $installer = new class ($manifest, $moduleRoot) extends \Joomla\CMS\Installer\Installer {
        public function __construct($manifest, $source)
        {
            $this->manifest = $manifest;
            $this->setPath('source', $source);
            $this->setOverwrite(true);
        }
    };
    $check(!isset($manifest->scriptfile) && !isset($manifest->layouts), 'The module uses only standard installation manifest sections');
    $installer->setPath('extension_root', $installedModule);
    $check($installer->parseFiles($manifest->files, -1) !== false, 'Joomla installs the declared module files');
    $rowDirectory = 'layouts/modules/mod_wtjshoppingcart/product';
    $installed = $installedModule . '/' . $rowDirectory . '/default.php';
    $source = $moduleRoot . '/' . $rowDirectory . '/default.php';
    $check(is_file($installed) && hash_file('sha256', $installed) === hash_file('sha256', $source), 'Bootstrap row is installed inside the module');
    $check(is_file(dirname($installed) . '/uikit.php'), 'UIkit row is installed inside the module');

    $sharedRows = JPATH_ROOT . '/' . $rowDirectory;
    if (!is_dir($sharedRows)) {
        mkdir($sharedRows, 0777, true);
    }
    $oldRow = $sharedRows . '/default.php';
    file_put_contents($oldRow, 'old shared row');
    file_put_contents($installed, 'old layout');
    $check($installer->parseFiles($manifest->files, -1) !== false && hash_file('sha256', $installed) === hash_file('sha256', $source), 'Update replaces the bundled row');
    $check(file_get_contents($oldRow) === 'old shared row', 'Update preserves the old shared file');

    $params = new \Joomla\Registry\Registry(['show_image' => 1, 'show_product_attributes' => 1]);
    $data = [
        'params' => $params,
        'jshopConfig' => (object) ['image_product_live_path' => '/images/'],
        'productNumber' => 4,
        'product' => ['product_name' => '<Variant>', 'quantity' => 1.5, 'price' => 10.25,
            'attributes_value' => [(object) ['attr' => 'Colour', 'value' => '<Blue>']],
            'free_attributes_value' => [(object) ['attr' => 'Text', 'value' => 'Custom']]],
    ];
    $render = static function () use (&$data) { return \Joomla\Module\Wtjshoppingcart\Site\Helper\WtjshoppingcartHelper::renderProduct($data); };
    $html = $render();
    $check(strpos($html, '&lt;Variant&gt;') !== false && strpos($html, '15.38 RUB') !== false, 'Bundled row takes priority over the old shared copy and renders escaped names and fractional totals');
    $check(strpos($html, 'Colour: &lt;Blue&gt;, Text: Custom') !== false, 'Native layout renders variant and free attributes');
    $params->set('cart_product_layout', '../invalid');
    $check(strpos($render(), '&lt;Variant&gt;') !== false, 'Unsafe layout names fall back to default');
    $params->set('cart_product_layout', 'missing');
    $check(strpos($render(), '&lt;Variant&gt;') !== false, 'Missing layouts fall back to default');

    $alternate = $sharedRows . '/alternate.php';
    file_put_contents($alternate, '<?php echo "shared alternate";');
    $override = JPATH_THEMES . '/fixture/html/layouts/modules/mod_wtjshoppingcart/product/alternate.php';
    if (!is_dir(dirname($override))) {
        mkdir(dirname($override), 0777, true);
    }
    $params->set('cart_product_layout', 'alternate');
    if (is_file($override)) {
        unlink($override);
    }
    $check($render() === 'shared alternate', 'A previously selected custom shared row still renders');
    file_put_contents($override, '<?php echo "template override";');
    FixtureLayoutCache::reset();
    $check($render() === 'template override', 'Standard Joomla template overrides take precedence');
    $params->set('show_image', 0);
    $params->set('show_product_attributes', 0);
    $params->set('cart_product_layout', 'default');
    $html = $render();
    $check(strpos($html, 'data-wt-jshop-module-cart-product-image') === false && strpos($html, 'data-wt-jshop-module-cart-product-attributes') === false, 'Each module retains its image and attribute settings');
    $field = $manifest->config->fields->fieldset[0]->xpath('field[@name="cart_product_layout"]')[0];
    $input = new \Joomla\CMS\Form\Field\TextField();
    $input->setup($field, 'alternate');
    $check((string) $field['type'] === 'text' && $input->value === 'alternate', 'Module settings retain custom names independently of the shared directory');

    $params->set('show_image', 1);
    $params->set('show_product_attributes', 1);
    $data['defaultProductLayout'] = 'uikit';
    $html = $render();
    $check(strpos($html, 'uk-grid') !== false && strpos($html, '&lt;Variant&gt;') !== false && strpos($html, '15.38 RUB') !== false, 'UIkit parent selects its native row with escaped names and fractional totals');
    $check(strpos($html, 'Colour: &lt;Blue&gt;, Text: Custom') !== false && strpos($html, 'aria-label="Remove product"') !== false, 'UIkit row renders attributes and translated action labels');
    $check(strpos($html, 'data-product-task="delete"') !== false && strpos($html, 'data-quantity="up"') !== false && strpos($html, 'data-product-number="4"') !== false, 'UIkit row retains delegated AJAX controls and product keys');
    $params->set('cart_product_layout', 'missing');
    $check(strpos($render(), 'uk-grid') !== false, 'Missing custom row falls back to the parent UIkit layout');
    $params->set('cart_product_layout', 'alternate');
    $check($render() === 'template override', 'Explicit custom row keeps priority in a UIkit parent');
    $params->set('cart_product_layout', 'default');
    $uikitOverride = dirname($override) . '/uikit.php';
    file_put_contents($uikitOverride, '<?php echo "UIkit template override";');
    FixtureLayoutCache::reset();
    $check($render() === 'UIkit template override', 'UIkit default row supports standard template overrides');
    unlink($uikitOverride);
    FixtureLayoutCache::reset();
    $params->set('show_image', 0);
    $params->set('show_product_attributes', 0);
    $html = $render();
    $check(strpos($html, 'data-wt-jshop-module-cart-product-image') === false && strpos($html, 'data-wt-jshop-module-cart-product-attributes') === false, 'UIkit row respects each module image and attribute settings');
    $data['defaultProductLayout'] = '../unsafe';
    $check(strpos($render(), 'uk-grid') === false, 'Unsafe parent default falls back to the original Bootstrap row');

    $renderModule = static function ($layout, $id, $values, $products = [], $sum = 15.375) use ($installedModule) {
        $params = new \Joomla\Registry\Registry($values);
        $module = (object) ['id' => $id];
        $jshopConfig = (object) ['image_product_live_path' => '/images/'];
        $cart = new class ($products, $sum) {
            public $products;
            public $count_product = 1.5;
            private $sum;
            public function __construct($products, $sum) { $this->products = $products; $this->sum = $sum; }
            public function getSum($tax, $currency) { return $this->sum; }
        };
        ob_start();
        include $installedModule . '/tmpl/' . $layout . '.php';
        return ob_get_clean();
    };
    $freeIcon = $renderModule('bootstrap5-icon-and-text', 89, [], [$data['product']], 0);
    $emptyIcon = $renderModule('bootstrap5-icon-and-text', 90, [], [], 0);
    $check(strpos($freeIcon, 'data-wt-jshop-module-cart-price-total>0.00 RUB</span>') !== false && strpos($freeIcon, 'data-wt-jshop-module-cart-empty hidden') !== false, 'The icon displays a fully discounted nonempty cart as zero');
    $check(strpos($emptyIcon, 'data-wt-jshop-module-cart-price-total hidden') !== false && strpos($emptyIcon, 'data-wt-jshop-module-cart-empty>') !== false, 'The empty icon keeps its cart label');
    $button = $renderModule('uikit-icon-btn', 91, ['bs_target_id' => 92]);
    $check(strpos($button, 'uk-toggle="target: #wt_jshopping_cart_92"') !== false && strpos($button, 'data-wt-jshop-module-cart="91"') !== false && strpos($button, 'data-wt-jshop-module-cart-count') !== false && strpos($button, '>1.5</span>') !== false, 'UIkit button targets the selected panel and exposes the shared counter');
    $disabled = $renderModule('uikit-icon-btn', 93, []);
    $check(strpos($disabled, 'disabled aria-disabled="true"') !== false && strpos($disabled, 'uk-toggle=') === false, 'Unconfigured UIkit button cannot open an unrelated panel');
    $panel = $renderModule('uikit-offcanvas', 92, [], [$data['product']]);
    $left = $renderModule('uikit-offcanvas', 94, ['bootstrap5_offcanvas_position' => 'offcanvas-start']);
    $check(strpos($panel, 'flip: true') !== false && strpos($left, 'flip: false') !== false, 'UIkit panels reuse the existing right and left position settings');
    $check(strpos($panel, 'id="wt_jshopping_cart_title_92"') !== false && strpos($panel, 'aria-labelledby="wt_jshopping_cart_title_92"') !== false && strpos($left, 'id="wt_jshopping_cart_title_94"') !== false, 'Each UIkit panel owns a unique accessible title');
    $check(strpos($panel, 'id="wt-jshop-cart-product-template-92"') !== false && strpos($panel, 'uk-grid') !== false && strpos($panel, 'uk-close') !== false, 'UIkit panel renders a native product row, AJAX prototype, and close control');
    $check(strpos($left, 'data-wt-jshop-module-cart-products hidden') !== false && strpos($left, 'uk-alert data-wt-jshop-module-cart-empty>') !== false, 'Empty UIkit panel shows the empty message and hides products');
    $check(strpos($panel . $button, 'data-bs-') === false && strpos($panel . $button, 'HTMLHelper') === false, 'UIkit templates have no Bootstrap controls or asset calls');

    $check($installer->removeFiles($manifest->files, -1) && !is_file($installed), 'Joomla removes the rows with the other module files');
    $check(file_get_contents($oldRow) === 'old shared row' && is_file($alternate) && is_file($override), 'Uninstall preserves shared rows and template overrides');
    $report = ['joomla' => JVERSION, 'php' => PHP_VERSION, 'passed' => count($results), 'failed' => 0, 'results' => $results];
    file_put_contents($runtime . '/verification.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
