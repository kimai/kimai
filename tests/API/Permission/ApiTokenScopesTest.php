<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API\Permission;

use App\API\Permission\ApiTokenScopes;
use App\Entity\AccessToken;
use App\Entity\User;
use App\Event\ApiTokenScopePermissionsEvent;
use App\EventSubscriber\ApiTokenScopePermissionSubscriber;
use App\Repository\RolePermissionRepository;
use App\Security\RolePermissionManager;
use App\User\PermissionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;

#[CoversClass(ApiTokenScopes::class)]
class ApiTokenScopesTest extends TestCase
{
    /**
     * @param array<string> $allowedPermissions granted to ROLE_USER
     */
    private function createSut(array $allowedPermissions = []): ApiTokenScopes
    {
        $data = [];
        foreach ($allowedPermissions as $permission) {
            $data[] = ['permission' => $permission, 'role' => 'ROLE_USER', 'allowed' => true];
        }

        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn($data);
        /** @var RolePermissionRepository $repository */
        $service = new PermissionService($repository, new ArrayAdapter());

        // register the core scope -> permission mapping via the real subscriber
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ApiTokenScopePermissionSubscriber());

        return new ApiTokenScopes(new RolePermissionManager($service, [], []), $dispatcher);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUserIdentifier('john');

        return $user;
    }

    public function testCreateScope(): void
    {
        self::assertSame('timesheet:read', ApiTokenScopes::createScope('timesheet', 'read'));
    }

    public function testUserMayUseScope(): void
    {
        $sut = $this->createSut(['view_customer']);
        $user = $this->createUser();

        // user holds one of the mapped permissions
        self::assertTrue($sut->userMayUseScope($user, 'customer', 'read'));
        // user holds none of the mapped permissions
        self::assertFalse($sut->userMayUseScope($user, 'customer', 'delete'));
        // unknown resource (e.g. plugin) is not restricted here
        self::assertTrue($sut->userMayUseScope($user, 'expense', 'read'));
    }

    public function testPluginCanContributeMappingViaEvent(): void
    {
        // the user holds none of the mapped permissions
        $repository = $this->getMockBuilder(RolePermissionRepository::class)->onlyMethods(['getAllAsArray'])->disableOriginalConstructor()->getMock();
        $repository->method('getAllAsArray')->willReturn([]);
        /** @var RolePermissionRepository $repository */
        $service = new PermissionService($repository, new ArrayAdapter());

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new ApiTokenScopePermissionSubscriber());
        // a plugin contributes the mapping for its own resource
        $dispatcher->addListener(ApiTokenScopePermissionsEvent::class, static function (ApiTokenScopePermissionsEvent $event): void {
            $event->addPermission('expense', ApiTokenScopes::ACTION_UPDATE, ['edit_expense']);
        });

        $sut = new ApiTokenScopes(new RolePermissionManager($service, [], []), $dispatcher);
        $user = $this->createUser();

        self::assertTrue($sut->isMappedResource('expense'));
        // plugin-mapped scope the user is not allowed to use -> false (not the "unknown -> true" fallback)
        self::assertFalse($sut->userMayUseScope($user, 'expense', ApiTokenScopes::ACTION_UPDATE));
        // an action the plugin did not map stays unknown -> not restricted here
        self::assertTrue($sut->userMayUseScope($user, 'expense', ApiTokenScopes::ACTION_DELETE));
    }

    public function testEffectiveMatrixForLegacyTokenIsAllTrue(): void
    {
        $sut = $this->createSut([]);
        $catalog = ['customer' => ['read', 'create'], 'timesheet' => ['read']];

        // legacy token (null scopes) -> everything true, regardless of user permissions
        $matrix = $sut->getEffectiveMatrix(null, new User(), $catalog);

        self::assertSame([
            'customer' => ['read' => true, 'create' => true],
            'timesheet' => ['read' => true],
        ], $matrix);
    }

    public function testEffectiveMatrixForScopedToken(): void
    {
        $sut = $this->createSut(['view_customer', 'create_customer']);
        $token = new AccessToken(new User(), 'foo');
        $token->setScopes(['customer:read', 'customer:delete']);

        $catalog = ['customer' => ['read', 'create', 'delete']];

        $matrix = $sut->getEffectiveMatrix($token, $this->createUser(), $catalog);

        self::assertSame([
            'customer' => [
                // in token AND user has view_customer
                'read' => true,
                // user has create_customer but scope not granted to token
                'create' => false,
                // scope granted to token but user lacks delete_customer
                'delete' => false,
            ],
        ], $matrix);
    }
}
