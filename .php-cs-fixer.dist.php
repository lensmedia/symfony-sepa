<?php return (new PhpCsFixer\Config())->setRules([
    // Symfony is our base
    '@Symfony' => true,
    '@Symfony:risky' => true,

    // Migrations these have all migrations from previous versions chained in them, update to latest when they become available
    '@PHP80Migration:risky' => true,
    '@PHP82Migration' => true,

    // Overrides from preset
    'cast_spaces' => ['space' => 'none'],

    // @Symfony - symfony enables this, but we are not ready yet
    'declare_strict_types' => false,

    // @Symfony - prefer imports over inline `\count`, `\Exception`, etc.
    'global_namespace_import' => [
        'import_constants' => true,
        'import_functions' => true,
        'import_classes' => true,
    ],

    // @Symfony - alignment mirrors argument list no need to do weird spacing stuff and track that with every change
    'phpdoc_align' => ['align' => 'left'],

    // @Symfony - this shit gets long for complex messages
    'single_line_throw' => false,

    // @Symfony - added ignore for todos
    'phpdoc_to_comment' => ['ignored_tags' => ['todo', 'see', 'noinspection']],

    // Others
    'array_indentation' => true,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'comment_to_phpdoc' => true,
    'date_time_create_from_format_call' => true,
    'date_time_immutable' => true,
    'declare_parentheses' => true,
    'explicit_string_variable' => true,
    'explicit_indirect_variable' => true,
    'get_class_to_class_keyword' => true,
    'mb_str_functions' => true,
    'multiline_comment_opening_closing' => true,
    'no_superfluous_elseif' => true,
    'no_unset_on_property' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'nullable_type_declaration_for_default_null_value' => true,
    'operator_linebreak' => true,
    'ordered_interfaces' => true,
    'phpdoc_to_param_type' => true,
    'phpdoc_to_property_type' => true,
    'phpdoc_to_return_type' => true,
    'self_static_accessor' => true,
    'simplified_if_return' => true,
    'strict_comparison' => true,
    'strict_param' => true,
])->setRiskyAllowed(true);
