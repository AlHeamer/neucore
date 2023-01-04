<?php
/** @noinspection PhpUnused */
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace Tests\Unit\Service\PluginService\plugin\src;

use Neucore\Plugin\CoreCharacter;
use Neucore\Plugin\Exception;
use Neucore\Plugin\GeneralPluginInterface;
use Neucore\Plugin\PluginConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class TestPlugin implements GeneralPluginInterface
{
    public function __construct(LoggerInterface $logger, PluginConfiguration $pluginConfiguration)
    {
    }

    public function onConfigurationChange(): void
    {
    }

    public function request(
        string $name,
        ServerRequestInterface $request,
        ResponseInterface $response,
        CoreCharacter $main,
        array $characters,
        array $memberGroups,
        array $managerGroups,
        array $roles,
    ): ResponseInterface {
        throw new Exception();
    }
}
