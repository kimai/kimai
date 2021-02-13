<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Repository\TimesheetRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @RouteResource("Report")
 * @SWG\Tag(name="Report")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class ReportController extends BaseApiController
{
    /**
     * @var TimesheetRepository
     */
    private $timesheetRepository;
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;

    public function __construct(ViewHandlerInterface $viewHandler, TimesheetRepository $timesheetRepository)
    {
        $this->timesheetRepository = $timesheetRepository;
        $this->viewHandler = $viewHandler;
    }

    /**
     * Returns stats.
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns user stats",
     *      @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref="#/definitions/ActivityCollection")
     *      )
     * )
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function userAction(ParamFetcherInterface $paramFetcher): Response
    {
        $currentUser = $this->getUser();

        $data = $this->timesheetRepository->getUserStatistics($currentUser);
        $view = new View($data, 200);

        return $this->viewHandler->handle($view);
    }
}
