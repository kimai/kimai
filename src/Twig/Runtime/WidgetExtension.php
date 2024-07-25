<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Widget\WidgetException;
use App\Widget\WidgetInterface;
use App\Widget\WidgetService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

final class WidgetExtension implements RuntimeExtensionInterface
{
    public function __construct(private WidgetService $service, private Security $security)
    {
    }

    /**
     * @param WidgetInterface|string $widget
     * @param array $options
     * @return string
     * @throws WidgetException
     */
    public function renderWidget(Environment $environment, $widget, array $options = []): string
    {
        if (!($widget instanceof WidgetInterface) && !\is_string($widget)) {
            throw new \InvalidArgumentException('Widget must be either a WidgetInterface or a string');
        }

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

        $options = $widget->getOptions($options);

        return $environment->render($widget->getTemplateName(), [
            'data' => $widget->getData($options),
            'options' => $options,
            'title' => $widget->getTitle(),
            'widget' => $widget,
        ]);
    }
}
