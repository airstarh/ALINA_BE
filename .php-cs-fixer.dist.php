<?php

$finder = PhpCsFixer\Finder::create()
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(false)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                            => true,
        'full_opening_tag'                  => false,
        'array_syntax'                      => ['syntax' => 'short'],
        'ordered_imports'                   => ['sort_algorithm' => 'alpha'],
        'fully_qualified_strict_types'      => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline'       => ['elements' => ['arrays']],
        'phpdoc_scalar'                     => true,
        'unary_operator_spaces'             => true,
        'binary_operator_spaces'            => [
            'default'   => 'align_single_space_minimal',
            'operators' => ['=>' => 'align_single_space_minimal'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['if', 'return', 'break', 'continue', 'throw', 'try', 'for', 'foreach', 'while', 'do'],
        ],
        'blank_line_after_opening_tag' => true,
        // 'no_extra_blank_lines' => true,  <-- лучше убрать: будет удалять лишние пустые строки, в т.ч. нужные
        'single_blank_line_at_eof'                => true,
        'static_lambda'                           => true,
        'control_structure_continuation_position' => [
            'position' => 'next_line',
        ],
        'braces' => [
            'position_after_functions_and_oop_constructs' => 'next',
            'position_after_control_structures'           => 'next',
            'position_after_anonymous_constructs'         => 'next',
        ],
        'visibility_required'   => ['elements' => ['property', 'method', 'const']],
        'statement_indentation' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'global_namespace_import' => [
            'import_classes'   => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        // Главное правило для разделения методов пустыми строками:
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                // при желании можно аналогично настроить property/const:
                // 'property' => 'one',
                // 'const' => 'one',
            ],
        ],
    ])
    ->setFinder($finder);
