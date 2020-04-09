<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\API\Model\I18n;
use App\API\Model\TimesheetConfig;
use App\Configuration\LanguageFormattings;
use App\Configuration\TimesheetConfiguration;
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation\Security as ApiSecurity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SWG\Tag(name="Default")
 *
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
final class ConfigurationController extends BaseApiController
{
    /**
     * @var ViewHandlerInterface
     */
    private $viewHandler;
    /**
     * @var LanguageFormattings
     */
    private $formats;
    /**
     * @var TimesheetConfiguration
     */
    private $timesheetConfiguration;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param LanguageFormattings $formats
     * @param TimesheetConfiguration $timesheetConfiguration
     */
    public function __construct(ViewHandlerInterface $viewHandler, LanguageFormattings $formats, TimesheetConfiguration $timesheetConfiguration)
    {
        $this->viewHandler = $viewHandler;
        $this->formats = $formats;
        $this->timesheetConfiguration = $timesheetConfiguration;
    }

    /**
     * Returns the user specific locale configuration
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the locale specific configurations for this user",
     *      @SWG\Schema(ref="#/definitions/I18nConfig")
     * )
     *
     * @Rest\Get(path="/config/i18n")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function i18nAction(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $locale = $user->getLocale();

        $model = new I18n();
        $model
            ->setFormDateTime($this->formats->getDateTimeTypeFormat($locale))
            ->setFormDate($this->formats->getDateTypeFormat($locale))
            ->setDateTime($this->formats->getDateTimeFormat($locale))
            ->setDate($this->formats->getDateFormat($locale))
            ->setDuration($this->formats->getDurationFormat($locale))
            ->setTime($this->formats->getTimeFormat($locale))
            ->setIs24hours($this->formats->isTwentyFourHours($locale))
        ;

        $view = new View($model, 200);
        $view->getContext()->setGroups(['Default', 'Config']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns the instance specific timesheet configuration
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the instance specific timesheet configuration",
     *      @SWG\Schema(ref="#/definitions/TimesheetConfig")
     * )
     *
     * @Rest\Get(path="/config/timesheet")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function timesheetConfigAction(): Response
    {
        $model = new TimesheetConfig();
        $model
            ->setTrackingMode($this->timesheetConfiguration->getTrackingMode())
            ->setDefaultBeginTime($this->timesheetConfiguration->getDefaultBeginTime())
            ->setActiveEntriesHardLimit($this->timesheetConfiguration->getActiveEntriesHardLimit())
            ->setActiveEntriesSoftLimit($this->timesheetConfiguration->getActiveEntriesSoftLimit())
            ->setIsAllowFutureTimes($this->timesheetConfiguration->isAllowFutureTimes())
        ;

        $view = new View($model, 200);
        $view->getContext()->setGroups(['Default', 'Config']);

        return $this->viewHandler->handle($view);
    }
}
