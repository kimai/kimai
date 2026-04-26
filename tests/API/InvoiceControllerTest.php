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
use App\Entity\Team;
use App\Entity\User;
use App\Repository\TeamRepository;
use App\Tests\DataFixtures\InvoiceFixtures;
use App\Tests\Mocks\InvoiceTestMetaFieldSubscriberMock;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcher;
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

    public function testDownloadRespectsCustomerPermission(): void
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
}
