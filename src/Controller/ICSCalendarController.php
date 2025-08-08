<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\ICSCalendarSource;
use App\Form\ICSCalendarSourceForm;
use App\Repository\ICSCalendarSourceRepository;
use App\Utils\PageSetup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/calendar/ics')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
final class ICSCalendarController extends AbstractController
{
    public function __construct(private ICSCalendarSourceRepository $repository)
    {
    }

    #[Route(path: '/', name: 'calendar_ics', methods: ['GET'])]
    public function indexAction(): Response
    {
        $user = $this->getUser();
        $sources = $this->repository->findEnabledForUser($user);

        $page = new PageSetup('calendar.ics.title');
        $page->setHelp('calendar.ics.html');

        return $this->render('calendar/ics/index.html.twig', [
            'page_setup' => $page,
            'sources' => $sources,
        ]);
    }

    #[Route(path: '/create', name: 'calendar_ics_create', methods: ['GET', 'POST'])]
    public function createAction(Request $request): Response
    {
        $user = $this->getUser();
        $source = new ICSCalendarSource();
        $source->setUser($user);

        $form = $this->createForm(ICSCalendarSourceForm::class, $source, [
            'action' => $this->generateUrl('calendar_ics_create'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($source, true);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('calendar_ics');
        }

        $page = new PageSetup('calendar.ics.create');
        $page->setHelp('calendar.ics.html');

        return $this->render('calendar/ics/edit.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'calendar_ics_edit', methods: ['GET', 'POST'])]
    public function editAction(ICSCalendarSource $source, Request $request): Response
    {
        $user = $this->getUser();
        
        if ($source->getUser() !== $user) {
            throw $this->createAccessDeniedException('You can only edit your own ICS calendar sources.');
        }

        // Handle AJAX toggle request
        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            
            if (is_array($data) && isset($data['enabled'])) {
                $source->setEnabled((bool) $data['enabled']);
                $this->repository->save($source, true);
                
                return new JsonResponse(['success' => true]);
            }
            
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $form = $this->createForm(ICSCalendarSourceForm::class, $source, [
            'action' => $this->generateUrl('calendar_ics_edit', ['id' => $source->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($source, true);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('calendar_ics');
        }

        $page = new PageSetup('calendar.ics.edit');
        $page->setHelp('calendar.ics.html');

        return $this->render('calendar/ics/edit.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
            'source' => $source,
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'calendar_ics_delete', methods: ['GET', 'POST'])]
    public function deleteAction(ICSCalendarSource $source, Request $request): Response
    {
        $user = $this->getUser();
        
        if ($source->getUser() !== $user) {
            throw $this->createAccessDeniedException('You can only delete your own ICS calendar sources.');
        }

        $deleteForm = $this->createFormBuilder(null, [
            'attr' => [
                'data-form-event' => 'kimai.confirmDelete',
                'data-msg-error' => 'action.delete.error',
                'data-msg-success' => 'action.delete.success',
            ],
        ])->getForm();

        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {
            $this->repository->remove($source, true);
            $this->flashSuccess('action.delete.success');
        }

        return $this->redirectToRoute('calendar_ics');
    }

    #[Route(path: '/{id}/toggle', name: 'calendar_ics_toggle', methods: ['POST'])]
    public function toggleAction(ICSCalendarSource $source, Request $request): Response
    {
        try {
            $user = $this->getUser();
            
            if ($source->getUser() !== $user) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            if (!$request->isXmlHttpRequest()) {
                return new JsonResponse(['error' => 'AJAX requests only'], 400);
            }

            $data = json_decode($request->getContent(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }
            
            if (!is_array($data) || !isset($data['enabled'])) {
                return new JsonResponse(['error' => 'Missing enabled parameter'], 400);
            }

            $source->setEnabled((bool) $data['enabled']);
            $this->repository->save($source, true);

            return new JsonResponse(['success' => true, 'enabled' => $source->isEnabled()]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    #[Route(path: '/{id}/refresh', name: 'calendar_ics_refresh', methods: ['POST'])]
    public function refreshAction(ICSCalendarSource $source): Response
    {
        $user = $this->getUser();
        
        if ($source->getUser() !== $user) {
            throw $this->createAccessDeniedException('You can only refresh your own ICS calendar sources.');
        }

        // Update the last sync time
        $source->setLastSync(new \DateTime());
        $this->repository->save($source, true);

        $this->flashSuccess('calendar.ics.refresh.success');

        return $this->redirectToRoute('calendar_ics');
    }
} 