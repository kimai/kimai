<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;

final class TotalsActivity extends SimpleWidget implements UserWidget, AuthorizedWidget
{
    use UserWidgetTrait;

    private $activity;

    public function __construct(ActivityRepository $activity)
    {
        $this->activity = $activity;
        $this->setTitle('stats.activityTotal');
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'admin_activity',
            'icon' => 'activity',
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

        $query = new ActivityQuery();
        $query->setCurrentUser($user);

        return $this->activity->countActivitiesForQuery($query);
    }

    /**
     * @return string[]
     */
    public function getPermissions(): array
    {
        return ['view_activity', 'view_teamlead_activity', 'view_team_activity'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }
}
