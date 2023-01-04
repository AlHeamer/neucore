<?php

declare(strict_types=1);

namespace Neucore\Controller;

use Neucore\Log\Context;
use Neucore\Plugin\Exception;
use Neucore\Service\PluginService;
use Neucore\Service\UserAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class PluginController extends BaseController
{
    /**
     * GET /plugin/{id}/{name}
     *
     * This URL is public.
     */
    public function request(
        string                 $id,
        string                 $name,
        ServerRequestInterface $request,
        PluginService          $pluginService,
        UserAuth               $userAuth,
        LoggerInterface        $logger
    ): ResponseInterface {
        $user = $userAuth->getUser();
        if (!$user) {
            $this->response->getBody()->write($this->getBodyWithHomeLink('Not logged in.'));
            return $this->response->withStatus(403);
        }
        $player = $user->getPlayer();

        $plugin = $this->repositoryFactory->getPluginRepository()->find((int) $id);
        if ($plugin === null) {
            $this->response->getBody()->write($this->getBodyWithHomeLink('Plugin not found.'));
            return $this->response->withStatus(404);
        }

        if (!$userAuth->hasRequiredGroups($plugin)) {
            $this->response->getBody()->write($this->getBodyWithHomeLink('Not allowed to use this plugin.'));
            return $this->response->withStatus(403);
        }

        $implementation = $pluginService->getPluginImplementation($plugin);
        if (!$implementation) {
            $this->response->getBody()->write($this->getBodyWithHomeLink('Plugin implementation not found.'));
            return $this->response->withStatus(404);
        }

        if ($player->getMain() !== null) {
            $coreCharacter = $player->getMain()->toCoreCharacter();
        } else {
            $this->response->getBody()->write(
                $this->getBodyWithHomeLink('Player or main character account not found.')
            );
            return $this->response->withStatus(404);
        }

        try {
            return $implementation->request(
                $name,
                $request,
                $this->response,
                $coreCharacter,
                $player->getCoreCharacters(),
                $player->getCoreGroups(),
                $player->getManagerCoreGroups(),
                $player->getCoreRoles(),
            );
        } catch (Exception $e) {
            $logger->error($e->getMessage(), [Context::EXCEPTION => $e]);
            return $this->response
                ->withHeader('Location', '/#Service/' . $plugin->getId() . '/?message=Unknown%20error.')
                ->withStatus(302);
        }
    }
}
