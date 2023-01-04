<?php

namespace Worksome\Foggy\Settings;

use Doctrine\DBAL\Connection;
use stdClass;
use Worksome\Foggy\Rules\ConditionValidator;

/**
 * The container for all of the settings for a table.
 */
class Table
{
    protected stdClass $settings;

    /** @var array<Rule> */
    protected array $rules;

    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
        $this->rules = array_map(function (stdClass $ruleSettings) {
            return new Rule($ruleSettings);
        }, $this->settings->rules ?? []);
    }

    public function withData(): bool
    {
        return $this->settings->withData ?? true;
    }

    public function getWhere(): ?string
    {
        if (isset($this->settings->where)) {
            return " WHERE {$this->settings->where}";
        }

        return null;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function findRules(string $column): array
    {
        return array_filter(
            $this->getRules(),
            function (Rule $rule) use ($column) {
                return $rule->getColumn() === $column;
            }
        );
    }

    /**
     * @param string     $columnName
     * @param Connection $db
     * @param array      $row
     *
     * @return string
     */
    public function getStringForInsertStatement(string $columnName, $value, Connection $db, array $row)
    {
        if ($value === null || $value == '0000-00-00' || $value == '0000-00-00 00:00:00') {
            return 'NULL';
        } elseif ($value === '') {
            return '""';
        } else {
            if (! empty($rules = $this->getRulesWithPassedCondition($columnName, $row))) {
                foreach ($rules as $rule) {
                    $value = $rule->processRow($value, $row, $db);
                }

                return $value;
            }

            return $db->quote($value);
        }
    }

    /**
     * @param string $column
     * @param array  $row
     *
     * @return array<Rule>
     */
    protected function getRulesWithPassedCondition(string $column, array $row): array
    {
        $rules = $this->findRules($column);

        $rules = array_filter($rules, function (Rule $rule) use ($row) {
            return ! $rule->hasCondition() || ConditionValidator::passes($rule, $row);
        });

        return $rules;
    }
}
