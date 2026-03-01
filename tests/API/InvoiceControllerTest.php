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
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class InvoiceControllerTest extends APIControllerBaseTestCase
{
    /**
     * @return Invoice[]
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
}
