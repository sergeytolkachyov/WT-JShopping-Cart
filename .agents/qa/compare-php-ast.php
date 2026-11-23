<?php

require rtrim((string) getenv('PHP_QA_ROOT'), '/\\') . '/vendor/autoload.php';

$baseline = realpath($argv[1] ?? '');
$parser = (new PhpParser\ParserFactory())->createForNewestSupportedVersion();

function runtimeTree($value) {
    if ($value instanceof PhpParser\Node) {
        if ($value instanceof PhpParser\Node\Stmt\InlineHTML) {
            return ['nodeType' => $value->getType(), 'value' => preg_replace('/\s+/u', ' ', trim($value->value))];
        }
        $result = ['nodeType' => $value->getType()];
        foreach ($value->getSubNodeNames() as $name) {
            $result[$name] = runtimeTree($value->$name);
        }
        return $result;
    }
    if (is_array($value)) {
        $result = [];
        foreach ($value as $key => $item) {
            if ($item instanceof PhpParser\Node\Stmt\InlineHTML && trim($item->value) === '') continue;
            $result[$key] = runtimeTree($item);
        }
        return array_values($result);
    }
    return $value;
}

if (realpath($_SERVER['SCRIPT_FILENAME']) !== realpath(__FILE__)) return;

if ($baseline === false || !is_dir($baseline)) {
    fwrite(STDERR, "Provide the baseline module directory.\n");
    exit(1);
}

chdir(dirname(__DIR__, 2) . '/mod_wtjshoppingcart');

$files = ['services/provider.php'];
foreach (['src', 'tmpl'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() === 'php') $files[] = str_replace('\\', '/', $file->getPathname());
    }
}
$results = [];
foreach ($files as $name) {
    $before = runtimeTree($parser->parse(file_get_contents($baseline . '/' . $name)));
    $after = runtimeTree($parser->parse(file_get_contents($name)));
    $results[] = ['file' => $name, 'runtime_ast_equal' => $before === $after];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
exit(array_filter($results, static fn ($result) => !$result['runtime_ast_equal']) ? 1 : 0);
