<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_tags')]
#[ORM\UniqueConstraint(columns: ['name'])]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[ORM\Index(columns: ['visible'])]
#[UniqueEntity('name')]
#[Serializer\ExclusionPolicy('all')]
class Tag
{
    /**
     * Internal Tag ID
     */
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?int $id = null;
    /**
     * The tag name
     */
    #[ORM\Column(name: 'name', type: Types::STRING, length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100, normalizer: 'trim')]
    #[Assert\Regex(pattern: '/,/', message: 'Tag name cannot contain comma', match: false)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private ?string $name = null;
    #[ORM\Column(name: 'visible', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    #[Assert\NotNull]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    private bool $visible = true;

    use ColorTrait;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $tagName): Tag
    {
        $this->name = $tagName !== null ? trim($tagName) : $tagName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
