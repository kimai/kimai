<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class DoctorControllerTest extends AbstractControllerBaseTestCase
{
    public function testDoctorIsSecure(): void
    {
        $this->assertUrlIsSecured('/doctor');
    }

    public function testDoctorIsSecureForRole(): void
    {
        $this->assertUrlIsSecuredForRole(User::ROLE_ADMIN, '/doctor');
    }

    public function testIndexAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/doctor');

        $result = $client->getCrawler()->filter('.content .accordion-header');
        $counter = \count($result);
        // this can contain a warning box, that a new release is available
        self::assertTrue($counter === 6 || $counter === 5);
    }

    public function testFlushLogIsNotPossibleWithGet(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/doctor/flush-log');

        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testFlushLogWithInvalidCsrf(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);

        $this->request($client, '/doctor/flush-log', 'POST', ['form' => ['_token' => 'rsetdzfukgli78t6r5uedtjfzkugl']]);

        $this->assertIsRedirect($client);
        $this->assertRedirectUrl($client, $this->createUrl('/doctor'));
        $client->followRedirect();
        $this->assertHasFlashError($client, 'The action could not be performed: invalid security token.');
    }

    public function testFlushLog(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertAccessIsGranted($client, '/doctor');

        $form = $client->getCrawler()->filter('form[action$="/doctor/flush-log"]')->form();
        self::assertEquals('POST', $form->getMethod());

        $client->submit($form);

        $this->assertIsRedirect($client);
        $this->assertRedirectUrl($client, $this->createUrl('/doctor'));
    }
}
