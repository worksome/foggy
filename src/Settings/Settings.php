<?php

namespace Worksome\Foggy\Settings;

use InvalidArgumentException;
use stdClass;

/**
 * This class is the base class for all of the settings.
 *
 * All settings in the config file is listed in this file.
 */
class Settings
{
    protected stdClass $settings;

    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
    }

    public function getDatabase(): Database
    {
        return new Database($this->settings->database);
    }

    public function findTable(string $table): Table|null
    {
        try {
            return $this->getDatabase()
                        ->getTable($table);
        } catch (InvalidArgumentException $exception) {
            return null;
        }
    }

    public function findView(string $view): View|null
    {
        try {
            return $this->getDatabase()
                        ->getView(($view));
        } catch (InvalidArgumentException $exception) {
            return null;
        }
    }
}
