<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/server/var/www') // ваш код в /var/www
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => true,
        'single_blank_line_at_eof' => true,
        'no_extra_blank_lines' => true,
    ])
    ->setFinder($finder)
    ->setUsingCache(true);
