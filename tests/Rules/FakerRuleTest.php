<?php

namespace Worksome\Foggy\Tests\Rules;

use Doctrine\DBAL\Connection;
use Faker\Generator as FakerGenerator;
use Mockery;
use Worksome\Foggy\Rules\FakerRule;
use Worksome\Foggy\Settings\Rule;

it('can pass arguments', function () {
    $faker = Mockery::mock(FakerGenerator::class);
    $faker->shouldReceive('firstName')->with('male')->once();

    $fakerRule = new FakerRule();
    $fakerRule::setFaker($faker);

    $fakeRule = Mockery::mock(Rule::class);
    $fakeRule->shouldReceive('getValue')->andReturn('firstName')->once();
    $fakeRule->shouldReceive('getParameters')->andReturn([
       ['male'],
    ])->once();

    $fakeConnection = Mockery::mock(Connection::class);
    $fakeConnection->shouldReceive('quote')->andReturn('quote result')->once();

    $fakerRule::handle(
        $fakeRule,
        $fakeConnection,
        [],
        ""
    );
});
