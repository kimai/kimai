<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\SecurityTesting;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Tests\API\APIControllerBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

/**
 * Security tests for OWASP WSTG v4.2 category "4.7 Input Validation Testing" (WSTG-INPV).
 *
 * Covers SQL injection (WSTG-INPV-05), reflected/stored XSS (WSTG-INPV-01) and
 * malformed input handling against the JSON API and the HTML frontend.
 */
#[Group('integration')]
#[Group('security')]
class InputValidationSecurityTest extends APIControllerBaseTestCase
{
    private const DATE_FORMAT_HTML5 = 'Y-m-d\TH:i:s';

    /**
     * WSTG-INPV-05:
     * SQL injection attempts in search filters must be neutralized by the query
     * builders parameter binding and must never cause a server error.
     */
    #[DataProvider('provideSearchEndpoints')]
    public function testSqlInjectionInSearchTermIsHandledSafely(string $role, string $url): void
    {
        $client = $this->getClientForAuthenticatedUser($role);

        foreach (["' OR 1=1; -- ", "' UNION SELECT null, username, password FROM kimai2_users; -- "] as $payload) {
            $this->request($client, $url, 'GET', ['term' => $payload]);

            $response = $client->getResponse();
            self::assertTrue(
                $response->isSuccessful(),
                \sprintf('SQLi payload on %s caused a server error (%s)', $url, $response->getStatusCode())
            );

            $content = $response->getContent();
            self::assertIsString($content);
            self::assertIsArray(json_decode($content, true), \sprintf('SQLi payload on %s broke the JSON response', $url));
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideSearchEndpoints(): array
    {
        return [
            'customers' => [User::ROLE_ADMIN, '/api/customers'],
            'projects' => [User::ROLE_ADMIN, '/api/projects'],
            'activities' => [User::ROLE_ADMIN, '/api/activities'],
            'timesheets' => [User::ROLE_ADMIN, '/api/timesheets'],
            // the user collection requires the "view_user" permission (super-admin only)
            'users' => [User::ROLE_SUPER_ADMIN, '/api/users'],
            'tags' => [User::ROLE_ADMIN, '/api/tags'],
        ];
    }

    /**
     * WSTG-INPV-05:
     * SQL injection payloads stored through the API must be persisted inertly and
     * the targeted table must stay intact (proves prepared statements are used).
     */
    public function testSqlInjectionPayloadIsStoredInertly(): void
    {
        $payload = "sql-test'); DROP TABLE kimai2_timesheet; --";

        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => $this->resolveFirstId($client, '/api/activities'),
            'project' => $this->resolveFirstId($client, '/api/projects'),
            'begin' => (new \DateTime('-1 hour'))->format(self::DATE_FORMAT_HTML5),
            'end' => (new \DateTime())->format(self::DATE_FORMAT_HTML5),
            'description' => $payload,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful(), 'Could not create timesheet for SQLi test');

        // the attacked table must still be intact and the payload must be readable verbatim
        $this->request($client, '/api/timesheets');
        self::assertTrue($client->getResponse()->isSuccessful(), 'Timesheet table was damaged by SQLi payload');

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        self::assertStringContainsString('DROP TABLE kimai2_timesheet', $content, 'SQLi payload was not stored inertly');
    }

    /**
     * WSTG-INPV-01:
     * A stored XSS payload (created via the API) must be HTML escaped when it is
     * rendered in the web frontend (Twig output escaping / markdown safe mode).
     */
    public function testStoredXssPayloadIsEscapedInHtmlOutput(): void
    {
        $payload = '<script>alert("WSTG-INPV-01")</script>';

        $apiClient = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => $this->resolveFirstId($apiClient, '/api/activities'),
            'project' => $this->resolveFirstId($apiClient, '/api/projects'),
            'begin' => (new \DateTime('-1 hour'))->format(self::DATE_FORMAT_HTML5),
            'end' => (new \DateTime())->format(self::DATE_FORMAT_HTML5),
            'description' => $payload,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($apiClient, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($apiClient->getResponse()->isSuccessful(), 'Could not create timesheet for XSS test');

        // a second client (web session instead of API token) requires a fresh kernel
        self::ensureKernelShutdown();
        $client = $this->loginByUsername(UserFixtures::USERNAME_ADMIN);
        $client->request('GET', '/en/timesheet/');
        self::assertTrue($client->getResponse()->isSuccessful());

        $html = $client->getResponse()->getContent();
        self::assertIsString($html);
        self::assertStringNotContainsString($payload, $html, 'Unescaped stored XSS payload found in HTML output');
        self::assertStringContainsString('&lt;script&gt;', $html, 'Escaped payload not found, page might not render timesheet descriptions');
    }

    /**
     * WSTG-INPV-01:
     * The API itself must return the payload unmodified (JSON is not HTML),
     * output encoding is the responsibility of the consuming context.
     */
    public function testXssPayloadIsReturnedVerbatimByApi(): void
    {
        $payload = '<script>alert("WSTG-INPV-01")</script>';

        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $data = [
            'activity' => $this->resolveFirstId($client, '/api/activities'),
            'project' => $this->resolveFirstId($client, '/api/projects'),
            'begin' => (new \DateTime('-1 hour'))->format(self::DATE_FORMAT_HTML5),
            'end' => (new \DateTime())->format(self::DATE_FORMAT_HTML5),
            'description' => $payload,
        ];
        $json = json_encode($data);
        self::assertIsString($json);
        $this->request($client, '/api/timesheets', 'POST', [], $json);
        self::assertTrue($client->getResponse()->isSuccessful());

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $result = json_decode($content, true);
        self::assertIsArray($result);
        self::assertSame($payload, $result['description']);

        $contentType = $client->getResponse()->headers->get('Content-Type');
        self::assertIsString($contentType);
        self::assertStringContainsString('application/json', $contentType);
    }

    /**
     * WSTG-INPV (generic input handling):
     * Malformed JSON bodies must be rejected with HTTP 400 and must not trigger
     * a server error or exception disclosure.
     */
    public function testMalformedJsonBodyIsRejected(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_ADMIN);
        $this->request($client, '/api/customers', 'POST', [], '{"name": "broken", ');
        $this->assertBadRequestResponse($client->getResponse());
    }

    /**
     * Resolves a real entity ID from the API instead of assuming fixture seeding
     * order.
     *
     * These payloads previously hard-coded 'activity' => 1 and 'project' => 1.
     * src/DataFixtures/UserFixtures.php supplies the constants but
     * src/Command/ResetTestCommand.php seeds the actual rows, so a literal ID
     * silently targets a different record - or none - whenever that ordering
     * changes. A POST that then 400s would make these injection tests pass
     * without ever storing the payload they are meant to check.
     */
    private function resolveFirstId(HttpKernelBrowser $client, string $url): int
    {
        $this->request($client, $url);
        self::assertTrue($client->getResponse()->isSuccessful(), \sprintf('Could not list %s', $url));

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        $rows = json_decode($content, true);
        self::assertIsArray($rows);
        self::assertNotEmpty($rows, \sprintf('No fixture rows returned by %s', $url));

        $first = $rows[0];
        self::assertIsArray($first);
        self::assertArrayHasKey('id', $first);

        $id = $first['id'];
        self::assertIsInt($id);

        return $id;
    }
}
