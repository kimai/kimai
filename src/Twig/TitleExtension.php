<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

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
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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

    /**
     * @param null|string $prefix
     * @param string $delimiter
     * @return string
     */
    public function generateTitle(?string $prefix = null, string $delimiter = ' – ')
    {
        return ($prefix ?? '') . 'Kimai' . $delimiter . $this->translator->trans('time_tracking', [], 'messages');
    }
}
