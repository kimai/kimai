<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Repository\Query\UserQuery;
use App\Repository\UserRepository;

final class TotalsUser extends SimpleWidget implements UserWidget, AuthorizedWidget
{
    use UserWidgetTrait;

    private $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
        $this->setTitle('stats.userTotal');
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'admin_user',
            'icon' => 'user',
            'color' => 'primary',
            'dataType' => 'int',
        ], parent::getOptions($options));
    }

    public function getData(array $options = [])
    {
        $options = $this->getOptions($options);

        $user = $options['user'];
        if (null === $user || !($user instanceof User)) {
            throw new \InvalidArgumentException('Widget option "user" must be an instance of ' . User::class);
        }

        $query = new UserQuery();
        $query->setCurrentUser($user);

        return $this->user->countUsersForQuery($query);
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return ['view_user'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }
}
