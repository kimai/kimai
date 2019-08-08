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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Custom form field type to edit a user preference.
 */
class UserPreferenceType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translate;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translate = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                /** @var UserPreference $preference */
                $preference = $event->getData();

                if (!($preference instanceof UserPreference)) {
                    return;
                }

                // prevents unconfigured values from showing up in the form
                if ($preference->getType() === null) {
                    return;
                }

                $required = true;
                if (CheckboxType::class == $preference->getType()) {
                    $required = false;
                }

                $type = $preference->getType();
                if (!$preference->isEnabled()) {
                    $type = HiddenType::class;
                }

                $transId = 'label.' . $preference->getName();
                if ($this->translate->trans($transId) === $transId) {
                    $transId = $preference->getName();
                }

                $options = array_merge(
                    [
                        'label' => $transId,
                        'constraints' => $preference->getConstraints(),
                        'required' => $required,
                        'disabled' => !$preference->isEnabled(),
                    ],
                    $preference->getOptions()
                );

                $event->getForm()->add('value', $type, $options);
            }
        );
        $builder->add('name', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => UserPreference::class,
        ]);
    }
}
