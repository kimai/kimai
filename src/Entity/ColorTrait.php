<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Constants;
use App\Export\Annotation as Exporter;
use App\Utils\Color;
use App\Validator\Constraints as Constraints;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

trait ColorTrait
{
    /**
     * The assigned color in HTML hex format, e.g. #dd1d00
     */
    #[ORM\Column(name: 'color', type: Types::STRING, length: 7, nullable: true)]
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Exporter\Expose(label: 'color')]
    #[Constraints\HexColor]
    private ?string $color = null;

    public function getColor(): ?string
    {
        if ($this->color === Constants::DEFAULT_COLOR) {
            return null;
        }

        return $this->color;
    }

    public function hasColor(): bool
    {
        return null !== $this->color && $this->color !== Constants::DEFAULT_COLOR;
    }

    public function setColor(?string $color = null): void
    {
        $this->color = $color;
    }

    abstract public function getName(): ?string;

    /**
     * Color will never be empty and is generated from the entity tag name if not set explicit.
     */
    #[Serializer\VirtualProperty]
    #[Serializer\SerializedName('color-safe')]
    #[Serializer\Groups(['Default'])]
    public function getColorSafe(): string
    {
        return $this->getColor() ?? (new Color())->getRandom($this->getName());
    }
}
