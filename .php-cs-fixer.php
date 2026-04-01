<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/share',
    ])
    ->exclude([
        'frontend/nagvis-js/ext',
        'server/core/ext',
    ])
    ->name('*.php')
    ->notPath('etc/conf.d/demo.ini.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'ordered_class_elements' => false,
    ])
    ->setFinder($finder);
