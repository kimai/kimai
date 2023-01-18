<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserExtension extends AbstractTypeExtension
{
    public function __construct(private Security $security)
    {
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['user']);
        // null needs to be allowed, as there is no user for anonymous forms (like "forgot password" and "registration")
        $resolver->setAllowedTypes('user', [User::class, 'null']);
        $resolver->setDefault('user', $this->security->getUser());
    }
}
