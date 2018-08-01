<?php
declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use FOS\RestBundle\View\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\UserRepository;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\ViewHandler;
use FOS\RestBundle\View\ViewHandlerInterface;
use \Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Not found', \Exception $previous = null, int $code = 0, array $headers = array())
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
