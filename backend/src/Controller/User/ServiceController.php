<?php

/** @noinspection PhpUnusedAliasInspection */

declare(strict_types=1);

namespace Neucore\Controller\User;

use Neucore\Controller\BaseController;
use Neucore\Entity\Character;
use Neucore\Entity\Plugin;
use Neucore\Factory\RepositoryFactory;
use Neucore\Plugin\Exception;
use Neucore\Plugin\Data\ServiceAccountData;
use Neucore\Plugin\ServiceInterface;
use Neucore\Service\AccountGroup;
use Neucore\Service\ObjectManager;
use Neucore\Service\PluginService;
use Neucore\Service\UserAuth;
/* @phan-suppress-next-line PhanUnreferencedUseNormal */
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * @OA\Tag(
 *     name="Service",
 *     description="Service management."
 * )
 *
 * The schema for Neucore\Plugin\ServiceAccountData:
 * @OA\Schema(
 *     schema="ServiceAccountData",
 *     required={"characterId", "username", "password", "email", "status", "name"},
 *     @OA\Property(property="characterId", type="integer", format="int64"),
 *     @OA\Property(property="username", type="string", nullable=true),
 *     @OA\Property(property="password", type="string", nullable=true),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", nullable=true,
 *                  enum={"Pending", "Active", "Deactivated", "Unknown"}),
 *     @OA\Property(property="name", type="string", nullable=true),
 * )
 */
class ServiceController extends BaseController
{
    private LoggerInterface $log;

    private PluginService $pluginService;

    private UserAuth $userAuth;

    private AccountGroup $accountGroup;

    private int $responseErrorCode = 200;

    public function __construct(
        ResponseInterface $response,
        ObjectManager     $objectManager,
        RepositoryFactory $repositoryFactory,
        LoggerInterface   $log,
        PluginService     $pluginService,
        UserAuth          $userAuth,
        AccountGroup      $accountGroup
    ) {
        parent::__construct($response, $objectManager, $repositoryFactory);
        $this->log = $log;
        $this->pluginService = $pluginService;
        $this->userAuth = $userAuth;
        $this->accountGroup = $accountGroup;
    }

    /**
     * @OA\Get(
     *     path="/user/service/{id}/get",
     *     operationId="serviceGet",
     *     summary="Returns service.",
     *     description="Needs role: user",
     *     tags={"Service"},
     *     security={{"Session"={}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the service.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="The service.",
     *         @OA\JsonContent(ref="#/components/schemas/Plugin")
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Service not found."
     *     )
     * )
     */
    public function get(string $id): ResponseInterface
    {
        $plugin = $this->getPlugin((int) $id);
        if (!$plugin) {
            return $this->response->withStatus($this->responseErrorCode);
        }

        return $this->withJson($plugin->jsonSerialize(false, false, false));
    }

    /**
     * @OA\Get(
     *     path="/user/service/{id}/accounts",
     *     operationId="serviceAccounts",
     *     summary="Returns all player's service accounts for a service.",
     *     description="Needs role: user",
     *     tags={"Service"},
     *     security={{"Session"={}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Service accounts.",
     *         description="The player property contains only the id and name.",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ServiceAccountData"))
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Service not found."
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="In the event of an error when retrieving accounts."
     *     )
     * )
     */
    public function accounts(string $id, UserAuth $userAuth): ResponseInterface
    {
        $serviceImplementation = $this->getPluginAndServiceImplementation((int) $id);
        if (!$serviceImplementation) {
            return $this->response->withStatus($this->responseErrorCode);
        }

        try {
            $accountData = $this->pluginService->getAccounts(
                $serviceImplementation,
                $this->getUser($userAuth)->getPlayer()->getCharacters()
            );
        } catch (Exception) {
            return $this->response->withStatus(500);
        }

        return $this->withJson($accountData);
    }

