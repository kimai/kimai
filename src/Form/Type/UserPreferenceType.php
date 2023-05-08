<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use App\Entity\UserPreference;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Custom form field type to edit a user preference.
 * @extends AbstractType<UserPreference>
 */
final class UserPreferenceType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var UserPreference $preference */
                $preference = $event->getData();

                $options = $preference->getOptions();
                $constraints = $preference->getConstraints();

                $required = true;
                if (\array_key_exists('required', $options)) {
                    $required = (bool) $options['required'];
                    unset($options['required']);
                }

                if (\in_array($preference->getType(), [TextType::class, TextareaType::class])) {
                    $constraints[] = new Length(['max' => 255]);
                }

                if (\in_array($preference->getType(), [CheckboxType::class, YesNoType::class])) {
                    $required = false;
                }

                // this will prevent that someone deletes the form element from the DOM and submits the form without
                if ($required) {
                    $constraints[] = new NotNull();
                }

                $type = $preference->getType();
                if (!$preference->isEnabled()) {
                    $type = HiddenType::class;
                }

                $transId = $preference->getName();
                if ($this->translator->trans($transId) === $transId) {
                    $transId = $preference->getName();
                }

                $options = array_merge(
                    [
                        'label' => $transId,
                        'constraints' => $constraints,
                        'required' => $required,
                        'disabled' => !$preference->isEnabled(),
                    ],
                    $options
                );

                $event->getForm()->add('value', $type, $options);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserPreference::class,
        ]);
    }
}
