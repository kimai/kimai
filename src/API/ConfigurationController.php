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
use App\Entity\User;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;

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
     * @param ViewHandlerInterface $viewHandler
     * @param LanguageFormattings $formats
     */
    public function __construct(ViewHandlerInterface $viewHandler, LanguageFormattings $formats)
    {
        $this->viewHandler = $viewHandler;
        $this->formats = $formats;
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
     */
    public function i18nAction()
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
}
