<?php

namespace Worksome\Foggy\Rules;

use Worksome\Foggy\Settings\Rule;

class ConditionValidator
{
    /**
     * @param Rule  $rule
     * @param array $row
     *
     * @return bool
     */
    public static function passes(Rule $rule, array $row): bool
    {
        if ($rule->getTimes() !== null && $rule->getTimes() <= $rule->getTimesRan()) {
            return false;
        }

        $passesCondition = eval("return {$rule->getCondition()};");

        if (! $passesCondition) {
            return false;
        }

        $rule->incrementTimesRan();

        return true;
    }
}
