<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Tag
 *
 * @ORM\Table(name="tags")
 * @ORM\Entity(repositoryClass="App\Repository\TagRepository")
 */
class Tag {

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
   * @ORM\Column(name="tag_name", type="string", length=255, nullable=false)
   * @Assert\NotBlank()
   * @Assert\Length(min=2, max=255)
   */
  private $tagName;

  /**
   * @var \App\Entity\Timesheet[]
   *
   * @ORM\ManyToMany(targetEntity="Timesheet", mappedBy="tags")
   */
  protected $timesheets;

  /**
   * Default constructor, initializes collections
   */
  public function __construct()
  {
    $this->timesheets = new ArrayCollection();
  }

  /**
   * @return int
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param string $tagName
   *
   * @return Tag
   */
  public function setName($tagName) {
    $this->tagName = $tagName;

    return $this;
  }

  /**
   * @return string
   */
  public function getTagName() {
    return $this->tagName;
  }

  /**
   * @param Timesheet $timesheet
   */
  public function addTimesheet(Timesheet $timesheet)
  {
    if ($this->timesheets->contains($timesheet)) {
      return;
    }
    $this->timesheets->add($timesheet);
    $timesheet->addTag($this);
  }

  /**
   * @param Timesheet $timesheet
   */
  public function removeTimesheet(Timesheet $timesheet)
  {
    if (!$this->timesheets->contains($timesheet)) {
      return;
    }
    $this->timesheets->removeElement($timesheet);
    $timesheet->removeTag($this);
  }

  /**
   * @return string
   */
  public function __toString() {
    return $this->getTagName();
  }

}