<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Controller\ActivityController;
use App\Entity\User;
use App\Tests\DataFixtures\TimesheetFixtures;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @coversDefaultClass \App\Controller\ActivityController
 * @group integration
 */
class ActivityControllerTest extends ControllerBaseTest
{
    public function testRecentActivitiesAction()
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $container = self::$kernel->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');
        $user = $em->getRepository(User::class)->getById(1);

        $storage = new TokenStorage();
        $storage->setToken(new UsernamePasswordToken($user, [], 'foo'));
        $container->set('security.token_storage', $storage);


        $fixture = new TimesheetFixtures();
        $fixture->setUser($user);
        $fixture->setAmount(1);
        $fixture->setStartDate(new \DateTime('-30 days'));
        $this->importFixture($em, $fixture);

        $controller = $container->get(ActivityController::class);
        $controller->setContainer($container);
        $response = $controller->recentActivitiesAction();

        $content = $response->getContent();

        $this->assertTrue($response->isSuccessful());
        $this->assertContains('<li class="dropdown notifications-menu">', $content);
        $this->assertContains('<span class="label label-success">1</span>', $content);
        $this->assertContains('<a href="/en/timesheet/start/1">', $content);
    }
}
