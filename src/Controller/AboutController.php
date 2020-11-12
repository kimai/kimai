<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Constants;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/about")
 */
class AboutController extends AbstractController
{
    /**
     * @var string
     */
    protected $projectDirectory;

    /**
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @Route(path="", name="about", methods={"GET"})
     */
    public function license(): Response
    {
        $filename = $this->projectDirectory . '/LICENSE';

        try {
            $license = file_get_contents($filename);
        } catch (\Exception $ex) {
            $this->logException($ex);
            $license = false;
        }

        if (false === $license) {
            $license = 'Failed reading license file: ' . $filename . '. ' .
                'Check this instead: ' . Constants::GITHUB . 'blob/master/LICENSE';
        }

        return $this->render('about/license.html.twig', [
            'license' => $license
        ]);
    }
}
