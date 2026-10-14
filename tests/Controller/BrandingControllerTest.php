<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Utils\FileHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(\App\Controller\BrandingController::class)]
#[Group('integration')]
class BrandingControllerTest extends AbstractControllerBaseTestCase
{
    private ?string $testFile = null;

    protected function tearDown(): void
    {
        if ($this->testFile !== null && file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        $this->testFile = null;

        parent::tearDown();
    }

    public function testImageActionReturns404ForMissingFile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);
        $client->request('GET', '/en/branding/image/does_not_exist.png');

        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testImageActionServesExistingFile(): void
    {
        $client = $this->getClientForAuthenticatedUser(User::ROLE_USER);

        $fileHelper = self::getContainer()->get(FileHelper::class);
        \assert($fileHelper instanceof FileHelper);
        $directory = $fileHelper->getDataDirectory('images');
        $this->testFile = $directory . 'test_branding_' . uniqid() . '.png';
        file_put_contents($this->testFile, 'fake image data');

        $filename = basename($this->testFile);
        $client->request('GET', '/en/branding/image/' . $filename);

        $response = $client->getResponse();
        self::assertEquals(200, $response->getStatusCode());
        self::assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
    }
}
