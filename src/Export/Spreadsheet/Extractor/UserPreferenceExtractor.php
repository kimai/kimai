<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Export\Spreadsheet\Extractor;

use App\Entity\User;
use App\Event\UserPreferenceDisplayEvent;
use App\Export\Spreadsheet\ColumnDefinition;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class UserPreferenceExtractor implements ExtractorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param UserPreferenceDisplayEvent $value
     * @return ColumnDefinition[]
     * @throws ExtractorException
     */
    public function extract($value): array
    {
        if (!($value instanceof UserPreferenceDisplayEvent)) {
            throw new ExtractorException('UserPreferenceExtractor needs a UserPreferenceDisplayEvent instance for work');
        }

        $columns = [];

        $this->eventDispatcher->dispatch($value);

        foreach ($value->getPreferences() as $field) {
            if (!$field->isEnabled()) {
                continue;
            }

            $columns[] = new ColumnDefinition(
                $field->getLabel(),
                'string',
                function (User $user) use ($field) {
                    $meta = $user->getPreference($field->getName());
                    if (null === $meta) {
                        return null;
                    }

                    return $meta->getValue();
                }
            );
        }

        return $columns;
    }
}
