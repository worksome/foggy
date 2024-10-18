<?php

namespace Worksome\Foggy\Tests\Rules;

use Doctrine\DBAL\Connection;
use Mockery;
use Worksome\Foggy\Rules\PhpRule;
use Worksome\Foggy\Settings\Rule;

it('can evaluate php string', function () {
    $value = 'column value';
    $phpString = '"appended " . $value';
    $generatedValue = 'appended column value';

    $fakerRule = new PhpRule();

    $fakeRule = Mockery::mock(Rule::class);
    $fakeRule->shouldReceive('getValue')->andReturn($phpString)->once();

    $fakeConnection = Mockery::mock(Connection::class);
    $fakeConnection->shouldReceive('quote')
        ->with($generatedValue)
        ->andReturn($generatedValue)->once();

    $fakerRule::handle(
        $fakeRule,
        $fakeConnection,
        [],
        $value,
    );
});

it('can cast null to SQL NULL', function () {
    $value = 'column value';
    $phpString = 'null';

    $fakerRule = new PhpRule();

    $fakeRule = Mockery::mock(Rule::class);
    $fakeRule->shouldReceive('getValue')->andReturn($phpString)->once();

    $fakeConnection = Mockery::mock(Connection::class);
    $fakeConnection->shouldNotHaveBeenCalled();

    $returns = $fakerRule::handle(
        $fakeRule,
        $fakeConnection,
        [],
        $value,
    );
    expect($returns)->toBe('NULL');
});
