<?php declare(strict_types=1);

namespace Brave\Core\Entity;

/**
 * Groups for third party apps.
 *
 * @SWG\Definition(
 *     definition="Group",
 *     required={"id", "name"}
 * )
 * @Entity(repositoryClass="Brave\Core\Entity\GroupRepository")
 * @Table(name="groups")
 */
class Group implements \JsonSerializable
{

    /**
     * Group ID.
     *
     * @SWG\Property()
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var integer
     */
    private $id;

    /**
     * A unique group name.
     *
     * @SWG\Property(maxLength=64, pattern="^[-._a-zA-Z0-9]+$")
     * @Column(type="string", unique=true, length=64)
     * @var string
     */
    private $name;

    /**
     * @ManyToMany(targetEntity="Player", mappedBy="applications")
     * @OrderBy({"name" = "ASC"})
     * @var \Doctrine\Common\Collections\Collection
     */
    private $applicants;

    /**
     * Group members.
     *
     * @ManyToMany(targetEntity="Player", mappedBy="groups")
     * @OrderBy({"name" = "ASC"})
     * @var \Doctrine\Common\Collections\Collection
     */
    private $players;

    /**
     * @ManyToMany(targetEntity="Player", inversedBy="managerGroups")
     * @JoinTable(name="group_manager")
     * @OrderBy({"name" = "ASC"})
     * @var \Doctrine\Common\Collections\Collection
     */
    private $managers;

    /**
     * @ManyToMany(targetEntity="App", mappedBy="groups")
     * @OrderBy({"name" = "ASC"})
     * @var \Doctrine\Common\Collections\Collection
     */
    private $apps;

    /**
     * Contains only information that is of interest for clients.
     *
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->applicants = new \Doctrine\Common\Collections\ArrayCollection();
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
        $this->managers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->apps = new \Doctrine\Common\Collections\ArrayCollection();
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

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Group
     */
    public function setName($name)
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
     * Add applicant.
     *
     * @param \Brave\Core\Entity\Player $applicant
     *
     * @return Group
     */
    public function addApplicant(\Brave\Core\Entity\Player $applicant)
    {
        $this->applicants[] = $applicant;

        return $this;
    }

    /**
     * Remove applicant.
     *
     * @param \Brave\Core\Entity\Player $applicant
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeApplicant(\Brave\Core\Entity\Player $applicant)
    {
        return $this->applicants->removeElement($applicant);
    }

    /**
     * Get applicants.
     *
     * @return Player[]
     */
    public function getApplicants()
    {
        return $this->applicants->toArray();
    }

    /**
     * Add player.
     *
     * @param \Brave\Core\Entity\Player $player
     *
     * @return Group
     */
    public function addPlayer(\Brave\Core\Entity\Player $player)
    {
        $this->players[] = $player;

        return $this;
    }

    /**
     * Remove player.
     *
     * @param \Brave\Core\Entity\Player $player
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePlayer(\Brave\Core\Entity\Player $player)
    {
        return $this->players->removeElement($player);
    }

    /**
     * Get players.
     *
     * @return Player[]
     */
    public function getPlayers()
    {
        return $this->players->toArray();
    }

    /**
     * Add manager.
     *
     * @param \Brave\Core\Entity\Player $manager
     *
     * @return Group
     */
    public function addManager(\Brave\Core\Entity\Player $manager)
    {
        $this->managers[] = $manager;

        return $this;
    }

    /**
     * Remove manager.
     *
     * @param \Brave\Core\Entity\Player $manager
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeManager(\Brave\Core\Entity\Player $manager)
    {
        return $this->managers->removeElement($manager);
    }

    /**
     * Get managers.
     *
     * @return Player[]
     */
    public function getManagers()
    {
        return $this->managers->toArray();
    }

    /**
     * Add app.
     *
     * @param \Brave\Core\Entity\App $app
     *
     * @return Group
     */
    public function addApp(\Brave\Core\Entity\App $app)
    {
        $this->apps[] = $app;

        return $this;
    }

    /**
     * Remove app.
     *
     * @param \Brave\Core\Entity\App $app
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeApp(\Brave\Core\Entity\App $app)
    {
        return $this->apps->removeElement($app);
    }

    /**
     * Get apps.
     *
     * @return App[]
     */
    public function getApps()
    {
        return $this->apps->toArray();
    }
}
