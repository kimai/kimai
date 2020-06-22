<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Configuration\ThemeConfiguration;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TitleExtension extends AbstractExtension
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;
    /**
     * @var ThemeConfiguration
     */
    protected $configuration;

    public function __construct(TranslatorInterface $translator, ThemeConfiguration $configuration)
    {
        $this->translator = $translator;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_title', [$this, 'generateTitle']),
        ];
    }

    public function generateTitle(?string $prefix = null, string $delimiter = ' – '): string
    {
        $title = $this->configuration->getTitle() ?? 'Kimai';

        return ($prefix ?? '') . ($title) . $delimiter . $this->translator->trans('time_tracking', [], 'messages');
    }
}
