<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 2019-01-05
 * Time: 14:23
 */

namespace App\Form\Type;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to enter tags or use one of autocompleted field
 *
 * @package App\Form\Type
 */
class TagsInputType extends AbstractType {

  /** @var TagRepository */
  private $tagRepository;

  public function __construct() {
  }

  /**
   * @inheritDoc
   */
  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults([
      'label' => 'label.tag',
      //'class' => Tag::class
      'data_class' => ArrayCollection::class
    ]);
  }


  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return TextType::class;
  }


}