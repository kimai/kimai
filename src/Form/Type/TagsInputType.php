<?php
/**
 * Created by PhpStorm.
 * User: mathias
 * Date: 2019-01-05
 * Time: 14:23
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Custom form field type to enter tags or use one of autocompleted field
 *
 * @package App\Form\Type
 */
class TagsInputType extends AbstractType {

  ///** @var TagRepository */
  //private $tagRepository;

  /**
   * Konstruktor übernimmt das
   *
   */
  //public function __construct() {
  //public function __construct(TagRepository $tagRepo) {
  // * @param TagRepository $tagRepo
  //$this->tagRepository = $tagRepo;
  //}

  /**
   * @inheritDoc
   */
  public function configureOptions(OptionsResolver $resolver) {
    $resolver->setDefaults([
        'label' => 'label.tag'
      // 'class' => Tag::class
      // 'data_class' => Tag::class,
      // 'required' => FALSE
    ]);
  }


  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return TextType::class;
  }


}