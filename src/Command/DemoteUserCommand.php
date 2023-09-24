<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\User;
use App\User\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'kimai:user:demote')]
final class DemoteUserCommand extends AbstractRoleCommand
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Demote a user by removing a role')
            ->setHelp(
                <<<'EOT'
                    The <info>kimai:user:demote</info> command demotes a user by removing a role

                      <info>php %command.full_name% susan_super ROLE_TEAMLEAD</info>
                      <info>php %command.full_name% --super susan_super</info>
                    EOT
            );
    }

    protected function executeRoleCommand(UserService $manipulator, SymfonyStyle $output, User $user, bool $super, $role): void
    {
        $username = $user->getUserIdentifier();
        if ($super) {
            if ($user->isSuperAdmin()) {
                $user->setSuperAdmin(false);
                $manipulator->updateUser($user);
                $output->success(sprintf('Super administrator role has been removed from the user "%s".', $username));
            } else {
                $output->warning(sprintf('User "%s" doesn\'t have the super administrator role.', $username));
            }
        } else {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
                $manipulator->updateUser($user);
                $output->success(sprintf('Role "%s" has been removed from user "%s".', $role, $username));
            } else {
                $output->warning(sprintf('User "%s" didn\'t have "%s" role.', $username, $role));
            }
        }
    }
}
