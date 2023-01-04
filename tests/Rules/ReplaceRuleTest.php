<?php

namespace Worksome\Foggy\Tests\Rules;

use Doctrine\DBAL\Connection;
use Mockery;
use Worksome\Foggy\Rules\ReplaceRule;
use Worksome\Foggy\Settings\Rule;

it('can replace value', function () {
    $newValue = 'static value';

    $replaceRule = new ReplaceRule();

    $fakeRule = Mockery::mock(Rule::class);
    $fakeRule->shouldReceive('getValue')->andReturn($newValue)->once();

    $fakeConnection = Mockery::mock(Connection::class);
    $fakeConnection->shouldReceive('quote')
        ->with($newValue)
        ->andReturn($newValue)->once();

    $replaceRule::handle(
        $fakeRule,
        $fakeConnection,
        [],
        ""
    );
});
