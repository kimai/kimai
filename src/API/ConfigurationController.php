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
 * @Security("is_granted('IS_AUTHENTICATED_REMEMBERED')")
 */
class ConfigurationController extends BaseApiController
{
    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;
    /**
     * @var LanguageFormattings
     */
    protected $formats;
    /**
     * @var TimesheetConfiguration
     */
    protected $timesheetConfiguration;

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
            ->setIs24hours($this->formats->isTwentyFourHours($locale));

        $view = new View($model, 200);
        $view->getContext()->setGroups(['Default', 'Config']);

        return $this->viewHandler->handle($view);
    }

    /**
     * Returns the instance specific timesheet configuration
     *
     * @SWG\Response(
     *      response=200,
     *      description="Returns the instance specific timesheet configuration"
     * )
     *
     * @Rest\Get(path="/config/timesheet")
     *
     * @ApiSecurity(name="apiUser")
     * @ApiSecurity(name="apiToken")
     */
    public function trackingModeAction(): Response
    {
        $configValues = [];
        $exposingFunctionPrefix = 'get';
        $allClassMethods = get_class_methods($this->timesheetConfiguration);
        $gettersInClass = array_filter($allClassMethods, function ($method) use ($exposingFunctionPrefix) {
            return strpos($method, $exposingFunctionPrefix) !== false;
        });

        foreach ($gettersInClass as $method) {
            array_push($configValues, [lcfirst(ltrim($method, $exposingFunctionPrefix)) => call_user_func([$this->timesheetConfiguration, $method])]);
        }

        $view = new View($configValues, 200);
        $view->getContext()->setGroups(['Default', 'Config']);

        return $this->viewHandler->handle($view);
    }
}
