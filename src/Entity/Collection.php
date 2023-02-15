<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *     "self",
 *     href = "expr('/api/collections/' ~ object.getIdentifier())"
 * )
 */
#[ORM\Entity(repositoryClass: Collection::class)]
class Collection
{
    #[JMS\Exclude]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private string $name;

    #[ORM\Column]
    private string $blockchain;

    #[ORM\Column]
    private string $status;

    #[ORM\Column(nullable: true)]
    private int|null $supply;

    #[ORM\Column(unique: true)]
    private string $identifier;

    #[JMS\Exclude]
    #[ORM\OneToMany(targetEntity: Token::class, mappedBy: 'collection')]
    private $tokens;

    #[JMS\Exclude]
    #[ORM\OneToMany(targetEntity: TraitType::class, mappedBy: 'collection')]
    private $traitTypes;

    #[JMS\Exclude]
    #[ORM\OneToMany(targetEntity: Attribute::class, mappedBy: 'collection')]
    private $attributes;

    #[JMS\Exclude]
    #[ORM\OneToMany(targetEntity: Rank::class, mappedBy: 'collection')]
    private $ranks;

    #[JMS\Exclude]
    #[ORM\Column(nullable: true)]
    private string|null $traitFileExtension = null;

    public function __construct()
    {
        $this->tokens = new ArrayCollection();
        $this->ranks = new ArrayCollection();
        $this->traitTypes = new ArrayCollection();
        $this->attributes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Collection
     */
    public function setId(int $id): Collection
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getBlockchain(): string
    {
        return $this->blockchain;
    }

    /**
     * @param string $blockchain
     * @return Collection
     */
    public function setBlockchain(string $blockchain): Collection
    {
        $this->blockchain = $blockchain;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return Collection
     */
    public function setIdentifier(string $identifier): Collection
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getTokens(): ArrayCollection
    {
        return $this->tokens;
    }

    public function addToken(Token $token): void
    {
        $this->tokens->add($token);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Collection
     */
    public function setName(string $name): Collection
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTraitFileExtension(): ?string
    {
        return $this->traitFileExtension;
    }

    /**
     * @param string $traitFileExtension
     * @return Collection
     */
    public function setTraitFileExtension(string $traitFileExtension): Collection
    {
        $this->traitFileExtension = $traitFileExtension;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Collection
     */
    public function setStatus(string $status): Collection
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTraitTypes(): ArrayCollection
    {
        return $this->traitTypes;
    }

    /**
     * @param TraitType $traitType
     */
    public function addTraitType(TraitType $traitType): void
    {
        $this->traitTypes->add($traitType);
    }

    /**
     * @return ArrayCollection
     */
    public function getAttributes(): ArrayCollection
    {
        return $this->attributes;
    }

    /**
     * @param Attribute $attribute
     */
    public function addAttribute(Attribute $attribute): void
    {
        $this->attributes->add($attribute);
    }

    /**
     * @return int|null
     */
    public function getSupply(): ?int
    {
        return $this->supply;
    }

    /**
     * @param int|null $supply
     * @return Collection
     */
    public function setSupply(?int $supply): Collection
    {
        $this->supply = $supply;
        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getRanks(): ArrayCollection
    {
        return $this->ranks;
    }

    /**
     * @param Rank $rank
     */
    public function addRank(Rank $rank)
    {
        $this->ranks->add($rank);
    }
}
