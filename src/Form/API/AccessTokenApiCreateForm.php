<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\API;

use App\Entity\AccessToken;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;

final class AccessTokenApiCreateForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
            ])
            ->add('expiresAt', DateTimeApiType::class, [
                'required' => false,
                'constraints' => [
                    new GreaterThan($options['min_date']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccessToken::class,
            'csrf_protection' => false,
            'min_date' => new \DateTimeImmutable('today 00:00:00'),
        ]);
        $resolver->setAllowedTypes('min_date', [\DateTimeInterface::class]);
    }
}
