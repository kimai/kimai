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
use App\Tests\Mocks\UserUpdateCounterSubscriberMock;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\EventDispatcher\EventDispatcher;

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

    public function testPasswordWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->assertAccessIsGranted($client, '/wizard/password');
    }

    public function testFinishWizard(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        // mark all wizards as seen so the WizardSubscriber does not interfere
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setWizardAsSeen('intro');
        $user->setWizardAsSeen('profile');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->assertAccessIsGranted($client, '/wizard/finish');
    }

    public function testWizardDoesNotAppearOnFirstLoginIfDisabled(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->setSystemConfiguration('user.wizard', false);

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

    public function testProfileWizardSubmitMarksSeenAndRedirectsToNext(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setPreferenceValue('__wizards__', null);
        $user->setWizardAsSeen('intro');
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

        // After a successful profile submit, the controller always redirects to
        // the virtual /wizard/next/ route — the WizardManager decides where
        // that ultimately lands. The route name and the _locale query string
        // make for a stable assertion target.
        $this->assertIsRedirect($client, '/wizard/next/', false);

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

    public function testPasswordWizardSubmitClearsResetFlagAndRedirectsToNext(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setRequiresPasswordReset(true);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $crawler = $this->request($client, '/wizard/password');
        $form = $crawler->filter('form[name=user_password]')->form();
        $values = $form->getPhpValues();
        $values['user_password']['plainPassword']['first'] = 'new-pa$$word-123';
        $values['user_password']['plainPassword']['second'] = 'new-pa$$word-123';
        $client->submit($form, $values);

        $this->assertIsRedirect($client, '/wizard/next/', false);

        $this->getEntityManager()->clear();
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        self::assertFalse($user->requiresPasswordReset());
    }

    public function testNextRedirectsToFirstUnseenStep(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setPreferenceValue('__wizards__', null);
        $user->setRequiresPasswordReset(false);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->request($client, '/wizard/next/');

        // The WizardSubscriber intercepts /wizard/next/ on kernel.request and
        // redirects to the first unseen step (intro, since we just cleared it).
        $this->assertIsRedirect($client, '/wizard/intro');
    }

    public function testNextRedirectsToFinishWhenAllStepsSeen(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setWizardAsSeen('intro');
        $user->setWizardAsSeen('profile');
        $user->setRequiresPasswordReset(false);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->request($client, '/wizard/next/');

        // With nothing left to see the subscriber returns early and the
        // controller falls back to the finish page.
        $this->assertIsRedirect($client, '/wizard/finish');
    }

    public function testPreviousRedirectsToPreviousStep(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/wizard/previous/profile');

        $this->assertIsRedirect($client, '/wizard/intro');
    }

    public function testPreviousFallsBackToIntroWhenNoPreviousStep(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        // intro is the very first step, so there is nothing before it
        $this->request($client, '/wizard/previous/intro');

        $this->assertIsRedirect($client, '/wizard/intro');
    }

    public function testPreviousFallsBackToIntroForUnknownStep(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/wizard/previous/does-not-exist');

        $this->assertIsRedirect($client, '/wizard/intro');
    }

    /**
     * The intro is a rendered page, so it stays a GET route, but it stores that the user has
     * seen it. That write may only ever happen once per account, otherwise every rendering of
     * this page would be a state change on a GET request - see GHSA-wv7c-q6q8-8rpw.
     */
    public function testIntroWizardIsPersistedOnlyOnce(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        // the counting subscriber has to survive the second request
        self::assertInstanceOf(KernelBrowser::class, $client);
        $client->disableReboot();

        $counter = new UserUpdateCounterSubscriberMock();
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber($counter);

        // the fixtures mark every wizard as seen, reset it to the state of a fresh account
        $user = $this->loadUserFromDatabase(UserFixtures::USERNAME_USER);
        $user->setPreferenceValue('__wizards__', null);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->getEntityManager()->clear();
        self::assertFalse($this->getUserByRole(User::ROLE_USER)->hasSeenWizard('intro'));

        $this->request($client, '/wizard/intro');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(1, $counter->getCount(), 'the first visit stores that the intro was seen');

        $this->getEntityManager()->clear();
        self::assertTrue($this->getUserByRole(User::ROLE_USER)->hasSeenWizard('intro'));

        $this->request($client, '/wizard/intro');
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(1, $counter->getCount(), 'every further visit must not save the user again');
    }
}
