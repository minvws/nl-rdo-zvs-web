<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/app') // Specify the directories to scan for PHP files
    ->name('*.php')
    ->notName('*.blade.php')
    ->notName('_bootstrap.php')
    ->notName('_config.php')
    ->notName('ImportPetitionsCommand.php')
    ->exclude('vendor'); // Exclude the vendor directory

$config = new PhpCsFixer\Config();
$config
    ->setRules([
        'no_blank_lines_after_class_opening' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'ordered_attributes' => true,
        'ordered_class_elements' => true,
        'no_closing_tag' => true,
        'binary_operator_spaces' => true,
        'types_spaces' =>  ['space' => 'none', 'space_multiple_catch' => 'single'],
        'braces_position' => [
            'allow_single_line_empty_anonymous_classes' => false,
        ],
    ])
    ->setFinder($finder);

return $config;