    /**
     * @OA\Post(
     *     path="/user/service/{id}/register",
     *     operationId="serviceRegister",
     *     summary="Registers a new account with a service.",
     *     description="Needs role: group-user",
     *     tags={"Service"},
     *     security={{"Session"={}, "CSRF"={}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="email",
     *                     description="E-mail address.",
     *                     type="string",
     *                     maxLength=255
     *                 )
     *             ),
     *         ),
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Registered successfully.",
     *         @OA\JsonContent(ref="#/components/schemas/ServiceAccountData")
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Service not found."
     *     ),
     *     @OA\Response(
     *         response="409",
     *         description="Different errors, see body text.",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Registration failed."
     *     )
     * )
     */
    public function register(string $id, ServerRequestInterface $request, UserAuth $userAuth): ResponseInterface
    {
        $emailAddress = $this->sanitizePrintable($this->getBodyParam($request, 'email', ''));

        // get main character
        $player = $this->getUser($userAuth)->getPlayer();
        $main = $player->getMain();
        if (!$main) {
            return $this->withJson('no_main', 409);
        }

        $plugin = $this->getPlugin((int) $id);
        if (!$plugin) {
            return $this->response->withStatus($this->responseErrorCode);
        }
        $serviceImplementation = $this->getServiceImplementation($plugin);
        if (!$serviceImplementation) {
            return $this->response->withStatus($this->responseErrorCode);
        }

        // check if a new account may be created
        $oneAccountOnly = $plugin->getConfigurationFile()?->oneAccount;
        if ($oneAccountOnly) {
            $characters = $player->getCharacters();
        } else {
            $characters = [$main];
        }
        try {
            $accounts = $this->pluginService->getAccounts($serviceImplementation, $characters, false);
        } catch (Exception) {
            return $this->response->withStatus(500);
        }
        if (
            !empty($accounts) &&
            (
                $oneAccountOnly ||
                !in_array( // check status of main character
                    $accounts[0]->getStatus(),
                    [ServiceAccountData::STATUS_DEACTIVATED, ServiceAccountData::STATUS_UNKNOWN]
                )
            )
        ) {
            return $this->withJson($oneAccountOnly ? 'second_account' : 'already_registered', 409);
        }

        try {
            $accountData = $serviceImplementation->register(
                $main->toCoreCharacter(),
                $this->accountGroup->getCoreGroups($player),
                $emailAddress,
                $player->getCharactersId()
            );
        } catch (Exception $e) {
            if ($e->getMessage() !== '') {
                return $this->withJson($e->getMessage(), 409);
            } else {
                return $this->response->withStatus(500);
            }
        }
        return $this->withJson($accountData);
    }

    /**
     * @OA\Put(
     *     path="/user/service/{id}/update-account/{characterId}",
     *     operationId="serviceUpdateAccount",
     *     summary="Update an account.",
     *     description="Needs role: user",
     *     tags={"Service"},
     *     security={{"Session"={}, "CSRF"={}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="characterId",
     *         in="path",
     *         required=true,
     *         description="A character ID from the player's account.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Account updated."
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Service, character or character's service account not found."
     *     ),
     *     @OA\Response(
     *         response="409",
     *         description="Different errors, see body text.",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Error during update."
     *     )
     * )
     */
    public function updateAccount(string $id, string $characterId, UserAuth $userAuth): ResponseInterface
    {
        $serviceImplementation = $this->getPluginAndServiceImplementation((int)$id);
        $validCharacter = $this->validateCharacter((int)$characterId, $userAuth);
        if (!$serviceImplementation || !$validCharacter) {
            return $this->response->withStatus($this->responseErrorCode);
        }

        // Check that there is an account for the character.
        $account = $this->getAccountOfCharacter($serviceImplementation, $validCharacter);
        if (!$account) {
            return $this->response->withStatus($this->responseErrorCode);
        }

        // Update account.
        $error = $this->pluginService->updateServiceAccount($validCharacter, $serviceImplementation);
        if (!empty($error)) {
            return $this->withJson($error, 409);
        } elseif ($error === '') {
            return $this->response->withStatus(500);
        }

        return $this->response->withStatus(204);
    }

    /**
     * @OA\Put(
     *     path="/user/service/update-all-accounts/{playerId}",
     *     operationId="serviceUpdateAllAccounts",
     *     summary="Update all service accounts of one player.",
     *     description="Needs role: user-admin, user-manager, group-admin, app-admin, user-chars, tracking or
                        watchlist",
     *     tags={"Service"},
     *     security={{"Session"={}, "CSRF"={}}},
     *     @OA\Parameter(
     *         name="playerId",
     *         in="path",
     *         required=true,
     *         description="The player ID.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Account(s) updated.",
     *         @OA\JsonContent(type="integer")
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Player not found."
     *     )
     * )
     */
    public function updateAllAccounts(string $playerId): ResponseInterface
    {
        // Note that user with the role tracking or watchlist should only update accounts from
        // the respective lists, but there's no harm allowing them to update all as long as
        // this does not return any data, otherwise this would need to check permissions like it's done
        // for /user/player/{id}/characters.

        $player = $this->repositoryFactory->getPlayerRepository()->find((int) $playerId);
        if (!$player) {
            return $this->response->withStatus(404);
        }

        $updated = $this->pluginService->updatePlayerAccounts($player);

        // Do not return account data because roles tracking and watchlist may execute this.
        return $this->withJson(count($updated));
    }

