<?php

namespace Worksome\Foggy\Rules;

use Doctrine\DBAL\Connection;
use Worksome\Foggy\Settings\Rule as SettingsRule;

/**
 * A rule defines what should happen to a column.
 *
 * A rule is tied to a column and is ran on each row. It has access to the whole row.
 */
interface Rule
{
    /**
     * This method is the one that runs the rule.
     *
     * The return value of it, is the new value for the column.
     *
     * @param SettingsRule $rule
     * @param Connection   $db
     * @param array        $row
     * @param string       $value
     *
     * @return string
     */
    public static function handle(SettingsRule $rule, Connection $db, array $row, string $value): string;
}
