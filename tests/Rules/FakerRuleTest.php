<?php

namespace Worksome\Foggy\Tests\Rules;

use Doctrine\DBAL\Connection;
use Faker\Generator as FakerGenerator;
use Mockery;
use Worksome\Foggy\Rules\FakerRule;
use Worksome\Foggy\Settings\Rule;

it('can pass arguments', function () {
    $faker = Mockery::mock(FakerGenerator::class);
    $faker->shouldReceive('firstName')->with('male')->andReturn('John')->once();

    $fakerRule = new FakerRule();
    $fakerRule::setFaker($faker);

    $fakeRule = Mockery::mock(Rule::class);
    $fakeRule->shouldReceive('getValue')->andReturn('firstName')->once();
    $fakeRule->shouldReceive('getParameters')->andReturn([
       ['male'],
    ])->once();

    $fakeConnection = Mockery::mock(Connection::class);
    $fakeConnection->shouldReceive('quote')->with('John')->andReturn('quote result')->once();

    $fakerRule::handle(
        $fakeRule,
        $fakeConnection,
        [],
        ''
    );
});

it('can cast null to SQL NULL', function () {
    $faker = Mockery::mock(FakerGenerator::class);
    $faker->shouldReceive('firstName')->andReturnNull()->once();

    $fakerRule = new FakerRule();
    $fakerRule::setFaker($faker);

    $fakeRule = Mockery::mock(Rule::class);
    $fakeRule->shouldReceive('getValue')->andReturn('firstName')->once();
    $fakeRule->shouldReceive('getParameters')->andReturn([[]])->once();

    $fakeConnection = Mockery::mock(Connection::class);
    $fakeConnection->shouldNotReceive('quote');

    expect($fakerRule::handle($fakeRule, $fakeConnection, [], ''))->toBe('NULL');
});
