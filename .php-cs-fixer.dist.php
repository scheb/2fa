<?php

$rules = [
    '@Symfony' => true,
    'global_namespace_import' => ['import_constants' => true, 'import_functions' => true, 'import_classes' => true],
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
    'phpdoc_to_comment' => false,
    'phpdoc_align' => false,
    'php_unit_method_casing' => false,
];

$finder = PhpCsFixer\Finder::create()
    ->in([
    __DIR__.'/src',
    __DIR__.'/tests',
    __DIR__.'/app/bin',
    __DIR__.'/app/config',
    __DIR__.'/app/public',
    __DIR__.'/app/src',
    __DIR__.'/app/tests',
]);

$config = new PhpCsFixer\Config();
return $config
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setUsingCache(true)
;
