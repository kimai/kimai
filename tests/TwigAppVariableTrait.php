<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use App\Entity\User;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

/**
 * Prepares the "app" variable for tests that render Twig templates in invoice/export scenarios.
 * Needs a setup that is as close to "production" to possible, so the StrictPolicy can actually
 * catch issues, instead of stopping at a null value.
 *
 * For example {{ app.user.password }} in a test context would stop at "app.user" being null,
 * instead of checking if the getPassword() method is allowed.
 */
trait TwigAppVariableTrait
{
    protected function prepareTwigAppVariable(string $locale = 'en', ?UserInterface $user = null): Request
    {
        if (!$this instanceof KernelTestCase) {
            throw new \Exception('TwigAppVariableTrait can only be used in a KernelTestCase');
        }

        /** @var RequestStack $stack */
        $stack = self::getContainer()->get('request_stack');

        $request = new Request();
        $request->setLocale($locale);
        $stack->push($request);

        return $request;
    }

    private function createTwigAppUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getName')->willReturn('Testing');
        $user->method('isAdmin')->willReturn(false);
        $user->method('isSuperAdmin')->willReturn(false);
        $user->method('getTimezone')->willReturn('America/Edmonton');

        return $user;
    }
}
