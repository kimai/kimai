<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Model\TimesheetConfig;
use App\Configuration\SystemConfiguration;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(name="Default")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
final class ConfigurationController extends BaseApiController
{
    public function __construct(private ViewHandlerInterface $viewHandler)
    {
    }

    /**
     * Returns the timesheet configuration
     *
     * @OA\Response(
     *      response=200,
     *      description="Returns the instance specific timesheet configuration",
     *      @OA\JsonContent(ref=@Model(type=TimesheetConfig::class))
     * )
     *
     * @Rest\Get(path="/config/timesheet")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function timesheetConfigAction(SystemConfiguration $configuration): Response
    {
        $model = new TimesheetConfig();
        $model->setTrackingMode($configuration->getTimesheetTrackingMode());
        $model->setDefaultBeginTime($configuration->getTimesheetDefaultBeginTime());
        $model->setActiveEntriesHardLimit($configuration->getTimesheetActiveEntriesHardLimit());
        $model->setIsAllowFutureTimes($configuration->isTimesheetAllowFutureTimes());
        $model->setIsAllowOverlapping($configuration->isTimesheetAllowOverlappingRecords());

        $view = new View($model, 200);
        $view->getContext()->setGroups(['Default', 'Config']);

        return $this->viewHandler->handle($view);
    }
}
