<?php

declare(strict_types=1);

namespace Neucore\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     required={"id", "name"}
 * )
 * @ORM\Entity
 * @ORM\Table(name="apps")
 */
class App implements \JsonSerializable
{
    /**
     * App ID
     *
     * @OA\Property()
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    private $id;

    /**
     * App name
     *
     * @OA\Property(maxLength=255)
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $secret;

    /**
     * Roles for authorization.
     *
     * @OA\Property(type="array", @OA\Items(ref="#/components/schemas/Role"))
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="apps")
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    private $roles;

    /**
     * Groups the app can see.
     *
     * @OA\Property(type="array", @OA\Items(ref="#/components/schemas/Group"))
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="apps")
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    private $groups;

    /**
     * @ORM\ManyToMany(targetEntity="Player", inversedBy="managerApps")
     * @ORM\JoinTable(name="app_manager")
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    private $managers;

    /**
     * @OA\Property(type="array", @OA\Items(ref="#/components/schemas/EveLogin"))
     * @ORM\ManyToMany(targetEntity="EveLogin")
     * @ORM\JoinTable(name="app_eve_login")
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
     */
    private $eveLogins;

    /**
     * Contains only information that is of interest to clients.
     *
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'groups' => $this->getGroups(),
            'roles' => $this->getRoles(),
            'eveLogins' => $this->getEveLogins(),
        ];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->managers = new ArrayCollection();
        $this->eveLogins = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $secret The hashed string, *not* the plain text password.
     */
    public function setSecret(string $secret): self
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get secret.
     *
     * @return string
     */
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function addRole(Role $role): self
    {
        $this->roles[] = $role;

        return $this;
    }

    public function removeRole(Role $role): bool
    {
        return $this->roles->removeElement($role);
    }

    /**
     * @return Role[]
     */
    public function getRoles(): array
    {
        return $this->roles->toArray();
    }

    /**
     * @return string[]
     */
    public function getRoleNames(): array
    {
        $names = [];
        foreach ($this->getRoles() as $role) {
            $names[] = $role->getName();
        }

        return $names;
    }

    public function hasRole(string $name): bool
    {
        return in_array($name, $this->getRoleNames());
    }

    public function addGroup(Group $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    public function removeGroup(Group $group): bool
    {
        return $this->groups->removeElement($group);
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        return $this->groups->toArray();
    }

    public function addManager(Player $manager): self
    {
        $this->managers[] = $manager;

        return $this;
    }

    public function removeManager(Player $manager): bool
    {
        return $this->managers->removeElement($manager);
    }

    /**
     * @return Player[]
     */
    public function getManagers(): array
    {
        return $this->managers->toArray();
    }

    public function isManager(Player $player): bool
    {
        $isManager = false;

        foreach ($this->getManagers() as $m) {
            if ($m->getId() === $player->getId()) {
                $isManager = true;
                break;
            }
        }

        return $isManager;
    }

    public function addEveLogin(EveLogin $eveLogins): self
    {
        $this->eveLogins[] = $eveLogins;

        return $this;
    }

    public function removeEveLogin(EveLogin $eveLogins): bool
    {
        return $this->eveLogins->removeElement($eveLogins);
    }

    /**
     * @return EveLogin[]
     */
    public function getEveLogins(): array
    {
        return $this->eveLogins->toArray();
    }
}
