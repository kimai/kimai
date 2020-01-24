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
    public function testConstructThrowsExceptionOnMissingEmailMapping()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Your SAML mapping is missing an attribute for the users email');

        $mapping = [
            'mapping' => [],
            'groups' => [
                'attribute' => '',
                'mapping' => []
            ]
        ];

        $sut = new SamlUserFactory($mapping);
    }

    public function testCreateUserThrowsExceptionOnMissingAttribute()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing user attribute: title');

        $mapping = [
            'mapping' => [
                'avatar' => '$$avatar',
                'email' => '$Email',
                'title' => '$title',
            ],
            'groups' => [
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
                'email' => '$Email',
                'title' => 'A static super title',
                'avatar' => '$$avatar',
            ],
            'groups' => [
                'attribute' => 'RoLeS',
                'mapping' => [
                    'fooobar' => 'ROLE_ADMIN',
                    'ROLE_1' => 'ROLE_TEAMLEAD',
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
        self::assertEquals('test@example.com', $user->getEmail());
        self::assertEquals('test@example.com', $user->getUsername());
        self::assertEquals('A static super title', $user->getTitle());
        self::assertEquals(['ROLE_TEAMLEAD', 'ROLE_2', 'ROLE_USER'], $user->getRoles());
    }
}
