<?php

$rules = [
    '@Symfony' => true,
    'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
    'phpdoc_to_comment' => false,
    'phpdoc_align' => false,
    'php_unit_method_casing' => false,
    'blank_line_between_import_groups' => false,
    'phpdoc_separation' => ['groups' => [['test', 'dataProvider']]],
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
