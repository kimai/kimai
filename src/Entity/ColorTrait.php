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
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

trait ColorTrait
{
    /**
     * The assigned color in HTML hex format, eg. #dd1d00
     *
     * @var string
     *
     * @Serializer\Expose()
     * @Serializer\Groups({"Default"})
     *
     * @Exporter\Expose(label="label.color")
     *
     * @ORM\Column(name="color", type="string", length=7, nullable=true)
     * @Assert\Length(min=4, max=7)
     */
    private $color = null;

    /**
     * @return string
     */
    public function getColor(): ?string
    {
        if ($this->color === Constants::DEFAULT_COLOR) {
            return null;
        }

        return $this->color;
    }

    /**
     * @param string $color
     * @return self
     */
    public function setColor(?string $color = null)
    {
        $this->color = $color;

        return $this;
    }
}
