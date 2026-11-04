<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\Extension;

use App\Form\API\ApiFormInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Symfony only supports type extensions for concrete form types, which are resolved at compile time.
 * This decorator adds the ApiFormExtension to every form type implementing the ApiFormInterface.
 */
#[AsDecorator(decorates: 'form.extension')]
final class ApiFormExtensionDecorator implements FormExtensionInterface
{
    public function __construct(
        #[AutowireDecorated]
        private readonly FormExtensionInterface $inner,
        private readonly ApiFormExtension $apiFormExtension
    ) {
    }

    public function getType(string $name): FormTypeInterface
    {
        return $this->inner->getType($name);
    }

    public function hasType(string $name): bool
    {
        return $this->inner->hasType($name);
    }

    public function getTypeExtensions(string $name): array
    {
        $extensions = $this->inner->getTypeExtensions($name);

        if ($this->isApiForm($name)) {
            $extensions[] = $this->apiFormExtension;
        }

        return $extensions;
    }

    public function hasTypeExtensions(string $name): bool
    {
        return $this->isApiForm($name) || $this->inner->hasTypeExtensions($name);
    }

    public function getTypeGuesser(): ?FormTypeGuesserInterface
    {
        return $this->inner->getTypeGuesser();
    }

    private function isApiForm(string $name): bool
    {
        return is_a($name, ApiFormInterface::class, true);
    }
}
