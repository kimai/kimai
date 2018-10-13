<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Constants;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HealthcheckController extends Controller
{
    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     */
    public function __construct(ViewHandlerInterface $viewHandler)
    {
        $this->viewHandler = $viewHandler;
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="A simple route that returns a 'pong', which you can use for testing the API",
     * )
     *
     * @Rest\Get(path="/ping")
     */
    public function pingAction()
    {
        $view = new View(['message' => 'pong'], 200);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns version information about the current release",
     * )
     *
     * @Rest\Get(path="/version")
     */
    public function versionAction()
    {
        $version = [
            'version' => Constants::VERSION,
            'candidate' => Constants::STATUS,
            'semver' => Constants::VERSION . '-' . Constants::STATUS,
            'name' => Constants::NAME,
            'copyright' => 'Kimai 2 - ' . Constants::VERSION . ' ' . Constants::STATUS . ' (' . Constants::NAME . ') by Kevin Papst and contributors.',
        ];

        return $this->viewHandler->handle(new View($version, 200));
    }
}
