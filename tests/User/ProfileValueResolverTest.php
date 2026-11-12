<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\User\ProfileValueResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[CoversClass(ProfileValueResolver::class)]
class ProfileValueResolverTest extends TestCase
{
    private function createArgument(string $type = User::class): ArgumentMetadata
    {
        return new ArgumentMetadata('profile', $type, false, false, null);
    }

    private function createRequest(mixed $username): Request
    {
        $request = new Request();
        $request->attributes->set('username', $username);

        return $request;
    }

    public function testIgnoresOtherArgumentTypes(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('loadUserByIdentifier');

        $sut = new ProfileValueResolver($repository);

        self::assertSame([], $sut->resolve($this->createRequest('foo'), $this->createArgument(Request::class)));
    }

    public function testResolvesExistingUser(): void
    {
        $user = new User();
        $user->setUserIdentifier('foo');

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('loadUserByIdentifier')->with('foo')->willReturn($user);

        $sut = new ProfileValueResolver($repository);

        self::assertSame([$user], $sut->resolve($this->createRequest('foo'), $this->createArgument()));
    }

    public function testUnknownUserIsDenied(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())->method('loadUserByIdentifier')->with('unknown')->willThrowException(new UserNotFoundException());

        $sut = new ProfileValueResolver($repository);

        $this->expectException(AccessDeniedException::class);
        $sut->resolve($this->createRequest('unknown'), $this->createArgument());
    }

    public function testMissingUsernameIsDenied(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->never())->method('loadUserByIdentifier');

        $sut = new ProfileValueResolver($repository);

        $this->expectException(AccessDeniedException::class);
        $sut->resolve($this->createRequest(null), $this->createArgument());
    }
}
