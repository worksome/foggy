<?php

namespace Worksome\Foggy\Settings;

use InvalidArgumentException;
use stdClass;

/**
 * This class is a container for the database settings.
 *
 * It illustrates all the settings used for the database connection which the settings are being ran on.
 */
class Database
{
    protected stdClass $settings;

    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns the table setting class which matches string parsed.
     *
     * @throws InvalidArgumentException
     */
    public function getTable(string $table): Table
    {
        try {
            return new Table($this->findInSettings($table));
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("The table [$table] does not exist in config.");
        }
    }

    public function getView(string $view): View
    {
        try {
            return new View($this->findInSettings($view));
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("The view [$view] does not exist in config.");
        }
    }

    private function findInSettings(string $search): stdClass
    {
        foreach ($this->settings as $name => $config) {
            $pattern = str_replace([ '*', '?' ], [ '(.*)', '.' ], $name);
            if (preg_match("/^$pattern$/i", $search)) {
                return $config;
            }
        }

        throw new InvalidArgumentException("[$search] does not exist in config.");
    }
}
