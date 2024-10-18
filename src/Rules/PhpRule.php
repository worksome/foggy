<?php

namespace Worksome\Foggy\Rules;

use Doctrine\DBAL\Connection;
use Worksome\Foggy\Settings\Rule as SettingsRule;

/**
 * Adds support for the php rule.
 *
 * This rule will execute any php code and return the result.
 * Be aware that this rule can be really dangerous.
 */
class PhpRule implements Rule
{
    public static function handle(SettingsRule $rule, Connection $db, array $row, string $value): string
    {
        $result = eval("return {$rule->getValue()};");

        if ($result === null) {
            return 'NULL';
        }

        return $db->quote($result);
    }
}
