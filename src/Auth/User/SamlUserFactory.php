<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Auth\User;

use App\Entity\User;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlTokenInterface;
use Hslavich\OneloginSamlBundle\Security\User\SamlUserFactoryInterface;

final class SamlUserFactory implements SamlUserFactoryInterface
{
    /**
     * @var array
     */
    private $mapping;

    public function __construct(array $attributes)
    {
        $this->mapping = $attributes['mapping'];
    }

    public function createUser(SamlTokenInterface $token)
    {
        $user = new User();
        // "enabled" state is currently ignored when using SAML, but lets set it properly
        $user->setEnabled(true);
        $user->setUsername($token->getUsername());
        $user->setPassword('');

        $reflection = new \ReflectionClass(User::class);
        foreach ($this->mapping as $field => $attribute) {
            $value = $this->getPropertyValue($token, $attribute);
            $setter = 'set' . ucfirst($field);
            $adder = 'add' . ucfirst($field);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            } elseif (method_exists($user, $adder)) {
                $user->$adder($value);
            } elseif (property_exists($user, $field)) {
                $property = $reflection->getProperty($field);
                $property->setAccessible(true);
                $property->setValue($user, $value);
            } else {
                throw new \RuntimeException('Invalid mapping field given: ' . $field);
            }
        }

        return $user;
    }

    private function getPropertyValue($token, $attribute)
    {
        if (is_string($attribute) && '$' == substr($attribute, 0, 1)) {
            $attributes = $token->getAttributes();

            return $attributes[substr($attribute, 1)][0];
        }

        return $attribute;
    }
}
