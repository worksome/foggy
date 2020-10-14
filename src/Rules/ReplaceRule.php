<?php

namespace Worksome\Foggy\Rules;

use Doctrine\DBAL\Connection;
use Worksome\Foggy\Settings\Rule as SettingsRule;

class ReplaceRule implements Rule
{
    public static function handle(SettingsRule $rule, Connection $db, array $row, string $value): string
    {
        return $db->quote($rule->getValue());
    }
}
