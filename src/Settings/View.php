<?php

namespace Worksome\Foggy\Settings;

use stdClass;

/**
 * The container for all of the settings for a view.
 * None at the moment
 */
class View
{
    protected stdClass $settings;

    public function __construct(stdClass $settings)
    {
        $this->settings = $settings;
    }
}
