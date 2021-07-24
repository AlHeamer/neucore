<?php

declare(strict_types=1);

namespace Neucore\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     required={"id", "name", "description", "esiScopes", "eveRoles"}
 * )
 * @ORM\Entity
 * @ORM\Table(name="eve_logins")
 */
class EveLogin implements \JsonSerializable
{
    /**
     * Prefix of all internal login IDs.
     */
    public const INTERNAL_LOGINS_PREFIX = 'core.';

    /**
     * Default login.
     */
    public const ID_DEFAULT = self::INTERNAL_LOGINS_PREFIX . 'default';

    /**
     * Alternative character login.
     */
    public const ID_ALT = self::INTERNAL_LOGINS_PREFIX . 'alt';

    /**
     * Login for "managed" accounts.
     */
    public const ID_MANAGED = self::INTERNAL_LOGINS_PREFIX . 'managed';

    /**
     * Login for "managed" alt characters.
     */
    public const ID_MANAGED_ALT = self::INTERNAL_LOGINS_PREFIX . 'managed-alt';

    /**
     * Login of the character that is used to send mails.
     */
    public const ID_MAIL = self::INTERNAL_LOGINS_PREFIX . 'mail';

    /**
     * Login of the character with director roles for the member tracking functionality.
     */
    public const ID_DIRECTOR = self::INTERNAL_LOGINS_PREFIX . 'director';

    /**
     * All internal login IDs.
     */
    public const INTERNAL_LOGINS = [
        self::ID_DEFAULT,
        self::ID_ALT,
        self::ID_MANAGED,
        self::ID_MANAGED_ALT,
        self::ID_MAIL,
        self::ID_DIRECTOR,
    ];

    public const SCOPE_MAIL = 'esi-mail.send_mail.v1';
    public const SCOPE_ROLES = 'esi-characters.read_corporation_roles.v1';
    public const SCOPE_TRACKING = 'esi-corporations.track_members.v1';
    public const SCOPE_STRUCTURES = 'esi-universe.read_structures.v1';
    public const SCOPE_MEMBERSHIP = 'esi-corporations.read_corporation_membership.v1';

    public const ROLE_DIRECTOR = 'Director';

    /**
     * @OA\Property(
     *     maxLength=20,
     *     pattern="^[-._a-zA-Z0-9]+$",
     *     nullable=false,
     *     description="IDs starting with 'core.' are reserverd for internal use."
     * )
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="string", length=20)
     * @var string|null
     */
    private $id;

    /**
     * @OA\Property(maxLength=255)
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $name = '';

    /**
     * @OA\Property(maxLength=1024)
     * @ORM\Column(type="string", length=1024)
     * @var string
     */
    private $description = '';

    /**
     * @OA\Property(maxLength=8192)
     * @ORM\Column(type="string", name="esi_scopes", length=8192)
     * @var string
     */
    private $esiScopes = '';

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="string"),
     *     description="Maximum length of all roles separated by comma: 1024."
     * )
     * @ORM\Column(type="string", name="eve_roles", length=1024)
     * @var string
     */
    private $eveRoles = '';

    /**
     * @ORM\OneToMany(targetEntity="EsiToken", mappedBy="eveLogin")
     * @ORM\OrderBy({"character" = "ASC"})
     * @var Collection
     */
    private $esiTokens;

    public static function isValidObject(\stdClass $data): bool
    {
        return
            property_exists($data, 'id')          && is_string($data->id) &&
            property_exists($data, 'name')        && is_string($data->name) &&
            property_exists($data, 'description') && is_string($data->description) &&
            property_exists($data, 'esiScopes')   && is_string($data->esiScopes) &&
            property_exists($data, 'eveRoles')    && is_array($data->eveRoles);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'esiScopes' => $this->esiScopes,
            'eveRoles' => $this->getEveRoles(),
        ];
    }

    public function __construct()
    {
        $this->esiTokens = new ArrayCollection();
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setEsiScopes(string $esiScopes): self
    {
        $this->esiScopes = $esiScopes;
        return $this;
    }

    public function getEsiScopes(): string
    {
        return $this->esiScopes;
    }

    /**
     * @param string[] $eveRoles
     */
    public function setEveRoles(array $eveRoles): self
    {
        $this->eveRoles = implode(',', $eveRoles);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getEveRoles(): array
    {
        if (empty($this->eveRoles)) {
            return [];
        }
        return explode(',', $this->eveRoles);
    }

    public function addEsiToken(EsiToken $token): self
    {
        $this->esiTokens[] = $token;
        return $this;
    }

    public function removeEsiToken(EsiToken $token): bool
    {
        return $this->esiTokens->removeElement($token);
    }

    /**
     * @return EsiToken[]
     */
    public function getEsiTokens(): array
    {
        return $this->esiTokens->toArray();
    }
}