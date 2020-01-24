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

        if (!array_key_exists('email', $attributes['mapping'])) {
            throw new \InvalidArgumentException('Your SAML mapping is missing an attribute for the users email');
        }
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
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            } elseif (property_exists($user, $field)) {
                $property = $reflection->getProperty($field);
                $property->setAccessible(true);
                $property->setValue($user, $value);
            } else {
                throw new \RuntimeException('Invalid mapping field given: ' . $field);
            }
        }

        if (empty($user->getUsername())) {
            $user->setUsername($user->getEmail());
        }

        return $user;
    }

    private function getPropertyValue($token, $attribute)
    {
        if (is_string($attribute)) {
            $attributes = $token->getAttributes();
            if ('$$' === substr($attribute, 0, 2)) {
                $key = substr($attribute, 2);
                if (isset($attributes[$key])) {
                    return $attributes[$key][0];
                }

                return null;
            } elseif ('$' === substr($attribute, 0, 1)) {
                $key = substr($attribute, 1);
                if (!isset($attributes[$key])) {
                    throw new \RuntimeException('Missing user attribute: ' . $key);
                }

                return $attributes[$key][0];
            }
        }

        return $attribute;
    }
}
