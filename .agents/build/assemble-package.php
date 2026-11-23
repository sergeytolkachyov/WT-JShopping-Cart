<?php

/**
 * @package    WT JShopping Cart
 * @version    1.2.0
 * @since      1.2.0
 */

declare(strict_types=1);

use PhingPackager\Packager;

/**
 * Find the archive produced in a build-specific output directory.
 *
 * @param   string  $directory  The output directory of one packager call.
 *
 * @return  string
 * @since   1.2.0
 */
function onlyArchive(string $directory): string
{
    $archives = glob($directory . '/*.zip');

    if ($archives === false || count($archives) !== 1) {
        throw new RuntimeException('Expected one archive in ' . $directory);
    }

    return $archives[0];
}

/**
 * Place the files prepared by Phing under their Joomla extension folder.
 *
 * @param   ZipArchive  $package     The open package archive.
 * @param   string      $archive     The prepared extension ZIP.
 * @param   string      $folderName  The extension folder declared in the package manifest.
 * @return  void
 * @since   1.2.0
 */
function appendExtension(ZipArchive $package, string $archive, string $folderName): void
{
    $extension = new ZipArchive();
    if ($extension->open($archive, ZipArchive::RDONLY) !== true) {
        throw new RuntimeException('Cannot read the prepared archive: ' . $folderName);
    }

    try {
        if (!$package->addEmptyDir($folderName)) {
            throw new RuntimeException('Cannot add the extension folder: ' . $folderName);
        }

        for ($index = 0; $index < $extension->numFiles; $index++) {
            $filename = $extension->getNameIndex($index);
            $contents = $extension->getFromIndex($index);
            if ($filename === false || $contents === false || !$package->addFromString($folderName . '/' . $filename, $contents)) {
                throw new RuntimeException('Cannot add the extension file: ' . $folderName . '/' . $filename);
            }
        }
    } finally {
        $extension->close();
    }
}

/**
 * Remove this invocation's verified build workspace.
 *
 * @param   string  $workspace  The staging directory created for this build.
 * @param   string  $output     The resolved project output directory.
 *
 * @return  void
 * @since   1.2.0
 */
function removeBuildWorkspace(string $workspace, string $output): void
{
    $resolved = realpath($workspace);
    if ($resolved === false || dirname($resolved) !== $output || !preg_match('/^\.bundle-[a-f0-9]{16}$/', basename($resolved))) {
        throw new RuntimeException('Invalid package staging directory.');
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir() && !$file->isLink()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($resolved);
}

if (PHP_SAPI !== 'cli' || $argc !== 3 || !in_array($argv[2], ['packageRelease', 'packageDev'], true)) {
    exit(1);
}

require_once rtrim($argv[1], '/\\') . '/src/Packager.php';

$root = dirname(__DIR__, 2);
$output = $root . '/.packages';
if (!is_dir($output) && !mkdir($output) && !is_dir($output)) {
    throw new RuntimeException('Cannot create the project package directory.');
}

$output = realpath($output);
$projectRoot = realpath($root);
if ($output === false || $projectRoot === false || dirname($output) !== $projectRoot) {
    throw new RuntimeException('Package outputs must be inside this project.');
}

$workspace = $output . '/.bundle-' . bin2hex(random_bytes(8));
if (!mkdir($workspace)) {
    throw new RuntimeException('Cannot create a staging directory for the package.');
}

try {
    $packageRoot = $workspace . '/package';
    $manifestFile = 'pkg_wtjshoppingcart.xml';
    $manifest = simplexml_load_file($root . '/' . $manifestFile, SimpleXMLElement::class, LIBXML_NONET);
    if ($manifest === false || !mkdir($packageRoot)) {
        throw new RuntimeException('Cannot prepare the Joomla package directory.');
    }

    $packageFiles = [$manifestFile, (string) $manifest->scriptfile];
    foreach ($manifest->languages->language as $language) {
        $packageFiles[] = (string) $manifest->languages['folder'] . '/' . (string) $language;
    }

    foreach ($packageFiles as $path) {
        $destination = $packageRoot . '/' . $path;
        $directory = dirname($destination);
        if ((!is_dir($directory) && !mkdir($directory, 0777, true)) || !copy($root . '/' . $path, $destination)) {
            throw new RuntimeException('Cannot stage the Joomla package file: ' . $path);
        }
    }

    $bundleOutput = $workspace . '/result';
    Packager::fromConfig(__DIR__ . '/package.config.json', [
        'root' => $packageRoot,
        'output-dir' => $bundleOutput,
        'stage-dir' => $workspace . '/stage',
    ], $root)->run($argv[2]);

    $archive = onlyArchive($bundleOutput);
    $package = new ZipArchive();
    if ($package->open($archive) !== true) {
        throw new RuntimeException('Cannot open the staged Joomla package.');
    }

    $profiles = [
        'mod_wtjshoppingcart' => 'module.package.config.json',
        'plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart' => 'plugin.package.config.json',
    ];
    try {
        foreach ($manifest->files->folder as $folder) {
            $folderName = (string) $folder;
            if (!isset($profiles[$folderName])) {
                throw new RuntimeException('Missing Phing profile for the Joomla extension folder: ' . $folderName);
            }

            $memberOutput = $workspace . '/' . $folderName;
            Packager::fromConfig(__DIR__ . '/' . $profiles[$folderName], [
                'output-dir' => $memberOutput,
                'stage-dir' => $workspace . '/stage',
            ], $root)->run($argv[2]);

            appendExtension($package, onlyArchive($memberOutput), $folderName);
        }
    } finally {
        $closed = $package->close();
    }

    if (!$closed) {
        throw new RuntimeException('Cannot finalize the Joomla package.');
    }

    $result = $output . DIRECTORY_SEPARATOR . basename($archive);
    if (!rename($archive, $result)) {
        throw new RuntimeException('Cannot publish the staged package to ' . $result);
    }

    echo 'Joomla package created: ' . $result . PHP_EOL;
} finally {
    removeBuildWorkspace($workspace, $output);
}
