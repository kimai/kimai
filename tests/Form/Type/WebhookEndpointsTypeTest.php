<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Type;

use App\Form\Type\WebhookEndpointsType;
use App\Form\Type\WebhookEndpointType;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\Traits\ValidatorExtensionTrait;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(WebhookEndpointsType::class)]
class WebhookEndpointsTypeTest extends TypeTestCase
{
    use ValidatorExtensionTrait;

    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([
                new WebhookEndpointType($this->createMock(TranslatorInterface::class), $this->createConfiguration()),
                $this->createType(),
            ], []),
        ]);
    }

    public function testExistingJsonIsDecodedIntoCollectionRows(): void
    {
        $model = new TypeTestModel([
            'endpoints' => '[{"url":"https://1.1.1.1/hook","secret":"top-secret","events":["timesheet.create"]}]',
        ]);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('endpoints', WebhookEndpointsType::class);
        $form = $form->getForm();

        self::assertCount(1, $form->get('endpoints'));
        self::assertSame('https://1.1.1.1/hook', $form->get('endpoints')->get('0')->get('url')->getData());
        self::assertSame('top-secret', $form->get('endpoints')->get('0')->get('secret')->getData());
        self::assertSame(['timesheet.create'], $form->get('endpoints')->get('0')->get('events')->getData());
    }

    public function testSubmitEncodesOnlyCompleteEndpointsAsJson(): void
    {
        $model = new TypeTestModel(['endpoints' => '[]']);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('endpoints', WebhookEndpointsType::class);
        $form = $form->getForm();

        $form->submit([
            'endpoints' => [
                [
                    'url' => 'https://1.1.1.1/hook',
                    'secret' => 'alpha',
                    'events' => [],
                ],
                [
                    'url' => '',
                    'secret' => 'ignored',
                    'events' => [],
                ],
            ],
        ]);

        self::assertTrue($form->isSynchronized());
        $stored = $model->offsetGet('endpoints');
        self::assertIsString($stored);
        self::assertJsonStringEqualsJsonString(
            '[{"url":"https://1.1.1.1/hook","secret":"alpha","events":[]}]',
            $stored
        );
    }

    public function testBuildViewExposesConfiguredMaximumEndpoints(): void
    {
        $model = new TypeTestModel(['endpoints' => '[]']);

        $form = $this->factory->createBuilder(FormType::class, $model);
        $form->add('endpoints', WebhookEndpointsType::class);
        $form = $form->getForm();
        $view = $form->createView();

        self::assertSame(2, $view['endpoints']->vars['max_endpoints']);
    }

    public function testSubmitRejectsTooManyEndpoints(): void
    {
        $violations = $this->validate([
            ['url' => 'https://1.1.1.1/hook', 'secret' => 'alpha', 'events' => []],
            ['url' => 'https://8.8.8.8/hook', 'secret' => 'beta', 'events' => []],
            ['url' => 'https://9.9.9.9/hook', 'secret' => 'gamma', 'events' => []],
        ]);

        self::assertCount(1, $violations);
        self::assertSame('Too many webhook endpoints: 3 configured, limit is 2.', $violations->get(0)->getMessage());
    }

    public function testSubmitRejectsDuplicateUrls(): void
    {
        $violations = $this->validate([
            ['url' => 'https://1.1.1.1/hook', 'secret' => 'alpha', 'events' => []],
            ['url' => 'https://1.1.1.1/hook', 'secret' => 'beta', 'events' => []],
        ]);

        self::assertCount(1, $violations);
        self::assertSame('Duplicate endpoint URL: https://1.1.1.1/hook', $violations->get(0)->getMessage());
        self::assertSame('1.url', $violations->get(0)->getPropertyPath());
    }

    private function createType(): WebhookEndpointsType
    {
        return new WebhookEndpointsType($this->createConfiguration());
    }

    private function createConfiguration(): \App\Configuration\SystemConfiguration
    {
        return SystemConfigurationFactory::createStub([
            'webhook.max_endpoints' => 2,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $value
     */
    private function validate(array $value): ConstraintViolationListInterface
    {
        return Validation::createValidator()->validate(
            $value,
            new Callback([$this->createType(), 'validateEndpoints']),
        );
    }
}
