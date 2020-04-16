<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Form\Type\CustomerType;
use App\Form\Type\ProjectType;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\CustomerFormTypeQuery;
use App\Repository\Query\ProjectFormTypeQuery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityEditForm extends AbstractType
{
    use EntityFormTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $project = null;
        $customer = null;
        $id = null;

        if (isset($options['data'])) {
            /** @var Activity $entry */
            $entry = $options['data'];

            if (null !== $entry->getProject()) {
                $project = $entry->getProject();
                $customer = $project->getCustomer();
                $options['currency'] = $customer->getCurrency();
            }

            $id = $entry->getId();
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
                'attr' => [
                    'autofocus' => 'autofocus'
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'label.description',
                'required' => false,
            ])
        ;

        if ($options['customer']) {
            $builder
                ->add('customer', CustomerType::class, [
                    'query_builder' => function (CustomerRepository $repo) use ($builder, $customer) {
                        $query = new CustomerFormTypeQuery($customer);
                        $query->setUser($builder->getOption('user'));

                        return $repo->getQueryBuilderForFormType($query);
                    },
                    'data' => $customer ? $customer : null,
                    'required' => false,
                    'mapped' => false,
                    'project_enabled' => true,
                ]);
        }

        $builder
            ->add('project', ProjectType::class, [
                'required' => false,
                'query_builder' => function (ProjectRepository $repo) use ($builder, $project, $customer) {
                    $query = new ProjectFormTypeQuery($project, $customer);
                    $query->setUser($builder->getOption('user'));
                    $query->setIgnoreDate(true);

                    return $repo->getQueryBuilderForFormType($query);
                },
            ]);

        // replaces the project select after submission, to make sure only projects for the selected customer are displayed
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($builder, $project) {
                $data = $event->getData();
                if (!isset($data['customer']) || empty($data['customer'])) {
                    return;
                }

                $event->getForm()->add('project', ProjectType::class, [
                    'group_by' => null,
                    'query_builder' => function (ProjectRepository $repo) use ($builder, $data, $project) {
                        $query = new ProjectFormTypeQuery($project, $data['customer']);
                        $query->setUser($builder->getOption('user'));
                        $query->setIgnoreDate(true);

                        return $repo->getQueryBuilderForFormType($query);
                    },
                ]);
            }
        );

        $this->addCommonFields($builder, $options);

        if (null === $id && $options['create_more']) {
            $this->addCreateMore($builder);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_activity_edit',
            'create_more' => false,
            'customer' => false,
            'currency' => Customer::DEFAULT_CURRENCY,
            'include_budget' => false,
            'attr' => [
                'data-form-event' => 'kimai.activityUpdate'
            ],
        ]);
    }
}
