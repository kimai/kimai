<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * ControllerBaseTest adds some useful functions for writing integration tests.
 */
class ControllerBaseTest extends WebTestCase
{

    const DEFAULT_LANGUAGE = 'en';

    /**
     * @return Client
     */
    protected function getClientForAuthenticatedUser()
    {
        $client = self::createClient([], [
            'PHP_AUTH_USER' => AppFixtures::USERNAME_USER,
            'PHP_AUTH_PW' => AppFixtures::DEFAULT_PASSWORD,
        ]);

        return $client;
    }

    /**
     * @param Client $client
     * @param string $url
     * @param string $method
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function request(Client $client, string $url, $method = 'GET')
    {
        return $client->request($method, '/' . self::DEFAULT_LANGUAGE . $url);
    }

    /**
     * @param string $url
     * @param string $method
     */
    protected function assertUrlIsSecured(string $url, $method = 'GET')
    {
        $client = self::createClient();
        $client->request($method, '/' . self::DEFAULT_LANGUAGE . $url);

        $this->assertTrue($client->getResponse()->isRedirect());

        $this->assertEquals(
            'http://localhost/' . self::DEFAULT_LANGUAGE . '/login',
            $client->getResponse()->getTargetUrl(),
            sprintf('The %s secure URL redirects to the login form.', $url)
        );
    }
}
