<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Utils\FileHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to serve uploaded branding images (e.g. company logo).
 */
final class BrandingController extends AbstractController
{
    #[Route(path: '/branding/image/{filename}', name: 'branding_image', methods: ['GET'])]
    public function imageAction(string $filename, FileHelper $fileHelper): Response
    {
        $directory = $fileHelper->getDataDirectory('images');
        $file = $directory . basename($filename);

        if (!file_exists($file)) {
            throw $this->createNotFoundException('Image not found.');
        }

        return new BinaryFileResponse($file);
    }
}
