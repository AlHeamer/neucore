<?php

declare(strict_types=1);

namespace Tests\Functional;

use Neucore\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Helper;

/**
 * Runs the application.
 */
class ConsoleTestCase extends TestCase
{
    protected function runConsoleApp(
        string $name,
        array $input = [],
        array $mocks = [],
        array $envVars = [],
        bool $forceDevMode = false
    ): string {
        $app = new Application();
        $app->loadSettings(true, $forceDevMode);

        foreach ($envVars as $envVar) {
            $_ENV[$envVar[0]] = $envVar[1];
        }

        // Add existing db connection
        $mocks = (new Helper)->addEm($mocks);

        try {
            $console = $app->getConsoleApp($mocks);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

        $command = $console->find($name);
        $commandTester = new CommandTester($command);
        $commandTester->execute($input);

        return $commandTester->getDisplay();
    }
}
