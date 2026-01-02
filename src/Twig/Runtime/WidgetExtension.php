<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Widget\WidgetInterface;
use App\Widget\WidgetService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class WidgetExtension implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly WidgetService $service,
        private readonly Security $security
    )
    {
    }

    /**
     * @param array<string, string|bool|int|float> $options
     */
    public function renderWidget(Environment $environment, WidgetInterface|string $widget, array $options = []): string
    {
        if (\is_string($widget)) {
            if (!$this->service->hasWidget($widget)) {
                throw new \InvalidArgumentException(\sprintf('Unknown widget "%s" requested', $widget));
            }

            $widget = $this->service->getWidget($widget);
        }

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $widget->setUser($user);
        }

        $options = array_merge($widget->getOptions(), $options);

        return $environment->render($widget->getTemplateName(), [
            'data' => $widget->getData($options),
            'options' => $options,
            'title' => $widget->getTitle(),
            'widget' => $widget,
        ]);
    }
}
