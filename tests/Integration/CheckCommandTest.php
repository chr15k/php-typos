<?php

declare(strict_types=1);

use Chr15k\Typos\Console\Commands\CheckCommand;
use Symfony\Component\Console\Tester\CommandTester;

it('passes with no typos', function (): void {
    $commandTester = new CommandTester(new CheckCommand);

    $commandTester->execute(['paths' => 'tests/Fixtures/UserRegistrationService.php']);

    $output = $commandTester->getDisplay();

    expect($output)->and($commandTester->getStatusCode())->toBe(0);
});

it('fails with typos', function (): void {
    $commandTester = new CommandTester(new CheckCommand);

    $commandTester->execute(['paths' => 'tests/Fixtures/UserRegistrationServiceWithTypos.php']);

    $output = $commandTester->getDisplay();

    expect($output)
        ->toContain('`Registation` should be `Registration`')
        ->toContain('`multipe` should be `multiple`')
        ->toContain('`capabilites` should be `capabilities`')
        ->toContain('`Unkonwn` should be `Unknown`')
        ->toContain('`Processs` should be `Processes`, `Process`')
        ->toContain('`paramaters` should be `parameters`')
        ->toContain('`argumets` should be `arguments`')
        ->toContain('`succesful` should be `successful`')
        ->toContain('`critial` should be `critical`')
        ->toContain('`Welcom` should be `Welcome`')
        ->toContain('`Extact` should be `Extract`, `Exact`')
        ->toContain('`Adress` should be `Address`')
        ->toContain('`adress` should be `address`')
        ->toContain('`occured` should be `occurred`')
        ->toContain('`Mising` should be `Missing`')
        ->toContain('`Alredy` should be `Already`')
        ->toContain('`Mesage` should be `Message`')
        ->toContain('`debuging` should be `debugging`')
        ->toContain('`unexpectd` should be `unexpected`')
        ->toContain('`Retreive` should be `Retrieve`')
        ->toContain('`Interal` should be `Internal`, `Interval`, `Integral`')
        ->and($commandTester->getStatusCode())->toBe(1);
});

it('passes with init option', function (): void {
    $commandTester = new CommandTester(new CheckCommand);

    $commandTester->execute([
        '--init' => true,
    ]);

    $output = $commandTester->getDisplay();

    expect(mb_trim($output))->toContain('Configuration file already exists.')
        ->and($commandTester->getStatusCode())->toBe(1);
});
