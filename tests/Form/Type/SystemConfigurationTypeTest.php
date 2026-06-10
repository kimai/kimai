<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Configuration\SystemConfiguration;
use App\Form\Model\Configuration;
use App\Form\Type\SystemConfigurationType;
use App\Form\Type\WebhookEndpointsType;
use App\Form\Type\WebhookEndpointType;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(SystemConfigurationType::class)]
class SystemConfigurationTypeTest extends TypeTestCase
{
    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                new SystemConfigurationType(),
                new WebhookEndpointType($this->createMock(TranslatorInterface::class), $this->createConfiguration()),
                new WebhookEndpointsType($this->createConfiguration()),
            ], []),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testTypeSpecificConstraintsArePreservedWhenConfigurationHasNone(): void
    {
        $configuration = (new Configuration('webhook.endpoints'))
            ->setLabel('webhook.endpoints')
            ->setRequired(false)
            ->setType(WebhookEndpointsType::class)
            ->setTranslationDomain('system-configuration')
            ->setValue('[]');

        $form = $this->factory->create(SystemConfigurationType::class, $configuration);
        $form->submit([
            'name' => 'webhook.endpoints',
            'value' => [
                ['url' => 'https://1.1.1.1/hook', 'secret' => 'alpha', 'events' => []],
                ['url' => 'https://8.8.8.8/hook', 'secret' => 'beta', 'events' => []],
                ['url' => 'https://9.9.9.9/hook', 'secret' => 'gamma', 'events' => []],
            ],
        ]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
        $errors = iterator_to_array($form->getErrors(true, false));

        self::assertCount(1, $errors);
        self::assertInstanceOf(FormError::class, $errors[0]);
        self::assertSame(
            'Too many webhook endpoints: 3 configured, limit is 2.',
            $errors[0]->getMessage()
        );
    }

    private function createConfiguration(): SystemConfiguration
    {
        return SystemConfigurationFactory::createStub([
            'webhook.max_endpoints' => 2,
        ]);
    }
}
