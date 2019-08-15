<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Entity\User;
use App\Security\CurrentUser;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserExtension extends AbstractTypeExtension
{
    /**
     * @var CurrentUser
     */
    private $user;

    public function __construct(CurrentUser $user)
    {
        $this->user = $user;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['user']);
        // null needs to be allowed, as there is no user for anonymoud forms (like "forgot password" and "registration")
        $resolver->setAllowedTypes('user', [User::class, 'null']);
        $resolver->setDefault('user', $this->user->getUser());
    }
}
