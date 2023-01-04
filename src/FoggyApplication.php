<?php

namespace Worksome\Foggy;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;

class FoggyApplication extends Application
{
    /**
     * Gets the name of the command based on input.
     */
    protected function getCommandName(InputInterface $input): string
    {
        return 'foggy:dump';
    }

    /**
     * Gets the default commands that should always be available.
     */
    protected function getDefaultCommands(): array
    {
        return [
            new HelpCommand(),
            new FoggyCommand(),
        ];
    }

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
}
