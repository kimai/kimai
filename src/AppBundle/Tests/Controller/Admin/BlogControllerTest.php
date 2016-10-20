<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Tests\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * FIXME CAN BE REMOVED
 *
 * Functional test for the controllers defined inside the BlogController used
 * for managing the blog in the backend.
 * See http://symfony.com/doc/current/book/testing.html#functional-tests
 *
 * Whenever you test resources protected by a firewall, consider using the
 * technique explained in:
 * http://symfony.com/doc/current/cookbook/testing/http_authentication.html
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ phpunit -c app
 *
 */
class BlogControllerTest extends WebTestCase
{
    public function testRegularUsersCannotAccessToTheBackend()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW'   => 'kitten',
        ]);

        $client->request('GET', '/en/admin/post/');

        $this->assertEquals(Response::HTTP_FORBIDDEN, $client->getResponse()->getStatusCode());
    }

    public function testAdministratorUsersCanAccessToTheBackend()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'anna_admin',
            'PHP_AUTH_PW'   => 'kitten',
        ]);

        $client->request('GET', '/en/admin/post/');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    public function testIndex()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'anna_admin',
            'PHP_AUTH_PW'   => 'kitten',
        ]);

        $crawler = $client->request('GET', '/en/admin/post/');

        $this->assertCount(
            30,
            $crawler->filter('body#admin_post_index #main tbody tr'),
            'The backend homepage displays all the available posts.'
        );
    }
}
