<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Form\Model\TotpActivation;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UserTwoFactorType extends AbstractType
{
    public function __construct(private TotpAuthenticatorInterface $totpAuthenticator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'login.2fa_label',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Callback([$this, 'validateCode']),
                ],
            ])
            ->add('enabled', HiddenType::class, [
                'mapped' => false,
                'label' => false,
                'required' => true,
            ])
        ;
    }

    public function validateCode(mixed $payload, ExecutionContextInterface $context): void
    {
        $form = $context->getRoot();
        /** @var TotpActivation $data */
        $data = $form->getData();

        if ($data->getCode() === null) {
            return;
        }

        if (!$this->totpAuthenticator->checkCode($data->getUser(), $data->getCode())) {
            $context
                ->buildViolation('The given code is not the correct TOTP token.')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TotpActivation::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'edit_user_2fa',
        ]);
    }
}
