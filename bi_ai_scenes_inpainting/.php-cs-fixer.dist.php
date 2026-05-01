<?php
/**
 * PHP-CS-Fixer configuration for bi_ai_scenes_inpainting.
 * PSR-12 + a small set of opinionated rules aligned with PrestaShop's
 * codebase conventions.
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/classes',
        __DIR__ . '/controllers',
        __DIR__ . '/traits',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->exclude('vendor');

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,
        'concat_space' => ['spacing' => 'one'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'cast_spaces' => ['space' => 'single'],
        'declare_strict_types' => false,
    ])
    ->setFinder($finder);
