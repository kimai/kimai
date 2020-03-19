<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The PWA controller offers localized manifest files.
 */
final class PwaController extends AbstractController
{
    /**
     * @Route(path="/manifest.json", name="pwa_manifest", methods={"GET"})
     */
    public function manifest(Request $request, TranslatorInterface $translator, RouterInterface $router)
    {
        // Compose the base manifest information
        $manifest = [
            'name' => 'Kimai ' . $translator->trans('time_tracking'),
            'short_name' => 'Kimai',
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'display' => 'standalone',
            'lang' => $request->getLocale(),
            'start_url' => $router->generate('homeLocale'),
            'icons' => [
                [
                    'src' => '/favicon-32x32.png',
                    'sizes' => '32x32',
                    'type' => 'image/png',
                    'density' => '0.75',
                ],
                [
                    'src' => '/touch-icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'density' => '4',
                ]
            ]
        ];

        return $this->json($manifest);
    }
}
