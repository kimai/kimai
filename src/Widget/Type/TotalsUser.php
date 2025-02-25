<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;
use App\Widget\WidgetInterface;

final class TotalsUser extends AbstractWidget
{
    public function __construct(private readonly UserRepository $repository)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'admin_user',
            'icon' => 'user',
            'color' => WidgetInterface::COLOR_TOTAL,
        ], parent::getOptions($options));
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): int
    {
        $user = $this->getUser();
        $query = new UserQuery();
        $query->setCurrentUser($user);

        return $this->repository->countUsersForQuery($query);
    }

    public function getTitle(): string
    {
        return 'stats.userTotal';
    }

    public function getPermissions(): array
    {
        return ['view_user'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }

    public function getId(): string
    {
        return 'TotalsUser';
    }
}
