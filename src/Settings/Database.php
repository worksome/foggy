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
    /** @var stdClass */
    protected $settings;

    /**
     * Database constructor.
     *
     * @param stdClass $settings
     */
    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns the table setting class which matches string parsed.
     *
     * @param string $table
     * @return Table
     * @throws InvalidArgumentException
     */
    public function getTable(string $table): Table
    {
        $configTables = $this->settings;

        foreach ($configTables as $name => $config) {
            $pattern = str_replace(['*', '?'], ['(.*)', '.'], $name);
            if (preg_match("/^$pattern$/i", $table)) {
                return new Table($config);
            }
        }

        throw new InvalidArgumentException("The table [$table] does not exist in config.");
    }
}
