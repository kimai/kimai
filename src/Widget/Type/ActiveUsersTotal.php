<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Widget\Type;

use App\Repository\TimesheetRepository;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;

final class ActiveUsersTotal extends AbstractActiveUsers
{
    public function __construct(private TimesheetRepository $repository)
    {
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     @return array<string, string|bool|int|null|array<string, mixed>>
     */
    public function getOptions(array $options = []): array
    {
        return array_merge(['color' => WidgetInterface::COLOR_TOTAL], parent::getOptions($options));
    }

    public function getId(): string
    {
        return 'activeUsersTotal';
    }

    /**
     * @param array<string, string|bool|int|null|array<string, mixed>> $options
     */
    public function getData(array $options = []): mixed
    {
        try {
            return $this->repository->countActiveUsers(null, null, null);
        } catch (\Exception $ex) {
            throw new WidgetException(
                'Failed loading widget data: ' . $ex->getMessage()
            );
        }
    }
}
