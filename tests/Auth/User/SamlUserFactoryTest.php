<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Auth\User;

use App\Auth\User\SamlUserFactory;
use App\Entity\User;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Auth\User\SamlUserFactory
 */
class SamlUserFactoryTest extends TestCase
{
    public function testCreateUserThrowsExceptionOnMissingAttribute()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing user attribute: title');

        $mapping = [
            'mapping' => [
                ['saml' => '$$avatar', 'kimai' => 'avatar'],
                ['saml' => '$Email', 'kimai' => 'email'],
                ['saml' => '$title', 'kimai' => 'title'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $attributes = [
            'Email' => ['test@example.com'],
        ];

        $token = new SamlToken();
        $token->setAttributes($attributes);

        $sut = new SamlUserFactory($mapping);
        $user = $sut->createUser($token);
    }

    public function testCreateUserThrowsExceptionOnInvalidMapping()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid mapping field given: foo');

        $mapping = [
            'mapping' => [
                ['saml' => '$$avatar', 'kimai' => 'avatar'],
                ['saml' => '$Email', 'kimai' => 'email'],
                ['saml' => '$Email', 'kimai' => 'foo'],
            ],
            'roles' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $attributes = [
            'Email' => ['test@example.com'],
        ];

        $token = new SamlToken();
        $token->setAttributes($attributes);

        $sut = new SamlUserFactory($mapping);
        $user = $sut->createUser($token);
    }

    public function testCreateUser()
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$$avatar', 'kimai' => 'avatar'],
                ['saml' => '$Email', 'kimai' => 'email'],
                ['saml' => 'A static super title', 'kimai' => 'title'],
            ],
            'roles' => [
                'attribute' => 'RoLeS',
                'mapping' => [
                    ['saml' => 'fooobar', 'kimai' => 'ROLE_ADMIN'],
                    ['saml' => 'ROLE_1', 'kimai' => 'ROLE_TEAMLEAD'],
                ]
            ]
        ];

        $attributes = [
            'RoLeS' => ['ROLE_1', 'ROLE_2'],
            'Email' => ['test@example.com'],
            'FOOO' => ['test', 'test2'],
            'avatar' => ['http://www.example.com/test.jpg'],
        ];

        $token = new SamlToken();
        $token->setAttributes($attributes);

        $sut = new SamlUserFactory($mapping);
        $user = $sut->createUser($token);

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isEnabled());
        self::assertEquals('', $user->getPassword());
        self::assertEquals('test@example.com', $user->getEmail());
        self::assertEquals('test@example.com', $user->getUsername());
        self::assertEquals('A static super title', $user->getTitle());
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_2', 'ROLE_USER'], $user->getRoles());
    }

    public function testCreateUserWithUsername()
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$$avatar', 'kimai' => 'avatar'],
                ['saml' => '$Email', 'kimai' => 'email'],
                ['saml' => 'A static super title', 'kimai' => 'title'],
                ['saml' => 'Mr. T', 'kimai' => 'username'],
            ],
            'roles' => [
                'attribute' => null,
                'mapping' => []
            ]
        ];

        $attributes = [
            'Email' => ['test@example.com'],
            'FOOO' => ['test', 'test2'],
            'avatar' => ['http://www.example.com/test.jpg'],
        ];

        $token = new SamlToken();
        $token->setAttributes($attributes);

        $sut = new SamlUserFactory($mapping);
        $user = $sut->createUser($token);

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isEnabled());
        self::assertEquals('', $user->getPassword());
        self::assertEquals('test@example.com', $user->getEmail());
        self::assertEquals('Mr. T', $user->getUsername());
        self::assertEquals('A static super title', $user->getTitle());
        self::assertEquals(['ROLE_USER'], $user->getRoles());
    }
}
