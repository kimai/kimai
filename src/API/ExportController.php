<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API;

use App\Entity\ExportTemplate;
use App\Repository\ExportTemplateRepository;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/export')]
#[IsGranted('API')]
#[OA\Tag(name: 'Export')]
final class ExportController extends BaseApiController
{
    /**
     * Delete export template
     */
    #[IsGranted('create_export_template')]
    #[OA\Delete(responses: [new OA\Response(response: 204, description: 'Delete export template')], x: ['internal' => true])]
    #[OA\Parameter(name: 'id', description: 'Export template ID to delete', in: 'path', required: true)]
    #[Route(path: '/{id}', name: 'delete_export_template', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteTemplate(ExportTemplate $exportTemplate, ExportTemplateRepository $repository, ViewHandlerInterface $viewHandler): Response
    {
        $repository->removeExportTemplate($exportTemplate);

        $view = new View(null, Response::HTTP_NO_CONTENT);

        return $viewHandler->handle($view);
    }
}
