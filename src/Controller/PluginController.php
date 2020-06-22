<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Plugin\PluginManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/plugins")
 * @Security("is_granted('plugins')")
 */
class PluginController extends AbstractController
{
    /**
     * @var PluginManager
     */
    protected $plugins;

    /**
     * @param PluginManager $manager
     */
    public function __construct(PluginManager $manager)
    {
        $this->plugins = $manager;
    }

    /**
     * @Route(path="/", name="plugins", methods={"GET"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $plugins = $this->plugins->getPlugins();
        foreach ($this->plugins->getPlugins() as $plugin) {
            $this->plugins->loadMetadata($plugin);
        }

        return $this->render('plugin/index.html.twig', [
            'plugins' => $plugins,
        ]);
    }
}
