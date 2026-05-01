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
     * @var array<string, int|string|null|bool>
     */
    #[ORM\Column(name: 'options', type: Types::JSON, nullable: false)]
    #[Assert\NotNull]
    private array $options = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->id === null;
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

    public function getOption(string $key, int|string|bool|null $default): int|string|bool|null
    {
        if (\array_key_exists($key, $this->options)) {
            return $this->options[$key] ?? $default;
        }

        return $default;
    }

    public function setOption(string $key, int|string|bool|null $value): void
    {
        if ($value === null) {
            if (\array_key_exists($key, $this->options)) {
                unset($this->options[$key]);
            }

            return;
        }

        $this->options[$key] = $value;
    }

    /**
     * @return array<string, int|string|null|bool>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, int|string|null|bool> $options
     */
    public function setOptions(?array $options): void
    {
        $this->options = $options ?? [];
    }

    /**
     * Only used for CSV export
     */
    public function setSeparator(string $separator): void
    {
        if (!\in_array($separator, [',', ';'], true)) {
            throw new \InvalidArgumentException('Invalid separator, comma and semicolon are allowed.');
        }

        $this->setOption('separator', $separator);
    }

    public function getSeparator(): string
    {
        return (string) $this->getOption('separator', ',');
    }

    /**
     * Only used for PDF export
     */
    public function setName(?string $name): void
    {
        $this->setOption('name', $name);
    }

    public function getName(): ?string
    {
        $name = $this->getOption('name', null);

        return \is_string($name) ? $name : null;
    }

    /**
     * Only used for PDF export
     */
    public function setPageSize(?string $pageSize): void
    {
        $this->setOption('pageSize', $pageSize);
    }

    public function getPageSize(): ?string
    {
        $pageSize = $this->getOption('pageSize', null);

        return \is_string($pageSize) ? $pageSize : null;
    }

    /**
     * Only used for PDF export
     */
    public function setOrientation(?string $orientation): void
    {
        if ($orientation !== null) {
            $orientation = strtolower($orientation);
            if (!\in_array($orientation, ['landscape', 'portrait'], true)) {
                throw new \InvalidArgumentException('Invalid orientation. Allowed values are "landscape" and "portrait".');
            }
        }
        $this->setOption('orientation', $orientation);
    }

    public function getOrientation(): ?string
    {
        $orientation = $this->getOption('orientation', null);

        return \is_string($orientation) ? $orientation : null;
    }

    /**
     * Only used for PDF export
     * @param array<string> $columns
     */
    public function setSummaryColumns(array $columns): void
    {
        $columns = \count($columns) > 0 ? implode(',', $columns) : null;

        $this->setOption('summary_columns', $columns);
    }

    /**
     * @return array<string>
     */
    public function getSummaryColumns(): array
    {
        $columns = $this->getOption('summary_columns', null);
        if (!\is_string($columns)) {
            return [];
        }

        return explode(',', $columns);
    }

    /**
     * Only used for PDF export
     */
    public function setFont(?string $font): void
    {
        $this->setOption('font', $font);
    }

    public function getFont(): ?string
    {
        $font = $this->getOption('font', null);

        return \is_string($font) ? $font : null;
    }

    public function isAvailableForAll(): bool
    {
        $isAllowed = $this->getOption('user_access', false);

        return \is_bool($isAllowed) ? $isAllowed : false;
    }

    public function setAvailableForAll(bool $userAccess): void
    {
        $this->setOption('user_access', $userAccess);
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
