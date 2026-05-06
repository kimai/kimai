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
use App\Entity\UserPreference;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class WizardControllerTest extends AbstractControllerBaseTestCase
{
    public function testUnknownWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/wizard/foo');
        $this->assertRouteNotFound($client);
    }

    public function testIntroWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/intro');
    }

    public function testProfileWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/profile');
    }

    public function testWizardDoesNotAppearOnFirstLoginIfDisabled(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->setSystemConfiguration('user.wizard', '0');

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setPreferenceValue('__wizards__', null);
        $user->setRequiresPasswordReset(false);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->request($client, '/timesheet/');

        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertFalse($client->getResponse()->isRedirect());
    }

    public function testWizardAppearsOnFirstLoginWithDefaultConfiguration(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setPreferenceValue('__wizards__', null);
        $user->setRequiresPasswordReset(false);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->request($client, '/timesheet/');

        $this->assertIsRedirect($client, '/wizard/intro');
    }

    public function testProfileWizardSubmitRedirectsToDoneAndMarksSeen(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setPreferenceValue('__wizards__', null);
        $user->setRequiresPasswordReset(false);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $crawler = $this->request($client, '/wizard/profile');
        $form = $crawler->filter('form[name=form]')->form();
        $values = $form->getPhpValues();
        $values['form']['reload'] = '0';
        $values['form'][UserPreference::LANGUAGE] = 'en';
        $values['form'][UserPreference::LOCALE] = 'en';
        $values['form'][UserPreference::TIMEZONE] = 'Europe/Berlin';
        $values['form'][UserPreference::SKIN] = 'auto';
        $client->submit($form, $values);

        $this->assertIsRedirect($client, '/wizard/done');

        $this->getEntityManager()->clear();
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        self::assertTrue($user->hasSeenWizard('profile'));
    }

    public function testProfileWizardSubmitReloadsProfileWhenRequested(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $crawler = $this->request($client, '/wizard/profile');
        $form = $crawler->filter('form[name=form]')->form();
        $values = $form->getPhpValues();
        $values['form']['reload'] = '1';
        $client->submit($form, $values);

        $this->assertIsRedirect($client, '/wizard/profile');
    }

    public function testProfileWizardSubmitRedirectsToPasswordIfResetRequired(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setRequiresPasswordReset(true);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $crawler = $this->request($client, '/wizard/profile');
        $form = $crawler->filter('form[name=form]')->form();
        $values = $form->getPhpValues();
        $values['form']['reload'] = '0';
        $client->submit($form, $values);

        $this->assertIsRedirect($client, '/wizard/password');
    }

    public function testDoneWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/done');
    }
}
