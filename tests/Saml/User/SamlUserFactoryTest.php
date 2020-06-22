<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Saml\User;

use App\Entity\User;
use App\Saml\User\SamlUserFactory;
use Hslavich\OneloginSamlBundle\Security\Authentication\Token\SamlToken;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Saml\User\SamlUserFactory
 */
class SamlUserFactoryTest extends TestCase
{
    public function testCreateUserThrowsExceptionOnMissingAttribute()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing user attribute: title');

        $mapping = [
            'mapping' => [
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

    public function testCreateUserThrowsExceptionOnMissingAttributeInMultiple()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing user attribute: test');

        $mapping = [
            'mapping' => [
                ['saml' => '$Email $test', 'kimai' => 'email'],
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
                ['saml' => '$avatar', 'kimai' => 'avatar'],
                ['saml' => '$Email', 'kimai' => 'email'],
                ['saml' => 'A static super title', 'kimai' => 'title'],
                // double space between "$LastName  $FOOO" on purpose!!!
                ['saml' => '$FirstName $LastName  $FOOO me', 'kimai' => 'alias'],
            ],
            'roles' => [
                'attribute' => 'RoLeS',
                'mapping' => [
                    ['saml' => 'fooobar', 'kimai' => 'ROLE_ADMIN'],
                    ['saml' => 'ROLE_1', 'kimai' => 'ROLE_TEAMLEAD'],
                    ['saml' => 'ROLE_2', 'kimai' => 'ROLE_2'],
                ]
            ]
        ];

        $attributes = [
            'RoLeS' => ['ROLE_1', 'ROLE_2', 'ROLE_3'],
            'Email' => ['test@example.com'],
            'FOOO' => ['test', 'test2'],
            'FirstName' => ['Kevin'],
            'LastName' => ['Papst'],
            'avatar' => ['http://www.example.com/test.jpg'],
        ];

        $token = new SamlToken();
        $token->setUser('foo@example.com');
        $token->setAttributes($attributes);

        $sut = new SamlUserFactory($mapping);
        $user = $sut->createUser($token);

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isEnabled());
        self::assertEquals('', $user->getPassword());
        self::assertEquals('test@example.com', $user->getEmail());
        self::assertEquals('foo@example.com', $user->getUsername());
        self::assertEquals('A static super title', $user->getTitle());
        self::assertEquals('Kevin Papst test me', $user->getAlias());
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_2', 'ROLE_USER'], $user->getRoles());
    }

    public function testCreateUserDoesOverwriteUsername()
    {
        $mapping = [
            'mapping' => [
                ['saml' => '$avatar', 'kimai' => 'avatar'],
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
        $token->setUser('foo@example.com');
        $token->setAttributes($attributes);

        $sut = new SamlUserFactory($mapping);
        $user = $sut->createUser($token);

        self::assertInstanceOf(User::class, $user);
        self::assertTrue($user->isEnabled());
        self::assertEquals('', $user->getPassword());
        self::assertEquals('test@example.com', $user->getEmail());
        self::assertEquals('foo@example.com', $user->getUsername());
        self::assertEquals('A static super title', $user->getTitle());
        self::assertEquals(['ROLE_USER'], $user->getRoles());
    }
}
