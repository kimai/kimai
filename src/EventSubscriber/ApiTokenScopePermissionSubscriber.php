<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventSubscriber;

use App\API\Permission\ApiTokenScopes;
use App\Event\ApiTokenScopePermissionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Registers the scope -> role permission mapping for the core API resources.
 *
 * Plugins add the mapping for their own resources by subscribing to the same
 * {@see ApiTokenScopePermissionsEvent}.
 *
 * This is a best-effort mapping on the role-permission level: some API endpoints
 * are guarded by subject voters (e.g. IsGranted('edit','customer')) which cannot
 * be evaluated without a concrete subject, so we map to the role permissions
 * those voters consult.
 */
final class ApiTokenScopePermissionSubscriber implements EventSubscriberInterface
{
    /**
     * "<resource>" => [ "<action>" => [ role permission, ... ] ]
     *
     * @var array<string, array<string, array<string>>>
     */
    private const PERMISSIONS = [
        'customer' => [
            ApiTokenScopes::ACTION_READ => ['view_customer', 'view_teamlead_customer', 'view_team_customer'],
            ApiTokenScopes::ACTION_CREATE => ['create_customer'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_customer', 'edit_teamlead_customer', 'edit_team_customer'],
            ApiTokenScopes::ACTION_DELETE => ['delete_customer'],
        ],
        'project' => [
            ApiTokenScopes::ACTION_READ => ['view_project', 'view_teamlead_project', 'view_team_project'],
            ApiTokenScopes::ACTION_CREATE => ['create_project'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_project', 'edit_teamlead_project', 'edit_team_project'],
            ApiTokenScopes::ACTION_DELETE => ['delete_project'],
        ],
        'activity' => [
            ApiTokenScopes::ACTION_READ => ['view_activity', 'view_teamlead_activity', 'view_team_activity'],
            ApiTokenScopes::ACTION_CREATE => ['create_activity'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_activity', 'edit_teamlead_activity', 'edit_team_activity'],
            ApiTokenScopes::ACTION_DELETE => ['delete_activity'],
        ],
        'timesheet' => [
            ApiTokenScopes::ACTION_READ => ['view_own_timesheet', 'view_other_timesheet'],
            ApiTokenScopes::ACTION_CREATE => ['create_own_timesheet', 'create_other_timesheet'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_own_timesheet', 'edit_other_timesheet'],
            ApiTokenScopes::ACTION_DELETE => ['delete_own_timesheet', 'delete_other_timesheet'],
        ],
        'team' => [
            ApiTokenScopes::ACTION_READ => ['view_team'],
            ApiTokenScopes::ACTION_CREATE => ['create_team'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_team'],
            ApiTokenScopes::ACTION_DELETE => ['delete_team'],
        ],
        'tag' => [
            ApiTokenScopes::ACTION_READ => ['view_tag'],
            ApiTokenScopes::ACTION_CREATE => ['create_tag'],
            ApiTokenScopes::ACTION_UPDATE => ['manage_tag'],
            ApiTokenScopes::ACTION_DELETE => ['delete_tag'],
        ],
        'user' => [
            ApiTokenScopes::ACTION_READ => ['view_user'],
            ApiTokenScopes::ACTION_CREATE => ['create_user'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_own_profile', 'edit_other_profile'],
            ApiTokenScopes::ACTION_DELETE => ['delete_user'],
        ],
        'invoice' => [
            ApiTokenScopes::ACTION_READ => ['view_invoice'],
            ApiTokenScopes::ACTION_CREATE => ['create_invoice'],
            ApiTokenScopes::ACTION_UPDATE => ['view_invoice'],
            ApiTokenScopes::ACTION_DELETE => ['delete_invoice'],
        ],
        'export' => [
            ApiTokenScopes::ACTION_READ => ['create_export'],
            ApiTokenScopes::ACTION_CREATE => ['create_export'],
            ApiTokenScopes::ACTION_UPDATE => ['edit_export_own_timesheet', 'edit_export_other_timesheet'],
            ApiTokenScopes::ACTION_DELETE => ['create_export_template'],
        ],
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            ApiTokenScopePermissionsEvent::class => ['onCollectPermissions', 1000],
        ];
    }

    public function onCollectPermissions(ApiTokenScopePermissionsEvent $event): void
    {
        foreach (self::PERMISSIONS as $resource => $actions) {
            foreach ($actions as $action => $permissions) {
                $event->addPermission($resource, $action, $permissions);
            }
        }
    }
}
