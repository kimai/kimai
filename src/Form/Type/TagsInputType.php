<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Form\DataTransformer\TagArrayToStringTransformer;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Custom form field type to enter tags or use one of autocompleted field
 */
class TagsInputType extends AbstractType
{
    /**
     * @var TagArrayToStringTransformer
     */
    private $transformer;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @param TagArrayToStringTransformer $transformer
     * @param UrlGeneratorInterface $router
     */
    public function __construct(TagArrayToStringTransformer $transformer, UrlGeneratorInterface $router)
    {
        $this->transformer = $transformer;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addModelTransformer(new CollectionToArrayTransformer(), true)
            ->addModelTransformer($this->transformer, true);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'label.tag',
            'attr' => [
                'data-autocomplete-url' => $this->router->generate('get_tags'),
                'class' => 'js-autocomplete',
                'autocomplete' => 'off',
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }
}