    /**
     * @OA\Put(
     *     path="/user/service/{id}/reset-password/{characterId}",
     *     operationId="serviceResetPassword",
     *     summary="Resets password for one account.",
     *     description="Needs role: user",
     *     tags={"Service"},
     *     security={{"Session"={}, "CSRF"={}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="characterId",
     *         in="path",
     *         required=true,
     *         description="A character ID from the player's account.",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Password changed, returns the new password.",
     *         @OA\JsonContent(type="string")
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="Service, character or character's service account not found."
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Password change failed."
     *     )
     * )
     */
    public function resetPassword(string $id, string $characterId, UserAuth $userAuth): ResponseInterface
    {
        $serviceImplementation = $this->getPluginAndServiceImplementation((int)$id);
        if (!$serviceImplementation) {
            return $this->response->withStatus($this->responseErrorCode);
        }
        $account = $this->validateCharacterAndGetAccount($serviceImplementation, (int)$characterId, $userAuth);
        if (!$account) {
            return $this->response->withStatus($this->responseErrorCode);
        }

        // change password
        try {
            $newPassword = $serviceImplementation->resetPassword($account->getCharacterId());
        } catch (Exception) {
            return $this->response->withStatus(500);
        }

        return $this->withJson($newPassword);
    }

    private function getPlugin(int $id): ?Plugin
    {
        // get service with data from plugin.yml
        $plugin = $this->pluginService->getPlugin($id);
        if ($plugin === null) {
            $this->responseErrorCode = 404;
            return null;
        }

        // check service permission
        if (!$this->userAuth->hasRequiredGroups($plugin)) {
            $this->responseErrorCode = 403;
            return null;
        }

        // check active
        if (!$plugin->getConfigurationDatabase()?->active) {
            $this->responseErrorCode = 404;
            return null;
        }

        return $plugin;
    }

    private function getServiceImplementation(Plugin $service): ?ServiceInterface
    {
        // get service object
        $serviceImplementation = $this->pluginService->getPluginImplementation($service);
        if (!$serviceImplementation instanceof ServiceInterface) {
            $this->log->error(
                "ServiceController: The configured service class does not exist or does not implement " .
                "Neucore\Plugin\ServiceInterface."
            );
            $this->responseErrorCode = 500;
            return null;
        }

        return $serviceImplementation;
    }

    private function getPluginAndServiceImplementation(int $id): ?ServiceInterface
    {
        $plugin = $this->getPlugin($id);
        return $plugin ? $this->getServiceImplementation($plugin) : null;
    }

    private function validateCharacter(int $characterId, UserAuth $userAuth): ?Character
    {
        $character = null;
        foreach ($this->getUser($userAuth)->getPlayer()->getCharacters() as $char) {
            if ($char->getId() === $characterId) {
                $character = $char;
                break;
            }
        }
        if ($character === null) {
            $this->responseErrorCode = 404;
            return null;
        }
        return $character;
    }

    private function getAccountOfCharacter(
        ServiceInterface $serviceImplementation,
        Character $validCharacter
    ): ?ServiceAccountData {
        try {
            $account = $this->pluginService->getAccounts(
                $serviceImplementation,
                [$validCharacter],
                false
            );
        } catch (Exception) {
            $this->responseErrorCode = 500;
            return null;
        }
        if (empty($account)) {
            $this->responseErrorCode = 404;
            return null;
        }
        return $account[0];
    }

    private function validateCharacterAndGetAccount(
        ServiceInterface $serviceImplementation,
        int $characterId,
        UserAuth $userAuth
    ): ?ServiceAccountData {
        $validCharacter = $this->validateCharacter($characterId, $userAuth);
        if (!$validCharacter) {
            return null;
        }
        return $this->getAccountOfCharacter($serviceImplementation, $validCharacter);
    }
}
