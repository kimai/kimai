<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\License\LicenseKeyInterface;
use App\Plugin\PluginManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/plugins")
 * @Security("is_granted('ROLE_SUPER_ADMIN')")
 */
class PluginController extends AbstractController
{
    /**
     * @var PluginManager
     */
    protected $plugins;
    /**
     * @var LicenseKeyInterface
     */
    protected $licenseKey;

    /**
     * @param PluginManager $manager
     * @param LicenseKeyInterface $license
     */
    public function __construct(PluginManager $manager, LicenseKeyInterface $license)
    {
        $this->plugins = $manager;
        $this->licenseKey = $license;
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
            'publicKey' => $this->licenseKey->getPublicKey(),
            'plugins' => $plugins,
        ]);
    }
}
