<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

$demoVersions = [
    'symfony5.4',
    'symfony5.4-types',
    'symfony6.x',
    'symfony6.x-orm3',
    'symfony6.x-types',
    'symfony7.x',
    'symfony7.x-types',
];

foreach ($demoVersions as $demoVersion) {
    $finder = $finder
        ->in(__DIR__.'/demo/'.$demoVersion.'/bin')
        ->in(__DIR__.'/demo/'.$demoVersion.'/src')
        ->in(__DIR__.'/demo/'.$demoVersion.'/tests')
    ;
}

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'standardize_increment' => false,
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
        ],
        'trailing_comma_in_multiline' => false, // This breaks code for php 7.x
    ])
    ->setFinder($finder)
;
