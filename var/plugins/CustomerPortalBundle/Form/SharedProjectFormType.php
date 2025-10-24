<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Form;

use App\Form\Type\CustomerType;
use App\Form\Type\ProjectType;
use App\Form\Type\YesNoType;
use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\CustomerPortalBundle\Model\RecordMergeMode;
use KimaiPlugin\CustomerPortalBundle\Service\ManageService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SharedProjectTimesheet>
 */
class SharedProjectFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mergeRecordTypes = array_flip(RecordMergeMode::getModes());

        if ($this->getType() === SharedProjectTimesheet::TYPE_PROJECT) {
            $builder->add('project', ProjectType::class, [
                'required' => true,
            ]);
        } elseif ($this->getType() === SharedProjectTimesheet::TYPE_CUSTOMER) {
            $builder->add('customer', CustomerType::class, [
                'required' => true,
            ]);
        }

        $builder
            ->add('recordMergeMode', ChoiceType::class, [
                'label' => 'shared_project_timesheets.manage.form.record_merge_mode',
                'required' => true,
                'choices' => $mergeRecordTypes,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'password',
                'required' => false,
                'always_empty' => false,
                'mapped' => false,
                'help' => 'shared_project_timesheets.manage.form.password_hint',
            ])
            ->add(
                $builder
                    ->create('tableOptions', FormType::class, [
                        'label' => 'shared_project_timesheets.manage.form.table_options',
                        'inherit_data' => true,
                        'required' => false,
                    ])
                    ->add('entryUserVisible', YesNoType::class, [
                        'label' => 'shared_project_timesheets.manage.form.entry_user_visible',
                        'required' => false,
                    ])
                    ->add('entryRateVisible', YesNoType::class, [
                        'label' => 'shared_project_timesheets.manage.form.entry_rate_visible',
                        'required' => false,
                    ])
            )
            ->add(
                $builder
                    ->create('chartOptions', FormType::class, [
                        'label' => 'shared_project_timesheets.manage.form.chart_options',
                        'inherit_data' => true,
                        'required' => false,
                    ])
                    ->add('annualChartVisible', YesNoType::class, [
                        'label' => 'shared_project_timesheets.manage.form.annual_chart_visible',
                        'required' => false,
                    ])
                    ->add('monthlyChartVisible', YesNoType::class, [
                        'label' => 'shared_project_timesheets.manage.form.monthly_chart_visible',
                        'required' => false,
                    ])
            )
            ->add(
                $builder
                    ->create('statisticsOptions', FormType::class, [
                        'label' => 'shared_project_timesheets.manage.form.statistics_options',
                        'inherit_data' => true,
                        'required' => false,
                    ])
                    ->add('budgetStatsVisible', YesNoType::class, [
                        'label' => 'shared_project_timesheets.manage.form.budget_stats_visible',
                        'required' => false,
                    ])
                    ->add('timeBudgetStatsVisible', YesNoType::class, [
                        'label' => 'shared_project_timesheets.manage.form.time_budget_stats_visible',
                        'required' => false,
                    ])
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SharedProjectTimesheet::class,
            'attr' => [
                'data-form-event' => 'kimai.sharedProject'
            ],
        ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $form->get('password')->getData();
        if (\is_string($data) && trim($data) !== '') {
            $view['password']->vars['value'] = ManageService::PASSWORD_DO_NOT_CHANGE_VALUE;
        }
    }

    protected function getType(): string
    {
        return SharedProjectTimesheet::TYPE_PROJECT;
    }
}
