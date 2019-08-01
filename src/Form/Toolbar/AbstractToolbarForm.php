<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Toolbar;

use App\Form\Type\ActivityType;
use App\Form\Type\CustomerType;
use App\Form\Type\DateRangeType;
use App\Form\Type\PageSizeType;
use App\Form\Type\ProjectType;
use App\Form\Type\TagsInputType;
use App\Form\Type\UserRoleType;
use App\Form\Type\UserType;
use App\Form\Type\VisibilityType;
use App\Repository\ActivityRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ActivityFormTypeQuery;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Defines the base form used for all toolbars.
 *
 * Extend this class and stack the elements defined here, they are coupled to each other and with the toolbar.js.
 */
abstract class AbstractToolbarForm extends AbstractType
{
    /**
     * Dirty hack to enable easy handling of GET form in controller and javascript.
     * Cleans up the name of all form elements (and unfortunately of the form itself).
     *
     * @return null|string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addUserChoice(FormBuilderInterface $builder)
    {
        $builder->add('user', UserType::class, [
            'label' => 'label.user',
            'required' => false,
        ]);
    }

    protected function addCustomerChoice(FormBuilderInterface $builder)
    {
        // just a fake field for having this field at the right position in the frontend
        $builder->add('customer', HiddenType::class);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $event->getForm()->add('customer', CustomerType::class, [
                    'required' => false,
                    'project_enabled' => true,
                    'query_builder' => function (CustomerRepository $repo) use ($data) {
                        $query = new CustomerFormTypeQuery();
                        if (isset($data['customer']) && !empty($data['customer'])) {
                            $query->setCustomer($data['customer']);
                        }

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]);
            }
        );
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string $label
     */
    protected function addVisibilityChoice(FormBuilderInterface $builder, string $label = 'label.visible')
    {
        $builder->add('visibility', VisibilityType::class, [
            'required' => false,
            'placeholder' => null,
            'label' => $label
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addPageSizeChoice(FormBuilderInterface $builder)
    {
        $builder->add('pageSize', PageSizeType::class, [
            'required' => false,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addUserRoleChoice(FormBuilderInterface $builder)
    {
        $builder->add('role', UserRoleType::class, [
            'required' => false,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addDateRangeChoice(FormBuilderInterface $builder, $allowEmpty = true)
    {
        $builder->add('daterange', DateRangeType::class, [
            'required' => false,
            'allow_empty' => $allowEmpty,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addProjectChoice(FormBuilderInterface $builder)
    {
        // just a fake field for having this field at the right position in the frontend
        $builder->add('project', HiddenType::class);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $event->getForm()->add('project', ProjectType::class, [
                    'required' => false,
                    'activity_enabled' => true,
                    'query_builder' => function (ProjectRepository $repo) use ($data) {
                        $query = new ProjectFormTypeQuery();

                        if (isset($data['customer']) && !empty($data['customer'])) {
                            $query->setCustomer($data['customer']);
                        }
                        if (isset($data['project']) && !empty($data['project'])) {
                            $query->setProject($data['project']);
                        }

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]);
            }
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addActivityChoice(FormBuilderInterface $builder)
    {
        // just a fake field for having this field at the right position in the frontend
        $builder->add('activity', HiddenType::class);

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $event->getForm()->add('activity', ActivityType::class, [
                    'required' => false,
                    'query_builder' => function (ActivityRepository $repo) use ($data) {
                        $query = new ActivityFormTypeQuery();

                        if (isset($data['activity']) && !empty($data['activity'])) {
                            $query->setActivity($data['activity']);
                        }
                        if (isset($data['project']) && !empty($data['project'])) {
                            $query->setProject($data['project']);
                        }

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]);
            }
        );
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addHiddenPagination(FormBuilderInterface $builder)
    {
        $builder->add('page', HiddenType::class, [
            'empty_data' => 1
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addTagInputField(FormBuilderInterface $builder)
    {
        $builder->add('tags', TagsInputType::class, [
            'required' => false
        ]);
    }
}
