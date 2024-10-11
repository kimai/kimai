<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Model\UserContractModel;
use App\WorkingTime\Mode\WorkingTimeMode;
use App\WorkingTime\Mode\WorkingTimeModeFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<UserContractModel>
 */
final class UserContractType extends AbstractType
{
    public function __construct(private readonly WorkingTimeModeFactory $contractModeService)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['workContractModes'] = $this->contractModeService->getAll();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $sorted = $this->contractModeService->getAll();

        usort($sorted, function (WorkingTimeMode $a, WorkingTimeMode $b) {
            return $a->getOrder() <=> $b->getOrder();
        });

        $modes = [];
        foreach ($sorted as $mode) {
            $modes[$mode->getName()] = $mode->getId();
        }

        if (\count($modes) > 1) {
            $builder->add('workContractMode', ChoiceType::class, ['label' => 'work_hours_mode', 'choices' => $modes]);
        }

        foreach ($modes as $mode) {
            $this->contractModeService->getMode($mode)->buildForm($builder, $options); // @phpstan-ignore-line
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserContractModel::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_user_contract',
        ]);
    }
}
