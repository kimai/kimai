<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

/**
 * Adds a prefix to every doctrine entity AKA database table
 */
class TablePrefixSubscriber implements EventSubscriber
{
    public const PREFIX = 'kimai2_';

    /**
     * @return array|string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    /**
     * @param LoadClassMetadataEventArgs $args
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $classMetadata = $args->getClassMetadata();
        if ($classMetadata->isInheritanceTypeSingleTable() && !$classMetadata->isRootEntity()) {
            // if we are in an inheritance hierarchy, only apply this once
            return;
        }

        $classMetadata->setPrimaryTable(['name' => self::PREFIX . $classMetadata->getTableName()]);

        foreach ($classMetadata->getAssociationMappings() as $fieldName => $mapping) {
            if (\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY == $mapping['type']
                // Check if "joinTable" exists:
                // it can be null if this field is the reverse side of a ManyToMany relationship
                && array_key_exists('name', $classMetadata->associationMappings[$fieldName]['joinTable'])) {
                $mappedTableName = $classMetadata->associationMappings[$fieldName]['joinTable']['name'];
                $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = self::PREFIX . $mappedTableName;
            }
        }
    }
}
