<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/*
 * @ ORM\Table(name="kimai2_roles",
 *     indexes={
 *          @ORM\Index(columns={"visible","project_id"}),
 *          @ORM\Index(columns={"visible","project_id","name"}),
 *          @ORM\Index(columns={"visible","name"})
 *     }
 * )
 * @ ORM\Entity(repositoryClass="App\Repository\ActivityRepository")
 *
 * columns={"visible","name"}               => IDX_8811FE1C7AB0E8595E237E06         => activity administration without filter
 * columns={"visible","project_id"}         => IDX_8811FE1C7AB0E859166D1F9C         => activity administration with customer or project filter
 * columns={"visible","project_id","name"}  => IDX_8811FE1C7AB0E859166D1F9C5E237E06 => activity drop-down for global activities in toolbar or globalsOnly filter in activity administration
 */
class Role
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     * @Assert\Length(min=7, max=50)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Role")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     **/
    private $parent;
}
