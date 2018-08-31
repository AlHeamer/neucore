<?php declare(strict_types=1);

namespace Brave\Core\Entity;

/**
 * EVE corporation.
 *
 * @SWG\Definition(
 *     definition="Corporation",
 *     required={"id", "name", "ticker"}
 * )
 * @Entity
 * @Table(name="corporations")
 */
class Corporation implements \JsonSerializable
{

    /**
     * EVE corporation ID.
     *
     * @SWG\Property(format="int64")
     * @Id
     * @Column(type="bigint")
     * @NONE
     * @var integer
     */
    private $id;

    /**
     * EVE corporation name.
     *
     * @SWG\Property()
     * @Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $name;

    /**
     * Corporation ticker.
     *
     * @SWG\Property()
     * @Column(type="string", length=16, nullable=true)
     * @var string
     */
    private $ticker;

    /**
     * Last ESI update.
     *
     * @SWG\Property()
     * @Column(type="datetime", name="last_update", nullable=true)
     * @var \DateTime
     */
    private $lastUpdate;

    /**
     *
     * @SWG\Property(ref="#/definitions/Alliance")
     * @ManyToOne(targetEntity="Alliance", inversedBy="corporations")
     * @var Alliance
     */
    private $alliance;

    /**
     * Groups for automatic assignment (API: not included by default).
     *
     * @SWG\Property(type="array", @SWG\Items(ref="#/definitions/Group"))
     * @ManyToMany(targetEntity="Group", inversedBy="corporations")
     * @JoinTable(name="corporation_group")
     * @OrderBy({"name" = "ASC"})
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     *
     * @OneToMany(targetEntity="Character", mappedBy="corporation")
     * @OrderBy({"name" = "ASC"})
     * @var \Doctrine\Common\Collections\Collection
     */
    private $characters;

    /**
     * Contains only information that is of interest for clients.
     *
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->name,
            'ticker' => $this->ticker,
            'alliance' => $this->alliance,
            // API: groups are not included by default
        ];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->characters = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Corporation
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // cast to int because Doctrine creates string for type bigint
        return $this->id !== null ? (int) $this->id : null;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Corporation
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set ticker.
     *
     * @param string $ticker
     *
     * @return Corporation
     */
    public function setTicker(string $ticker)
    {
        $this->ticker = $ticker;

        return $this;
    }

    /**
     * Get ticker.
     *
     * @return string
     */
    public function getTicker()
    {
        return $this->ticker;
    }

    /**
     * Set lastUpdate.
     *
     * @param \DateTime $lastUpdate
     *
     * @return Corporation
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = clone $lastUpdate;

        return $this;
    }

    /**
     * Get lastUpdate.
     *
     * @return \DateTime|null
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Set alliance.
     *
     * @param \Brave\Core\Entity\Alliance|null $alliance
     *
     * @return Corporation
     */
    public function setAlliance(Alliance $alliance = null)
    {
        $this->alliance = $alliance;

        return $this;
    }

    /**
     * Get alliance.
     *
     * @return \Brave\Core\Entity\Alliance|null
     */
    public function getAlliance()
    {
        return $this->alliance;
    }

    /**
     * Add group.
     *
     * @param \Brave\Core\Entity\Group $group
     *
     * @return Corporation
     */
    public function addGroup(Group $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group.
     *
     * @param \Brave\Core\Entity\Group $group
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeGroup(Group $group)
    {
        return $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return Group[]
     */
    public function getGroups()
    {
        return $this->groups->toArray();
    }

    public function hasGroup(int $groupId): bool
    {
        foreach ($this->getGroups() as $g) {
            if ($g->getId() === $groupId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add character.
     *
     * @param \Brave\Core\Entity\Character $character
     *
     * @return Corporation
     */
    public function addCharacter(Character $character)
    {
        $this->characters[] = $character;

        return $this;
    }

    /**
     * Remove character.
     *
     * @param \Brave\Core\Entity\Character $character
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeCharacter(Character $character)
    {
        return $this->characters->removeElement($character);
    }

    /**
     * Get characters.
     *
     * @return Character[]
     */
    public function getCharacters()
    {
        return $this->characters->toArray();
    }
}
