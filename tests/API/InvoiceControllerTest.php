<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\API;

use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\InvoiceTemplate;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\TeamRepository;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Tests\DataFixtures\InvoiceTemplateFixtures;
use App\Tests\Mocks\InvoiceTestMetaFieldSubscriberMock;
use App\User\PermissionService;
use App\Utils\FileHelper;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
class InvoiceControllerTest extends APIControllerBaseTestCase
{
    /**
     * @param int<1, 999> $amount
     * @return non-empty-array<Invoice>
     */
    protected function importInvoiceFixtures(int $amount, ?array $status = null): array
    {
        $fixture = new InvoiceFixtures();
        $fixture->setAmount($amount);
        if (\is_array($status)) {
            $fixture->setStatus($status);
        }

        return $this->importFixture($fixture);
    }

    public function testIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/invoices');
    }

    public function testGetCollection(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importInvoiceFixtures(10);

        $this->assertAccessIsGranted($client, '/api/invoices');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(10, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('InvoiceCollection', $result[0]);
    }

    public function testGetCollectionWithQuery(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importInvoiceFixtures(5, [Invoice::STATUS_PENDING]);
        $this->importInvoiceFixtures(2, [Invoice::STATUS_NEW]);
        $this->importInvoiceFixtures(7, [Invoice::STATUS_PAID]);
        $this->importInvoiceFixtures(1, [Invoice::STATUS_CANCELED]);

        $query = ['order' => 'ASC', 'orderBy' => 'name', 'status' => [Invoice::STATUS_PAID]];
        $this->assertAccessIsGranted($client, '/api/invoices', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(7, \count($result));
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('InvoiceCollection', $result[0]);
    }

    public function testGetCollectionWithPagination(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);
        $this->importInvoiceFixtures(20);

        $query = ['page' => 2, 'size' => 4];
        $this->assertAccessIsGranted($client, '/api/invoices', 'GET', $query);

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertEquals(4, \count($result));
        $this->assertPagination($client->getResponse(), 2, 4, 5, 20);
        self::assertIsArray($result[0]);
        self::assertApiResponseTypeStructure('InvoiceCollection', $result[0]);
    }

    public function testGetEntityIsSecure(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $invoices = $this->importInvoiceFixtures(1);

        $this->assertApiAccessDenied($client, '/api/invoices/' . $invoices[0]->getId());
    }

    public function testGetEntity(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $invoices = $this->importInvoiceFixtures(1);

        $this->assertAccessIsGranted($client, '/api/invoices/' . $invoices[0]->getId());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('Invoice', $result);
        self::assertArrayHasKey('metaFields', $result);
        self::assertCount(0, $result['metaFields']);
    }

    public function testNotFound(): void
    {
        $this->assertEntityNotFound(User::ROLE_USER, '/api/invoices/' . PHP_INT_MAX);
    }

    public function testGetEntityRespectsCustomerPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $invoices = $this->importInvoiceFixtures(1, [Invoice::STATUS_NEW]);
        $invoice = $invoices[0];
        $customer = $invoice->getCustomer();
        self::assertInstanceOf(Customer::class, $customer);

        $this->assertAccessIsGranted($client, '/api/invoices/' . $invoice->getId());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('Invoice', $result);

        $team = new Team('foo');
        $team->addTeamlead($this->getUserByRole(User::ROLE_ADMIN));
        $team->addCustomer($customer);

        $em = $this->getEntityManager();
        /** @var TeamRepository $repository */
        $repository = $em->getRepository(Team::class);
        $repository->saveTeam($team);

        $this->assertApiAccessDenied($client, '/api/invoices/' . $invoice->getId());
    }

    public function testDownload(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $invoice = $this->importInvoiceFixtures(1)[0];
        $filename = $invoice->getInvoiceFilename() . '.pdf';
        $invoice->setFilename($filename);

        $em = $this->getEntityManager();
        $em->persist($invoice);
        $em->flush();

        /** @var FileHelper $fileHelper */
        $fileHelper = $this->getPrivateService(FileHelper::class);
        $path = $fileHelper->getDataDirectory('invoices') . $filename;
        file_put_contents($path, '%PDF-1.4 test');

        try {
            $this->assertAccessIsGranted($client, '/api/invoices/' . $invoice->getId() . '/download');

            $response = $client->getResponse();
            self::assertInstanceOf(BinaryFileResponse::class, $response);
            self::assertEquals('application/pdf', $response->headers->get('Content-Type'));
            self::assertStringContainsString('attachment; filename=' . $filename, $response->headers->get('Content-Disposition') ?? '');
        } finally {
            $fileHelper->removeFile($path);
        }
    }

    public function testDownloadIsSecure(): void
    {
        $client = $this->getClientForAuthenticatedUser();
        $invoices = $this->importInvoiceFixtures(1);

        $this->assertApiAccessDenied($client, '/api/invoices/' . $invoices[0]->getId() . '/download');
    }

    public function testDownloadRespectsCustomerPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $invoice = $this->importInvoiceFixtures(1, [Invoice::STATUS_NEW])[0];
        $filename = $invoice->getInvoiceFilename() . '.pdf';
        $invoice->setFilename($filename);

        $em = $this->getEntityManager();
        $em->persist($invoice);
        $em->flush();

        /** @var FileHelper $fileHelper */
        $fileHelper = $this->getPrivateService(FileHelper::class);
        $path = $fileHelper->getDataDirectory('invoices') . $filename;
        file_put_contents($path, '%PDF-1.4 test');

        try {
            $this->assertAccessIsGranted($client, '/api/invoices/' . $invoice->getId() . '/download');

            $customer = $invoice->getCustomer();
            self::assertInstanceOf(Customer::class, $customer);

            $team = new Team('foo');
            $team->addTeamlead($this->getUserByRole(User::ROLE_ADMIN));
            $team->addCustomer($customer);

            /** @var TeamRepository $repository */
            $repository = $em->getRepository(Team::class);
            $repository->saveTeam($team);

            $this->assertApiAccessDenied($client, '/api/invoices/' . $invoice->getId() . '/download');
        } finally {
            $fileHelper->removeFile($path);
        }
    }

    public function testCollectionRespectsCustomerPermission(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_TEAMLEAD);

        $invoices = $this->importInvoiceFixtures(1, [Invoice::STATUS_NEW]);
        $invoice = $invoices[0];
        $customer = $invoice->getCustomer();
        self::assertInstanceOf(Customer::class, $customer);

        $query = ['customers' => [$customer->getId()]];
        $this->assertAccessIsGranted($client, '/api/invoices', 'GET', $query);

        $team = new Team('foo');
        $team->addTeamlead($this->getUserByRole(User::ROLE_ADMIN));
        $team->addCustomer($customer);

        $em = $this->getEntityManager();
        /** @var TeamRepository $repository */
        $repository = $em->getRepository(Team::class);
        $repository->saveTeam($team);

        $this->request($client, '/api/invoices', 'GET', $query);
        $this->assertApiResponseAccessDenied($client->getResponse());
    }

    // ------------------------------------- [META FIELDS] -------------------------------------

    public function testUpdateInvoiceMetaFieldsThrowsNotFound(): void
    {
        $this->assertEntityNotFoundForPatch(User::ROLE_ADMIN, '/api/invoices/42/custom-fields', []);
    }

    public function testUpdateInvoiceMetaFieldsThrowsExceptionOnWrongStructure(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $id = $this->importInvoiceFixtures(1)[0]->getId();

        $this->assertExceptionForPatchAction($client, '/api/invoices/' . $id . '/custom-fields', ['name' => 'X', 'value' => 'X'], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testUpdateInvoiceMetaFieldsThrowsExceptionOnMissingName(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $id = $this->importInvoiceFixtures(1)[0]->getId();

        $this->assertExceptionForPatchAction($client, '/api/invoices/' . $id . '/custom-fields', [['value' => 'X']], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testUpdateInvoiceMetaFieldsThrowsExceptionOnMissingValue(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $id = $this->importInvoiceFixtures(1)[0]->getId();

        $this->assertExceptionForPatchAction($client, '/api/invoices/' . $id . '/custom-fields', [['name' => 'X']], [
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => 'Bad Request'
        ]);
    }

    public function testUpdateInvoiceMetaFieldsThrowsExceptionOnMissingMetafield(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $id = $this->importInvoiceFixtures(1)[0]->getId();

        $this->assertExceptionForPatchAction($client, '/api/invoices/' . $id . '/custom-fields', [['name' => 'X', 'value' => 'Y']], [
            'code' => Response::HTTP_NOT_FOUND,
            'message' => 'Not Found'
        ]);
    }

    public function testUpdateInvoiceMetaFields(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $invoices = $this->importInvoiceFixtures(1);
        $id = $invoices[0]->getId();
        /** @var EventDispatcher $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addSubscriber(new InvoiceTestMetaFieldSubscriberMock());

        $data = [
            [
                'name' => 'metatestmock',
                'value' => 'another,testing,bar'
            ],
            [
                'name' => 'foobar',
                'value' => 13081978
            ],
        ];
        $this->request($client, '/api/invoices/' . $id . '/custom-fields', 'PATCH', [], (string) json_encode($data));

        self::assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);

        self::assertIsArray($result);
        self::assertApiResponseTypeStructure('Invoice', $result);
        self::assertArrayHasKey('metaFields', $result);
        // only visible should be returned
        self::assertCount(1, $result['metaFields']);
        self::assertEquals(['name' => 'metatestmock', 'value' => 'another,testing,bar'], $result['metaFields'][0]);

        $em = $this->getEntityManager();
        /** @var Invoice $invoice */
        $invoice = $em->getRepository(Invoice::class)->find($id);
        self::assertEquals('another,testing,bar', $invoice->getMetaField('metatestmock')?->getValue());
        self::assertEquals(13081978, $invoice->getMetaField('foobar')?->getValue());
    }

    public function testDeleteIsSecure(): void
    {
        $this->assertUrlIsSecured('/api/invoices/1', 'DELETE');
    }

    public function testDeleteIsSecureForRole(): void
    {
        // no default role holds "delete_invoice", not even the super-admin
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $invoices = $this->importInvoiceFixtures(1);

        $this->request($client, '/api/invoices/' . $invoices[0]->getId(), 'DELETE');
        $this->assertApiResponseAccessDenied($client->getResponse());
    }

    public function testDeleteNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/invoices/' . PHP_INT_MAX);
    }

    public function testDelete(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_SUPER_ADMIN);
        $this->grantDeleteInvoicePermission();
        $invoices = $this->importInvoiceFixtures(1);
        $id = $invoices[0]->getId();
        self::assertIsInt($id);

        $this->request($client, '/api/invoices/' . $id, 'DELETE');

        self::assertTrue($client->getResponse()->isSuccessful());
        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());
        self::assertEmpty($client->getResponse()->getContent());

        $em = $this->getEntityManager();
        $em->clear();
        self::assertNull($em->getRepository(Invoice::class)->find($id));
    }

    /**
     * The "delete_invoice" permission is not part of any default role.
     */
    private function grantDeleteInvoicePermission(): void
    {
        $em = $this->getEntityManager();

        $role = (new Role())->setName(User::ROLE_SUPER_ADMIN);
        $em->persist($role);
        $em->flush();

        $permissionService = self::getContainer()->get(PermissionService::class);
        self::assertInstanceOf(PermissionService::class, $permissionService);
        $permissionService->saveRolePermission(
            (new RolePermission())->setRole($role)->setPermission('delete_invoice')->setAllowed(true)
        );
    }

    public function testDeleteTemplateIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/invoices/templates/1', 'DELETE');
    }

    public function testDeleteDocumentIsSecure(): void
    {
        $this->assertRequestIsSecured(self::createClient(), '/api/invoices/documents/invoice', 'DELETE');
    }

    public function testDeleteTemplateIsSecureForRole(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $id = $templates[0]->getId();

        $this->request($client, '/api/invoices/templates/' . $id, 'DELETE');

        $this->assertApiResponseAccessDenied($client->getResponse());

        $this->getEntityManager()->clear();
        self::assertNotNull($this->getEntityManager()->getRepository(InvoiceTemplate::class)->find($id));
    }

    public function testDeleteTemplate(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $id = $templates[0]->getId();

        $this->request($client, '/api/invoices/templates/' . $id, 'DELETE');

        self::assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->getEntityManager()->clear();
        self::assertNull($this->getEntityManager()->getRepository(InvoiceTemplate::class)->find($id));
    }

    /**
     * The predecessor of this endpoint carried its CSRF token in the URL and accepted GET.
     */
    public function testDeleteTemplateWithGetIsNotAllowed(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $id = $templates[0]->getId();

        $this->request($client, '/api/invoices/templates/' . $id, 'GET');

        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $this->getEntityManager()->clear();
        self::assertNotNull($this->getEntityManager()->getRepository(InvoiceTemplate::class)->find($id));
    }

    public function testDeleteTemplateNotFound(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/invoices/templates/' . PHP_INT_MAX);
    }

    public function testDeleteUnknownDocument(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->assertNotFoundForDelete($client, '/api/invoices/documents/this-does-not-exist');
    }

    /**
     * Shipped documents live in the source tree and may never be deleted.
     */
    public function testDeleteBuiltInDocumentIsRejected(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $this->request($client, '/api/invoices/documents/invoice', 'DELETE');

        $this->assertBadRequestResponse($client->getResponse());
        self::assertFileExists(__DIR__ . '/../../templates/invoice/renderer/invoice.html.twig');
    }

    /**
     * A document which is used by a template may not be deleted.
     */
    public function testDeleteUsedDocumentIsRejected(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);

        $fixture = new InvoiceTemplateFixtures();
        $templates = $this->importFixture($fixture);
        $renderer = $templates[0]->getRenderer();
        self::assertNotNull($renderer);

        $this->request($client, '/api/invoices/documents/' . $renderer, 'DELETE');

        $this->assertBadRequestResponse($client->getResponse());
    }

    public function testDeleteDocumentIsSecureForRole(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $this->request($client, '/api/invoices/documents/invoice', 'DELETE');

        $this->assertApiResponseAccessDenied($client->getResponse());
    }
}
