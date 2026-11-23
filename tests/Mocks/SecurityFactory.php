<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Mocks;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class SecurityFactory extends AbstractMockFactory
{
    public function create(): Security
    {
        $interface = $this->createMock(TokenInterface::class);
        $interface->method('getUser')->willReturn(new User());
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($interface);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn($storage);

        return new Security($container);
    }
}
