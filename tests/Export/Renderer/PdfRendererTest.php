<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Export\Renderer;

use App\Entity\User;
use App\Export\Renderer\PDFRenderer;
use App\Repository\UserRepository;
use App\Security\CurrentUser;
use App\Timesheet\UserDateTimeFactory;
use App\Utils\HtmlToPdfConverter;
use App\Utils\MPdfConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Twig\Loader\FilesystemLoader;

/**
 * @covers \App\Export\Renderer\PDFRenderer
 * @covers \App\Export\Renderer\RendererTrait
 */
class PdfRendererTest extends AbstractRendererTest
{
    protected function getDateTimeFactory()
    {
        $user = new User();
        $repository = $this->getMockBuilder(UserRepository::class)->setMethods(['getById'])->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('getById')->willReturn($user);
        $token = $this->getMockBuilder(UsernamePasswordToken::class)->setMethods(['getUser'])->disableOriginalConstructor()->getMock();
        $token->expects($this->once())->method('getUser')->willReturn($user);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $user = new CurrentUser($tokenStorage, $repository);

        return new UserDateTimeFactory($user);
    }

    public function testConfiguration()
    {
        $sut = new PDFRenderer(
            $this->getMockBuilder(\Twig_Environment::class)->disableOriginalConstructor()->getMock(),
            $this->getDateTimeFactory(),
            $this->getMockBuilder(HtmlToPdfConverter::class)->getMock()
        );

        $this->assertEquals('pdf', $sut->getId());
        $this->assertEquals('pdf', $sut->getTitle());
        $this->assertEquals('pdf', $sut->getIcon());
    }

    public function testRender()
    {
        $kernel = self::bootKernel();
        /** @var \Twig_Environment $twig */
        $twig = $kernel->getContainer()->get('twig');
        $stack = $kernel->getContainer()->get('request_stack');
        $cacheDir = $kernel->getContainer()->getParameter('kernel.cache_dir');
        $converter = new MPdfConverter($cacheDir);
        $request = new Request();
        $request->setLocale('en');
        $stack->push($request);

        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();

        $sut = new PDFRenderer($twig, $this->getDateTimeFactory(), $converter);

        $response = $this->render($sut);

        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertEquals('attachment; filename=kimai-export.pdf', $response->headers->get('Content-Disposition'));

        $this->assertNotEmpty($response->getContent());
    }
}
