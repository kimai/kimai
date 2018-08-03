<?php
declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Swagger\Annotations as SWG;

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
        $view = new View('pong', 200);
        return $this->viewHandler->handle($view);
    }
}
