<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\ExportTemplate;
use App\Entity\User;
use App\Repository\ExportTemplateRepository;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class ExportControllerTest extends APIControllerBaseTestCase
{
    private function importExportTemplate(): ExportTemplate
    {
        /** @var ExportTemplateRepository $repository */
        $repository = $this->getEntityManager()->getRepository(ExportTemplate::class);

        $template = new ExportTemplate();
        $template->setRenderer('csv');
        $template->setTitle('csv');
        $template->setColumns(['activity.name', 'project.number', 'project.name', 'customer.name', 'user.account_number', 'duration', 'date', 'rate', 'currency']);
        $template->setLanguage('en');
        $template->setLanguage('en');

        $repository->saveExportTemplate($template);

        return $template;
    }

    public function testDeleteIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/export/1', Request::METHOD_DELETE);
    }

    public function testDeleteActionWithUnknownTemplate(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/export/' . PHP_INT_MAX);
    }

    public function testDeleteEntityIsSecure(): void
    {
        $client = $this->createClient();
        $template = $this->importExportTemplate();

        $this->assertRequestIsSecured($client, '/api/export/' . $template->getId(), Request::METHOD_DELETE);
    }

    public function testDeleteActionWithoutAuthorization(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $template = $this->importExportTemplate();

        $this->request($client, '/api/export/' . $template->getId(), Request::METHOD_DELETE);

        $response = $client->getResponse();
        $this->assertApiResponseAccessDenied($response);
    }

    public function testDeleteAction(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $template = $this->importExportTemplate();

        $this->request($client, '/api/export/' . $template->getId(), Request::METHOD_DELETE);
        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());
    }
}
