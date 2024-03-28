<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\Customer;
use App\Entity\Project;
use App\Form\Type\CustomerType;
use App\Form\Type\DatePickerType;
use App\Form\Type\InvoiceLabelType;
use App\Form\Type\TeamType;
use App\Form\Type\YesNoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectEditForm extends AbstractType
{
    use EntityFormTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $customer = null;
        $isNew = false;
        $options['currency'] = null;
        $customerOptions = [];

        if (isset($options['data'])) {
            /** @var Project $entry */
            $entry = $options['data'];
            $isNew = $entry->getId() === null;

            if (null !== $entry->getCustomer()) {
                $customer = $entry->getCustomer();
                $options['currency'] = $customer->getCurrency();

                if (!$customer->isVisible()) {
                    // force visibility, see https://github.com/kimai/kimai/issues/3985
                    $customerOptions['pre_select_customer'] = true;
                }
            }
        }

        $dateTimeOptions = [
            'model_timezone' => $options['timezone'],
            'view_timezone' => $options['timezone'],
        ];
        // primarily for API usage, where we cannot use a user/locale specific format
        if (null !== $options['date_format']) {
            $dateTimeOptions['format'] = $options['date_format'];
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
                'attr' => [
                    'autofocus' => 'autofocus'
                ],
            ])
            ->add('number', TextType::class, [
                'label' => 'project_number',
                'required' => false,
                'attr' => [
                    'maxlength' => 10,
                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'description',
                'required' => false,
            ])
            ->add('invoiceText', InvoiceLabelType::class)
            ->add('orderNumber', TextType::class, [
                'label' => 'orderNumber',
                'required' => false,
            ])
            ->add('orderDate', DatePickerType::class, array_merge($dateTimeOptions, [
                'label' => 'orderDate',
                'required' => false,
                'force_time' => 'start',
            ]))
            ->add('start', DatePickerType::class, array_merge($dateTimeOptions, [
                'label' => 'project_start',
                'required' => false,
                'force_time' => 'start',
            ]))
            ->add('end', DatePickerType::class, array_merge($dateTimeOptions, [
                'label' => 'project_end',
                'required' => false,
                'force_time' => 'end',
            ]))
            ->add('customer', CustomerType::class, array_merge([
                'placeholder' => ($isNew && null === $customer) ? '' : false,
                'customers' => $customer,
                'query_builder_for_user' => true,
            ], $customerOptions))
            ->add('globalActivities', YesNoType::class, [
                'label' => 'globalActivities',
                'help' => 'help.globalActivities'
            ])
        ;

        if ($isNew) {
            $builder
                ->add('teams', TeamType::class, [
                    'required' => false,
                    'multiple' => true,
                    'expanded' => false,
                    'by_reference' => false,
                    'help' => 'help.teams',
                ]);
        }

        $this->addCommonFields($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'admin_project_edit',
            'currency' => Customer::DEFAULT_CURRENCY,
            'date_format' => null,
            'include_budget' => false,
            'include_time' => false,
            'timezone' => date_default_timezone_get(),
            'attr' => [
                'data-form-event' => 'kimai.projectUpdate'
            ],
        ]);
    }
}
