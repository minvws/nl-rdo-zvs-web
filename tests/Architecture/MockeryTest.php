<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

test('Tests use Mockery instead of PHPUnit mocks', function (): void {
    $methods = [
        'createMock',
        'createStub',
        'createPartialMock',
        'createConfiguredMock',
        'createTestProxy',
        'getMockBuilder',
        'getMockForAbstractClass',
        'getMockForTrait',
        'createMockForIntersectionOfInterfaces',
        'createStubForIntersectionOfInterfaces',
    ];
    $forbidden = sprintf('/->(%s)\(|PHPUnit\\\\Framework\\\\MockObject/', implode('|', $methods));

    $violations = [];
    $files = Finder::create()->files()->in(dirname(__DIR__))->name('*.php')->notName('MockeryTest.php');

    foreach ($files as $file) {
        foreach (file($file->getPathname()) ?: [] as $number => $line) {
            if (preg_match($forbidden, $line) === 1) {
                $violations[] = sprintf('%s:%d', $file->getRelativePathname(), $number + 1);
            }
        }
    }

    expect($violations)->toBe([], 'Use Mockery instead of PHPUnit mocks in: ' . implode(', ', $violations));
});
