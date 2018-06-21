<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Constants;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * This controller can render the markdown documentation from /var/docs/
 *
 * @Route("/help")
 * @Security("is_granted('ROLE_USER')")
 */
class HelpController extends Controller
{
    public const README = 'README';
    public const DOCS_DIR = 'var/docs/';

    /**
     * @var string
     */
    protected $projectDirectory;

    /**
     * HelpController constructor.
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @Route("/", defaults={"chapter": "README"}, name="help")
     * @Route("/{chapter}", requirements={"chapter": "[a-zA-Z]*"}, name="help_chapter")
     * @Method("GET")
     *
     * @param string $chapter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(?string $chapter)
    {
        $breadcrumb = [self::README];
        if (self::README !== $chapter) {
            $breadcrumb[] = $chapter;
        }

        $chapterFile = $this->getFilenameForChapter($chapter);

        if (!file_exists($chapterFile)) {
            throw $this->createNotFoundException('Documentation chapter not found: ' . $chapter);
        }

        $content = file_get_contents($chapterFile);

        return $this->render('help/index.html.twig', [
            'breadcrumb' => $breadcrumb,
            'chapter' => $chapter,
            'documentation' => $content,
            'github' => Constants::GITHUB
        ]);
    }

    /**
     * @param string $chapter
     * @return string
     */
    protected function getFilenameForChapter(string $chapter)
    {
        return $this->projectDirectory . DIRECTORY_SEPARATOR . self::DOCS_DIR . $chapter . '.md';
    }
}
