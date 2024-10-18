<?php

namespace Worksome\Foggy\Rules;

use Doctrine\DBAL\Connection;
use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
use Worksome\Foggy\Settings\Rule as SettingsRule;

class FakerRule implements Rule
{
    protected static FakerGenerator|null $faker = null;

    public static function faker(): FakerGenerator
    {
        if (self::$faker === null) {
            return FakerFactory::create();
        }

        return self::$faker;
    }

    public static function handle(SettingsRule $rule, Connection $db, array $row, string $value): string
    {
        $values = explode('|', $rule->getValue());
        $params = $rule->getParameters();

        $result = self::faker()->{array_shift($values)}(...array_shift($params));

        foreach ($values as $key => $ruleName) {
            $result = $result->{$ruleName}(...$params[$key]);
        }

        return $db->quote($result);
    }

    public static function setFaker(FakerGenerator $faker): void
    {
        self::$faker = $faker;
    }
}
