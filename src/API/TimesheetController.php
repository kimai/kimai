<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\Timesheet;
use App\Form\TimesheetEditForm;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TimesheetRepository;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Nelmio\ApiDocBundle\Annotation as API;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("Timesheet")
 *
 * @Security("is_granted('ROLE_USER')")
 */
class TimesheetController extends Controller
{
    /**
     * @var TimesheetRepository
     */
    protected $repository;

    /**
     * @var ViewHandlerInterface
     */
    protected $viewHandler;

    /**
     * @param ViewHandlerInterface $viewHandler
     * @param TimesheetRepository $repository
     */
    public function __construct(ViewHandlerInterface $viewHandler, TimesheetRepository $repository)
    {
        $this->viewHandler = $viewHandler;
        $this->repository = $repository;
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns the collection of all existing timesheets for the user",
     *     @SWG\Schema(ref=@API\Model(type=Timesheet::class)),
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
        $query = new TimesheetQuery();
        $query->setUser($this->getUser());
        $query->setResultType(TimesheetQuery::RESULT_TYPE_OBJECTS);

        $data = $this->repository->findByQuery($query);
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Returns one timesheet entity",
     *     @SWG\Schema(ref=@API\Model(type=Timesheet::class)),
     * )
     *
     * @param int $id
     * @return Response
     */
    public function getAction($id)
    {
        $data = $this->repository->find($id);
        if (null === $data) {
            throw new NotFoundException();
        }
        $view = new View($data, 200);
        $view->getContext()->setGroups(['Default', 'Entity']);

        return $this->viewHandler->handle($view);
    }

    /**
     * @SWG\Response(
     *     response=200,
     *     description="Creates a new new timesheet entry and returns it afterwards",
     *     @SWG\Schema(ref=@API\Model(type=Timesheet::class)),
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request)
    {
        // TODO check permissions
        // TODO allow setting the user id
        // TODO define SWG\Schema (Timesheet::class is wrong due to virtual_properties)
        // TODO support duration_only mode ?

        $timesheet = new Timesheet();
        $timesheet->setUser($this->getUser());
        $timesheet->setBegin(new \DateTime());

        $form = $this->createForm(TimesheetEditForm::class, $timesheet, [
            'csrf_protection' => false,
            'method' => 'POST',
            'duration_only' => false,
        ]);

        $form->setData($timesheet);
        $form->submit($request->request->all());

        if ($form->isValid()) {
            if (null !== $timesheet->getId()) {
                return new Response('This method does not support updates', Response::HTTP_BAD_REQUEST);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($timesheet);
            $entityManager->flush();

            $view = new View($timesheet, 200);
            $view->getContext()->setGroups(['Default', 'Entity']);

            return $this->viewHandler->handle($view);
        }

        $view = new View($form);

        return $this->viewHandler->handle($view);
    }
}
