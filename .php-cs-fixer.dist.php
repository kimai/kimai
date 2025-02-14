<?php

$fileHeaderComment = <<<COMMENT
This file is part of the Kimai time-tracking app.

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
COMMENT;

$fixer = new PhpCsFixer\Config();
$fixer
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        'encoding' => true,
        'full_opening_tag' => true,
        'blank_line_after_namespace' => true,
        'control_structure_braces' => true,
        'control_structure_continuation_position' => ['position' => 'same_line'],
        'declare_parentheses' => true,
        'no_multiple_statements_per_line' => true,
        'statement_indentation' => true,
        'class_definition' => true,
        'elseif' => true,
        'function_declaration' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'constant_case' => ['case' => 'lower'],
        'lowercase_keywords' => true,
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline', 'after_heredoc' => true],
        'header_comment' => ['header' => $fileHeaderComment, 'separate' => 'both'],
        'no_php4_constructor' => true,
        'ordered_imports' => true,
        'no_break_comment' => true,
        'no_closing_tag' => true,
        'no_spaces_after_function_name' => true,
        'spaces_inside_parentheses' => ['space' => 'none'],
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'single_blank_line_at_eof' => true,
        'single_class_element_per_statement' => ['elements' => ['property']],
        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,
        'php_unit_method_casing' => true,
        'array_syntax' => [
            'syntax' => 'short'
        ],
        'binary_operator_spaces' => true,
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        'cast_spaces' => true,
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'concat_space' => ['spacing' => 'one'],
        'declare_equal_normalize' => true,
        'type_declaration_spaces' => ['elements' => ['function', 'property']],
        'include' => true,
        'lowercase_cast' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'native_function_casing' => true,
        'new_with_parentheses' => true,
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => ['tokens' => [
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ]],
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_mixed_echo_print' => ['use' => 'echo'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_spaces_around_offset' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_braces' => true,
        'no_unneeded_final_method' => true,
        'no_unused_imports' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'normalize_index_brace' => true,
        'object_operator_without_whitespace' => true,
        'php_unit_fqcn_annotation' => true,
        'phpdoc_align' => [
            'align' => 'left',
            'tags' => [
                'method',
                'param',
                'property',
                'return',
                'throws',
                'type',
                'var',
            ],
        ],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_alias_tag' => true,
        'phpdoc_no_empty_return' => false,
        'phpdoc_no_package' => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_return_self_reference' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => false,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => false,
        'phpdoc_to_comment' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_var_without_name' => true,
        'protected_to_private' => true,
        'return_type_declaration' => true,
        'semicolon_after_instruction' => true,
        'short_scalar_cast' => true,
        'blank_lines_before_namespace' => ['min_line_breaks' => 2, 'max_line_breaks' => 2],
        'single_line_comment_style' => [
            'comment_types' => ['hash'],
        ],
        'single_quote' => true,
        'space_after_semicolon' => [
            'remove_in_empty_for_expressions' => true,
        ],
        'standardize_increment' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline' => false,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
        'yoda_style' => false,
        'ternary_to_null_coalescing' => true,
        'visibility_required' => ['elements' => [
            'const',
            'method',
            'property',
        ]],
        'native_function_invocation' => [
            'include' => [
                '@compiler_optimized'
            ],
            'scope' => 'namespaced'
        ],
        'native_type_declaration_casing' => true,
        'no_alias_functions' => [
            'sets' => [
                '@internal'
            ]
        ],
        'list_syntax' => ['syntax' => 'short'],
        'assign_null_coalescing_to_coalesce_equal' => true,
        'non_printable_character' => true,
        'octal_notation' => true,
        'heredoc_indentation' => ['indentation' => 'start_plus_one'],
        'no_space_around_double_colon' => true,
        'no_useless_nullsafe_operator' => true,
        'clean_namespace' => true,
        'no_unset_cast' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/migrations/',
                __DIR__ . '/src/',
                __DIR__ . '/tests/',
            ])
    )
    ->setFormat('checkstyle')
;

return $fixer;