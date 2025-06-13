<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\ExportTemplateRepository;
use App\Validator\Constraints\ExportRenderer;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'kimai2_export_templates')]
#[ORM\UniqueConstraint(columns: ['title'])]
#[ORM\Entity(repositoryClass: ExportTemplateRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
#[UniqueEntity('title')]
class ExportTemplate
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;
    #[ORM\Column(name: 'title', type: Types::STRING, length: 100, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    private ?string $title = null;
    #[ORM\Column(name: 'renderer', type: Types::STRING, length: 20, nullable: false)]
    #[ExportRenderer]
    #[Assert\NotBlank]
    private string $renderer = 'csv';
    /**
     * Used for header column translation.
     */
    #[ORM\Column(name: 'language', type: Types::STRING, length: 6, nullable: true)]
    #[Assert\Locale]
    private ?string $language = null;
    /**
     * @var array<int, string>
     */
    #[ORM\Column(name: 'columns', type: Types::JSON, nullable: false)]
    #[Assert\Count(min: 1)]
    #[Assert\NotNull]
    private array $columns = [];
    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'options', type: Types::JSON, nullable: false)]
    #[Assert\NotNull]
    private array $options = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setRenderer(string $renderer): void
    {
        $this->renderer = $renderer;
    }

    public function getRenderer(): string
    {
        return $this->renderer;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array<int, string>|null $columns
     */
    public function setColumns(?array $columns): void
    {
        $this->columns = $columns ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(?array $options): void
    {
        $this->options = $options ?? [];
    }

    public function __toString(): string
    {
        return $this->title ?? 'New';
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
