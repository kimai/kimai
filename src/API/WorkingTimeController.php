<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Model\WorkingTimeYear;
use App\Entity\User;
use App\WorkingTime\WorkingTimeService;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/working-times')]
#[IsGranted('API')]
#[OA\Tag(name: 'WorkingTime')]
final class WorkingTimeController extends BaseApiController
{
    public function __construct(
        private readonly ViewHandlerInterface $viewHandler,
        private readonly WorkingTimeService $workingTimeService,
    ) {
    }

    /**
     * Fetch the working times of a user for one year
     */
    #[IsGranted('hours', 'profile')]
    #[OA\Response(response: 200, description: 'Returns the expected and actual working times of the year, per month, as shown on the "working times" page.', content: new OA\JsonContent(ref: new Model(type: WorkingTimeYear::class)))]
    #[OA\Parameter(name: 'id', in: 'path', description: 'User ID', required: true)]
    #[OA\Parameter(name: 'year', in: 'path', description: 'Year, eg. 2026', required: true)]
    #[OA\Parameter(name: 'until', in: 'query', description: 'Count expected times up to (and including) this day, format: YYYY-MM-DD (default: today)', required: false)]
    #[Route(methods: ['GET'], path: '/{id}/{year}', name: 'get_working_times_year', requirements: ['id' => '\d+', 'year' => '\d{4}'])]
    public function getYear(User $profile, int $year, Request $request): Response
    {
        $dateTimeFactory = $this->getDateTimeFactory($profile);

        $until = $dateTimeFactory->createDateTime();
        $untilParam = $request->query->get('until');
        if (\is_string($untilParam) && $untilParam !== '') {
            $until = $dateTimeFactory->createDateTimeFromFormat('!Y-m-d', $untilParam);
            if ($until === false || $until->format('Y-m-d') !== $untilParam) {
                throw new BadRequestHttpException('Invalid "until" date, expected format: YYYY-MM-DD');
            }
            $until = $until->setTime(23, 59, 59);
        }

        $yearDate = $dateTimeFactory->createStartOfYear($dateTimeFactory->createDateTime($year . '-01-01'));
        $workingTimes = $this->workingTimeService->getYear($profile, $yearDate, $until);
        $summary = $this->workingTimeService->getYearSummary($workingTimes, $until);

        return $this->viewHandler->handle(new View(new WorkingTimeYear($workingTimes, $summary, $until), Response::HTTP_OK));
    }
}
