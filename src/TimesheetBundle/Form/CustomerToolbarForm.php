<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form;

use AppBundle\Form\Type\PageSizeType;
use AppBundle\Form\Type\VisibilityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Repository\Query\CustomerQuery;

/**
 * Defines the form used for filtering the customer.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class CustomerToolbarForm extends AbstractType
{
    /**
     * Dirty hack to enable easy handling of GET form in controller and javascript.
     *Cleans up the name of all form elents (and unfortunately of the form itself).
     *
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var CustomerQuery $query */
        $query = $options['data'];

        $builder
            ->add('pageSize', PageSizeType::class, [
                'required' => false,
            ])
            ->add('visibility', VisibilityType::class, [
                'required' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomerQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
