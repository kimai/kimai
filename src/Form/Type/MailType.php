<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

final class MailType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'email',
            // no constraint by default, because the validation should be triggered
            // by a constraint on the entity, otherwise the error shows up twice
            // see User::$email or Customer::$email
            // the test UserControllerTest::testValidationForCreateAction() otherwise fails
        ]);
    }

    public function getParent(): string
    {
        return EmailType::class;
    }
}
