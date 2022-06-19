<?php

declare(strict_types=1);

namespace Neucore\Service;

use Neucore\Entity\Player;
use Neucore\Entity\SystemVariable;
use Neucore\Factory\RepositoryFactory;
use Neucore\Plugin\CoreGroup;

class AccountGroup
{
    private RepositoryFactory $repositoryFactory;

    public function __construct(RepositoryFactory $repositoryFactory)
    {
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * Checks if groups are deactivated for this player.
     */
    public function groupsDeactivated(Player $player, bool $ignoreDelay = false): bool
    {
        // managed account?
        if ($player->getStatus() === Player::STATUS_MANAGED) {
            return false;
        }

        // enabled?
        $requireToken = $this->repositoryFactory->getSystemVariableRepository()->findOneBy(
            ['name' => SystemVariable::GROUPS_REQUIRE_VALID_TOKEN]
        );
        if (! $requireToken || $requireToken->getValue() === '0') {
            return false;
        }

        // get configured alliances and corporations
        $sysVarRepo = $this->repositoryFactory->getSystemVariableRepository();
        $allianceVar = $sysVarRepo->find(SystemVariable::ACCOUNT_DEACTIVATION_ALLIANCES);
        $corporationVar = $sysVarRepo->find(SystemVariable::ACCOUNT_DEACTIVATION_CORPORATIONS);
        if ($allianceVar === null || $corporationVar === null) {
            // Alliance and/or Corporation settings variable not found
            return false;
        }
        $alliances = array_map('intval', explode(',', $allianceVar->getValue()));
        $corporations = array_map('intval', explode(',', $corporationVar->getValue()));

        // check if player account has at least one character in one of the configured alliances or corporations
        if (! $player->hasCharacterInAllianceOrCorporation($alliances, $corporations)) {
            return false;
        }

        // get delay
        if ($ignoreDelay) {
            $hours = 0;
        } else {
            $delay = $this->repositoryFactory->getSystemVariableRepository()->findOneBy(
                ['name' => SystemVariable::ACCOUNT_DEACTIVATION_DELAY]
            );
            $hours = $delay !== null ? (int) $delay->getValue() : 0;
        }

        if ($player->hasCharacterWithInvalidTokenOlderThan($hours)) {
            return true;
        }

        return false;
    }

    /**
     * @param Player $player
     * @return CoreGroup[]
     */
    public function getCoreGroups(Player $player): array
    {
        if ($this->groupsDeactivated($player)) { // do not ignore delay
            return [];
        }
        return $player->getCoreGroups();
    }
}