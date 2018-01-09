<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TimesheetBundle\Form\Toolbar;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TimesheetBundle\Form\Type\CustomerType;
use TimesheetBundle\Repository\CustomerRepository;
use TimesheetBundle\Repository\Query\CustomerQuery;
use TimesheetBundle\Repository\Query\ProjectQuery;

/**
 * Defines the form used for filtering the projects.
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class ProjectToolbarForm extends VisibilityToolbarForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('customer', CustomerType::class, [
                'required' => false,
                'query_builder' => function (CustomerRepository $repo) {
                    $query = new CustomerQuery();
                    $query->setVisibility(CustomerQuery::SHOW_BOTH); // this field is the reason for the query here
                    $query->setResultType(CustomerQuery::RESULT_TYPE_QUERYBUILDER);
                    return $repo->findByQuery($query);
                },
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProjectQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
