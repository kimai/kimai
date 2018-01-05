<?php

/*
 * This file is part of the Kimai package.
 *
 * (c) Kevin Papst <kevin@kevinpapst.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle;

use AppBundle\DependencyInjection\CompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Kimai main application bundle.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/best_practices.html
 * @see http://symfony.com/doc/current/best_practices/business-logic.html
 *
 * @author Kevin Papst <kevin@kevinpapst.de>
 */
class AppBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1000);
    }
}