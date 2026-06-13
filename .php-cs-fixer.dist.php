<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/server/srv/alina')
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
            'operators' => ['=>' => 'align_single_space_minimal'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['if', 'return', 'break', 'continue', 'throw', 'try'],
        ],
        'blank_line_after_opening_tag' => true,
        'single_blank_line_at_eof' => true,
        'no_extra_blank_lines' => true,
        'static_lambda' => true,
        'control_structure_continuation_position' => [
            'position' => 'next_line',
        ],
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'next',
            'position_after_control_structures' => 'same',
            'position_after_anonymous_constructs' => 'same',
        ],
        'visibility_required' => ['elements' => ['property', 'method', 'const']],
    ])
    ->setFinder($finder)
    ->setUsingCache(true);