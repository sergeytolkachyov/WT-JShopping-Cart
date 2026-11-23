<?php

require rtrim((string) getenv('PHP_QA_ROOT'), '/\\') . '/vendor/autoload.php';

chdir(dirname(__DIR__, 2) . '/mod_wtjshoppingcart');

$factory = new PhpParser\ParserFactory();
$parser = method_exists($factory, 'createForNewestSupportedVersion')
    ? $factory->createForNewestSupportedVersion()
    : $factory->create(PhpParser\ParserFactory::PREFER_PHP7);
$version = (string) simplexml_load_file('mod_wtjshoppingcart.xml')->version;
$packageVersion = (string) simplexml_load_file('../pkg_wtjshoppingcart.xml')->version;
$pluginPath = '../plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart';
$pluginVersion = (string) simplexml_load_file($pluginPath . '/wtjshoppingproductsajaxaddtocart.xml')->version;
$files = [
    '../pkg_script.php' => $packageVersion,
    'services/provider.php' => $version,
    $pluginPath . '/src/Fields/PlugininfoField.php' => $pluginVersion,
];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src', FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[str_replace('\\', '/', $file->getPathname())] = $version;
    }
}

$rows = [];
foreach ($files as $file => $extensionVersion) {
    $visitor = new class ($file, $extensionVersion) extends PhpParser\NodeVisitorAbstract {
        public array $rows = [];
        private array $classes = [];

        public function __construct(private string $file, private string $version)
        {
        }

        public function enterNode(PhpParser\Node $node)
        {
            if ($node instanceof PhpParser\Node\Stmt\Class_) {
                $name = $node->name ? (string) $node->name : 'anonymous@' . $node->getStartLine();
                $this->classes[] = $name;
                if ($node->name) {
                    $this->record('class', $name, $node);
                }
            } elseif ($node instanceof PhpParser\Node\Stmt\ClassMethod) {
                $this->record('method', end($this->classes) . '::' . $node->name, $node);
            } elseif ($node instanceof PhpParser\Node\Stmt\Property) {
                foreach ($node->props as $property) {
                    $this->record('property', end($this->classes) . '::$' . $property->name, $node);
                }
            }
        }

        public function leaveNode(PhpParser\Node $node)
        {
            if ($node instanceof PhpParser\Node\Stmt\Class_) {
                array_pop($this->classes);
            }
        }

        private function record(string $kind, string $name, PhpParser\Node $node): void
        {
            $comment = $node->getDocComment();
            $text = $comment ? $comment->getText() : '';
            preg_match('/@since\s+([^\s*]+)/', $text, $match);
            $since = $match[1] ?? null;
            $this->rows[] = [
                'file' => $this->file,
                'kind' => $kind,
                'name' => $name,
                'declaration_line' => $node->getStartLine(),
                'doc_line' => $comment ? $comment->getStartLine() : null,
                'has_docblock' => (bool) $comment,
                'since' => $since,
                'since_after_manifest_version' => $since !== null
                    && preg_match('/^\d+(?:\.\d+)+$/', $since)
                    && version_compare($since, $this->version, '>'),
            ];
        }
    };
    $traverser = new PhpParser\NodeTraverser();
    $traverser->addVisitor($visitor);
    $traverser->traverse($parser->parse(file_get_contents($file)));
    $rows = array_merge($rows, $visitor->rows);
}

echo json_encode([
    'module_version' => $version,
    'declarations' => count($rows),
    'missing_since' => array_values(array_filter($rows, static fn ($row) => $row['since'] === null)),
    'since_after_manifest_version' => array_values(array_filter($rows, static fn ($row) => $row['since_after_manifest_version'])),
    'all_declarations' => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$invalid = array_filter($rows, static fn ($row) => $row['since'] === null || $row['since_after_manifest_version']);
foreach (glob('tmpl/*.php') as $layout) {
    preg_match('/\/\*\*.*?\*\//s', file_get_contents($layout), $header);
    if (strpos($header[0] ?? '', '@since') === false) { $invalid[] = $layout; }
}
exit($invalid ? 1 : 0);
