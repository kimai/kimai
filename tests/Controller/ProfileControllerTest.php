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

    public function testUpdateApiToken()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
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

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER)));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), UserFixtures::DEFAULT_API_TOKEN, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getApiToken(), 'test123', $user->getSalt()));
    }

    public function testUpdatePassword()
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $this->request($client, '/profile/' . UserFixtures::USERNAME_USER);

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
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

        $this->assertIsRedirect($client, $this->createUrl('/profile/' . urlencode(UserFixtures::USERNAME_USER)));
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isSuccessful());

        $this->assertHasFlashSuccess($client);

        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $this->assertFalse($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), UserFixtures::DEFAULT_PASSWORD, $user->getSalt()));
        $this->assertTrue($passwordEncoder->getEncoder($user)->isPasswordValid($user->getPassword(), 'test123', $user->getSalt()));
    }
}
