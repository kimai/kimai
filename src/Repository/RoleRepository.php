<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Configuration\ConfigLoaderInterface;
use App\Entity\Configuration;
use App\Form\Model\SystemConfiguration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query;

class RoleRepository extends EntityRepository
{
}
