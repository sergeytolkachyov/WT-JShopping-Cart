<?php

$projectRoot = dirname(__DIR__, 2);
$moduleRoot = $projectRoot . '/mod_wtjshoppingcart';
$finder = PhpCsFixer\Finder::create()->in([$moduleRoot . '/src', $moduleRoot . '/services'])->append([$projectRoot . '/pkg_script.php']);

return (new PhpCsFixer\Config())
    ->setUsingCache(false)
    ->setRiskyAllowed(false)
    ->setFinder($finder)
    ->setRules(['@PSR12' => true, 'phpdoc_indent' => true]);
