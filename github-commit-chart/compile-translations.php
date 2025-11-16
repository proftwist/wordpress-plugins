<?php
/**
 * Translation compiler for GitHub Commit Chart plugin
 *
 * Run this script to compile all translation files
 * Usage: php compile-translations.php
 */

// Only allow execution from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

$plugin_path = __DIR__;

// Compile Russian translation
echo "Compiling Russian translation...\n";
$ru_po = $plugin_path . '/languages/github-commit-chart-ru_RU.po';
$ru_mo = $plugin_path . '/languages/github-commit-chart-ru_RU.mo';

if (file_exists($ru_po)) {
    $command = "msgfmt " . escapeshellarg($ru_po) . " -o " . escapeshellarg($ru_mo);
    system($command, $result);
    if ($result === 0) {
        echo "✓ Russian translation compiled successfully\n";
    } else {
        echo "✗ Failed to compile Russian translation\n";
    }
} else {
    echo "✗ Russian PO file not found: $ru_po\n";
}

// Compile English translation
echo "Compiling English translation...\n";
$en_po = $plugin_path . '/languages/github-commit-chart-en_US.po';
$en_mo = $plugin_path . '/languages/github-commit-chart-en_US.mo';

if (file_exists($en_po)) {
    $command = "msgfmt " . escapeshellarg($en_po) . " -o " . escapeshellarg($en_mo);
    system($command, $result);
    if ($result === 0) {
        echo "✓ English translation compiled successfully\n";
    } else {
        echo "✗ Failed to compile English translation\n";
    }
} else {
    echo "✗ English PO file not found: $en_po\n";
}

echo "Translation compilation complete!\n";