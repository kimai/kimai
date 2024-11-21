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
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('API')]
#[OA\Tag(name: 'Default')]
final class ConfigurationController extends BaseApiController
{
    public function __construct(private readonly ViewHandlerInterface $viewHandler)
    {
    }

    /**
     * Returns the timesheet configuration
     */
    #[OA\Response(response: 200, description: 'Returns the instance specific timesheet configuration', content: new OA\JsonContent(ref: new Model(type: TimesheetConfig::class)))]
    #[Route(path: '/config/timesheet', methods: ['GET'])]
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

    /**
     * Returns the configured color codes and names
     */
    #[OA\Response(response: 200, description: 'Returns the configured color codes and names', content: new OA\JsonContent(type: 'object', example: ['Red' => '#ff0000'], additionalProperties: new OA\AdditionalProperties(type: 'string')))]
    #[Route(path: '/config/colors', methods: ['GET'])]
    public function colorConfigAction(SystemConfiguration $configuration): Response
    {
        $view = new View($configuration->getThemeColors(), 200);
        $view->getContext()->setGroups(['Default']);

        return $this->viewHandler->handle($view);
    }
}
