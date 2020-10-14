<?php

namespace Worksome\Foggy\Settings;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use stdClass;
use Worksome\Foggy\Rules\FakerRule;
use Worksome\Foggy\Rules\PhpRule;
use Worksome\Foggy\Rules\ReplaceRule;

/**
 * A container for all of the settings for a rule.
 */
class Rule
{
    /** @var stdClass */
    protected $settings;

    protected $timesRan = 0;

    public const RULE_MAP = [
        'faker'   => FakerRule::class,
        'php'     => PhpRule::class,
        'replace' => ReplaceRule::class,
    ];

    /**
     * Rule constructor.
     *
     * @param stdClass $settings
     */
    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
    }

    public function hasCondition(): bool
    {
        return $this->getCondition() !== null || $this->getTimes() !== null;
    }

    public function getCondition(): ?string
    {
        return $this->settings->condition ?? null;
    }

    public function getColumn(): string
    {
        return $this->settings->column;
    }

    public function getValue(): ?string
    {
        return $this->settings->value ?? null;
    }

    public function getTimes(): ?int
    {
        return $this->settings->times ?? null;
    }

    public function getTimesRan(): int
    {
        return $this->timesRan;
    }

    public function getParameters(): array
    {
        if (!isset($this->settings->params)) {
            return [[]];
        }

        $parameters = explode('|', $this->settings->params);

        return array_map(function ($parameters) {
            return explode(',', $parameters);
        }, $parameters);
    }

    public function incrementTimesRan(int $increment = 1): self
    {
        $this->timesRan += $increment;

        return $this;
    }

    /**
     * @return string|\Worksome\Foggy\Rules\Rule
     */
    public function getType(): string
    {
        if (!isset(self::RULE_MAP[$this->settings->type])) {
            throw new InvalidArgumentException("Cannot handle rule of type [{$this->settings->type}]");
        }

        return self::RULE_MAP[$this->settings->type];
    }

    public function processRow(string $value, array $row, Connection $db): string
    {
        return $this->getType()::handle($this, $db, $row, $value);
    }

    /**
     * @return stdClass
     */
    public function getSettings(): stdClass
    {
        return $this->settings;
    }
}
