<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * @coversDefaultClass \App\Controller\ProfileController
 * @group integration
 */
class ProfileControllerTest extends ControllerBaseTest
{
    public function testIsSecure()
    {
        $this->assertUrlIsSecured('/profile/' . UserFixtures::USERNAME_USER);
    }

    public function testIndexAction()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);
        $this->assertTrue($client->getResponse()->isSuccessful());

        $expectedTabs = ['#charts', '#settings', '#password', '#api-token', '#preferences'];

        $tabs = $client->getCrawler()->filter('div.nav-tabs-custom ul.nav-tabs li');
        $this->assertEquals(count($expectedTabs), $tabs->count());
        $foundTabs = [];
        foreach ($tabs->filter('a') as $tab) {
            $foundTabs[] = $tab->getAttribute('href');
        }
        $this->assertEmpty(array_diff($expectedTabs, $foundTabs));
    }

    public function testIndexActionWithDifferentUsername()
    {
        $client = $this->getClientForAuthenticatedUser();
        $this->request($client, '/profile/' . UserFixtures::USERNAME_TEAMLEAD);
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testEditAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/edit');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());
        $this->assertEquals('John Doe', $user->getAlias());
        $this->assertEquals('Developer', $user->getTitle());
        $this->assertEquals(UserFixtures::DEFAULT_AVATAR, $user->getAvatar());
        $this->assertEquals('john_user@example.com', $user->getEmail());
        $this->assertTrue($user->isEnabled());

        $form = $client->getCrawler()->filter('form[name=user_edit]')->form();
        $client->submit($form, [
            'user_edit' => [
                'alias' => 'Johnny',
                'title' => 'Code Monkey',
                'avatar' => '/fake/image.jpg',
                'email' => 'updated@example.com',
                'enabled' => 0,
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/edit'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());
        $this->assertEquals('Johnny', $user->getAlias());
        $this->assertEquals('Code Monkey', $user->getTitle());
        $this->assertEquals('/fake/image.jpg', $user->getAvatar());
        $this->assertEquals('updated@example.com', $user->getEmail());
        $this->assertFalse($user->isEnabled());
    }

    public function testPasswordAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/password');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        /** @var EncoderFactoryInterface $passwordEncoder */
        $passwordEncoder = $client->getContainer()->get('test.PasswordEncoder');

        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test123', $user->getSalt()));
        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());

        $form = $client->getCrawler()->filter('form[name=user_password]')->form();
        $client->submit($form, [
            'user_password' => [
                'plainPassword' => [
                    'first' => 'test123',
                    'second' => 'test123',
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/password'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test123', $user->getSalt()));
    }

    public function testApiTokenAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/api-token');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);
        /** @var EncoderFactoryInterface $passwordEncoder */
        $passwordEncoder = $client->getContainer()->get('test.PasswordEncoder');

        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN, $user->getSalt()));
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), 'test123', $user->getSalt()));
        $this->assertEquals(UserFixtures::USERNAME_USER, $user->getUsername());

        $form = $client->getCrawler()->filter('form[name=user_api_token]')->form();
        $client->submit($form, [
            'user_api_token' => [
                'plainApiToken' => [
                    'first' => 'test123',
                    'second' => 'test123',
                ]
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/api-token'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), 'test123', $user->getSalt()));
    }

    public function testRolesActionIsSecured()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');
        $this->assertFalse($client->getResponse()->isSuccessful());
    }

    public function testRolesAction()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER . '/roles');

        /** @var User $user */
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        $form = $client->getCrawler()->filter('form[name=user_roles]')->form();
        $client->submit($form, [
            'user_roles[roles]' => [
                'ROLE_TEAMLEAD',
                'ROLE_SUPER_ADMIN',
            ]
        ]);

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER) . '/roles'));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $this->getUserByRole($em, User::ROLE_USER);

        $this->assertEquals(['ROLE_TEAMLEAD', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
