<?php

declare(strict_types=1);

namespace Neucore\Service;

use Eve\Sso\EveAuthentication;
use Neucore\Entity\Character;
use Neucore\Entity\EsiToken;
use Neucore\Entity\EveLogin;
use Neucore\Entity\RemovedCharacter;
use Neucore\Entity\Role;
use Neucore\Exception\RuntimeException;
use Neucore\Factory\RepositoryFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;

/**
 * Provides methods to authenticate and get a user.
 *
 * A user is identified by it's Eve character ID and is
 * created in the database if it does not already exist already.
 *
 * After that, the session variable "character_id" identifies the user.
 */
class UserAuth implements RoleProviderInterface
{
    const LOGIN_AUTHENTICATED_SUCCESS = 1;

    const LOGIN_AUTHENTICATED_FAIL = 2;

    const LOGIN_CHARACTER_ADDED_SUCCESS = 3;

    const LOGIN_CHARACTER_ADDED_FAIL = 4;

    private SessionData $session;

    private Account $accountService;

    private ObjectManager $objectManager;

    private RepositoryFactory $repositoryFactory;

    private LoggerInterface $log;

    private ?Character $user = null;

    public function __construct(
        SessionData $session,
        Account $charService,
        ObjectManager $objectManager,
        RepositoryFactory $repositoryFactory,
        LoggerInterface $log
    ) {
        $this->session = $session;
        $this->accountService = $charService;
        $this->objectManager = $objectManager;
        $this->repositoryFactory = $repositoryFactory;
        $this->log = $log;
    }

    /**
     * {@inheritdoc}
     * @see \Tkhamez\Slim\RoleAuth\RoleProviderInterface::getRoles()
     */
    public function getRoles(ServerRequestInterface $request = null): array
    {
        $this->getUser();

        $roles = [];
        if ($this->user !== null) {
            foreach ($this->user->getPlayer()->getRoles() as $role) {
                $roles[] = $role->getName();
            }
        }
        if (empty($roles)) {
            $roles[] = Role::ANONYMOUS;
        }

        return $roles;
    }

    /**
     * Loads and returns current logged-in user from the database.
     */
    public function getUser(): ?Character
    {
        if ($this->user === null) {
            $this->loadUser();
        }

        return $this->user;
    }

    public function login(EveAuthentication $eveAuth): int
    {
        $this->getUser();
        if ($this->user === null) {
            $success = $this->authenticate($eveAuth);
            if ($success) {
                return self::LOGIN_AUTHENTICATED_SUCCESS;
            } else {
                return self::LOGIN_AUTHENTICATED_FAIL;
            }
        } else {
            $success = $this->addAlt($eveAuth);
            if ($success) {
                return self::LOGIN_CHARACTER_ADDED_SUCCESS;
            } else {
                return self::LOGIN_CHARACTER_ADDED_FAIL;
            }
        }
    }

    public function findCharacterOnAccount(EveAuthentication $eveAuth): ?Character
    {
        $user = $this->getUser();
        $character = $user ? $user->getPlayer()->getCharacter($eveAuth->getCharacterId()) : null;
        if ($character) {
            return $character;
        }
        return null;
    }

    /**
     * @param EveLogin $eveLogin An instance attached to the entity manager.
     * @return bool False if save failed.
     */
    public function addToken(EveLogin $eveLogin, EveAuthentication $eveAuth, Character $character): bool
    {
        $esiToken = $this->repositoryFactory->getEsiTokenRepository()->findOneBy([
            'character' => $character,
            'eveLogin' => $eveLogin
        ]);
        if (!$esiToken) {
            $esiToken = new EsiToken();
            $esiToken->setEveLogin($eveLogin);
            $esiToken->setCharacter($character);
            $this->objectManager->persist($esiToken);
        }

        $token = $eveAuth->getToken();
        $esiToken->setAccessToken($token->getToken());
        $esiToken->setRefreshToken((string)$token->getRefreshToken());
        $esiToken->setLastChecked(new \DateTime());
        $esiToken->setExpires((int)$token->getExpires());
        $esiToken->setValidToken(true);
        if (!empty($eveLogin->getEveRoles())) {
            $esiToken->setHasRoles(true);
        }

        return $this->objectManager->flush();
    }

    /**
     * User login.
     *
     * Creates character with player account if it is missing.
     *
     * @param EveAuthentication $eveAuth
     * @return bool
     */
    private function authenticate(EveAuthentication $eveAuth): bool
    {
        $characterId = $eveAuth->getCharacterId();
        $char = $this->repositoryFactory->getCharacterRepository()->find($characterId);

        $updateAutoGroups = false;
        if ($char === null || $char->getCharacterOwnerHash() !== $eveAuth->getCharacterOwnerHash()) {
            // first login or changed owner, create account
            $userRole = $this->repositoryFactory->getRoleRepository()->findBy(['name' => Role::USER]);
            if (count($userRole) !== 1) {
                $this->log->critical('UserAuth::authenticate(): Role "'.Role::USER.'" not found.');
                return false;
            }
            $updateAutoGroups = true;
            if ($char === null) {
                $char = $this->accountService->createNewPlayerWithMain($characterId, $eveAuth->getCharacterName());
            } else {
                $oldPlayerId = $char->getPlayer()->getId();
                $char = $this->accountService->moveCharacterToNewAccount($char);
                $this->accountService->updateGroups($oldPlayerId); // flushes the entity manager
            }
            $char->getPlayer()->addRole($userRole[0]);
        }

        $success = $this->accountService->updateAndStoreCharacterWithPlayer($char, $eveAuth, $updateAutoGroups);

        if (!$success) {
            return false;
        }

        $this->accountService->increaseLoginCount($char->getPlayer());
        $this->user = $char;
        $this->session->set('character_id', $this->user->getId());

        return true;
    }

    /**
     * @param EveAuthentication $eveAuth
     * @return bool
     */
    private function addAlt(EveAuthentication $eveAuth): bool
    {
        $characterId = $eveAuth->getCharacterId();
        $player = $this->user->getPlayer();

        // check if the character was already registered,
        // if so, move it to this player account if needed, otherwise create it
        // (there is no need to check for a changed character owner hash here)
        $alt = $this->repositoryFactory->getCharacterRepository()->find($characterId);
        if ($alt !== null && $alt->getPlayer()->getId() !== $player->getId()) {
            $oldPlayerId = $alt->getPlayer()->getId();
            $this->accountService->moveCharacter($alt, $player, RemovedCharacter::REASON_MOVED);
            $this->accountService->updateGroups($oldPlayerId); // flushes the entity manager
            $alt->setMain(false);
        } elseif ($alt === null) {
            $alt = new Character();
            $alt->setId($characterId);
            try {
                $alt->setCreated(new \DateTime());
            } catch (\Exception $e) {
                // ignore
            }
            $player->addCharacter($alt);
            $alt->setPlayer($player);
            $alt->setMain(false);
        }

        return $this->accountService->updateAndStoreCharacterWithPlayer($alt, $eveAuth, true);
    }

    /**
     * @return void
     */
    private function loadUser()
    {
        try {
            $userId = $this->session->get('character_id');
        } catch (RuntimeException $e) {
            // session could not be started, e.g. for 404 errors.
            return;
        }

        if ($userId !== null) {
            $this->user = $this->repositoryFactory->getCharacterRepository()->find($userId);
        }
    }
}
