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
    /**
     * @var string
     */
    private $groupAttribute;
    /**
     * @var array
     */
    private $groupMapping;

    public function __construct(array $attributes)
    {
        $this->mapping = $attributes['mapping'];
        $this->groupAttribute = $attributes['groups']['attribute'];
        $this->groupMapping = $attributes['groups']['mapping'];
    }

    public function createUser(SamlTokenInterface $token)
    {
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername($token->getUsername());
        $user->setPassword('');

        // extract user roles from a special saml attribute
        if (!empty($this->groupAttribute) && $token->hasAttribute($this->groupAttribute)) {
            $samlGroups = $token->getAttribute($this->groupAttribute);
            if (is_string($samlGroups)) {
                $samlGroups = [$samlGroups];
            }
            foreach ($samlGroups as $groupName) {
                if (array_key_exists($groupName, $this->groupMapping)) {
                    $groupName = $this->groupMapping[$groupName];
                }
                $user->addRole($groupName);
            }
        }

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
        if (is_string($attribute)) {
            $attributes = $token->getAttributes();
            if ('$$' === substr($attribute, 0, 1)) {
                return $attributes[substr($attribute, 2)];
            } elseif ('$' === substr($attribute, 0, 1)) {
                return $attributes[substr($attribute, 1)][0];
            }
        }

        return $attribute;
    }
}
