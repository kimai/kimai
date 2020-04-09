<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Model\Version;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SWG\Tag(name="Default")
 */
class StatusController extends BaseApiController
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
     * A testing route for the API
     *
     * @SWG\Response(
     *     response=200,
     *     description="A simple route that returns a 'pong', which you can use for testing the API",
     *     examples={"{'message': 'pong'}"}
     * )
     *
     * @Rest\Get(path="/ping")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function pingAction(): Response
    {
        $view = new View(['message' => 'pong'], 200);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns information about the Kimai release
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns version information about the current release",
     *      @SWG\Schema(ref=@Model(type=Version::class))
     * )
     *
     * @Rest\Get(path="/version")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function versionAction(): Response
    {
        return $this->viewHandler->handle(new View(new Version(), 200));
    }
}
