<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\AccessToken;
use App\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThan;

final class AccessTokenForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('expiresAt', DatePickerType::class, [
                'label' => 'expires',
                'required' => false,
                'force_time' => 'end',
                'min_day' => $options['min_date'],
                'constraints' => [
                    new GreaterThan($options['min_date'])
                ],
            ])
            ->add('scopes', ChoiceType::class, [
                'label' => 'permissions',
                'help' => 'api_token.scopes_help',
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                // only scopes the user may actually use are offered (grouped by resource)
                'choices' => $options['scope_choices'],
                // secure default: nothing preselected, at least one scope is mandatory
                'constraints' => [
                    new Count(min: 1, minMessage: 'At least one permission must be selected.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccessToken::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'access_token_form',
            'attr' => [
                'data-form-event' => 'kimai.accessToken'
            ],
            'min_date' => new \DateTimeImmutable('today 00:00:00'),
            'scope_choices' => [],
        ]);
        $resolver->setAllowedTypes('min_date', [\DateTimeInterface::class]);
        $resolver->setAllowedTypes('scope_choices', ['array']);
    }
}
