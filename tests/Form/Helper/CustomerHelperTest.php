<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\Helper;

use App\Entity\Customer;
use App\Form\Helper\CustomerHelper;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomerHelper::class)]
class CustomerHelperTest extends TestCase
{
    private function createSut(string $format): CustomerHelper
    {
        $config = SystemConfigurationFactory::createStub(['customer.choice_pattern' => $format]);
        $helper = new CustomerHelper($config);

        return $helper;
    }

    public function testInvalidPattern(): void
    {
        $helper = $this->createSut('sdfsdf');
        self::assertEquals(CustomerHelper::PATTERN_NAME, $helper->getChoicePattern());
    }

    public function testGetChoicePattern(): void
    {
        $helper = $this->createSut(
            CustomerHelper::PATTERN_NAME . CustomerHelper::PATTERN_SPACER .
            CustomerHelper::PATTERN_COMMENT . CustomerHelper::PATTERN_SPACER .
            CustomerHelper::PATTERN_COMPANY . CustomerHelper::PATTERN_SPACER .
            CustomerHelper::PATTERN_NUMBER
        );

        self::assertEquals(
            CustomerHelper::PATTERN_NAME . CustomerHelper::SPACER .
            CustomerHelper::PATTERN_COMMENT . CustomerHelper::SPACER .
            CustomerHelper::PATTERN_COMPANY . CustomerHelper::SPACER .
            CustomerHelper::PATTERN_NUMBER,
            $helper->getChoicePattern()
        );
    }

    public function testGetChoiceLabel(): void
    {
        $helper = $this->createSut(
            CustomerHelper::PATTERN_NAME . CustomerHelper::PATTERN_SPACER .
            CustomerHelper::PATTERN_COMMENT . CustomerHelper::PATTERN_SPACER .
            CustomerHelper::PATTERN_COMPANY . CustomerHelper::PATTERN_SPACER .
            CustomerHelper::PATTERN_NUMBER
        );

        $customer = new Customer(' - --- - -FOO BAR- --- -  -  - ');
        self::assertEquals('--- - -FOO BAR- ---', $helper->getChoiceLabel($customer));

        $customer = new Customer('FOO BAR');
        $customer->setComment('Lorem Ipsum');
        self::assertEquals('FOO BAR - Lorem Ipsum', $helper->getChoiceLabel($customer));
        $customer->setCompany('Acme University');
        self::assertEquals('FOO BAR - Lorem Ipsum - Acme University', $helper->getChoiceLabel($customer));
        $customer->setNumber('2023-0815');
        self::assertEquals('FOO BAR - Lorem Ipsum - Acme University - 2023-0815', $helper->getChoiceLabel($customer));
    }

    public function testGetChoiceLabelKeepsMaxLengthName(): void
    {
        $helper = $this->createSut(CustomerHelper::PATTERN_NAME);

        $name = str_repeat('a', 150);
        $customer = new Customer($name);
        self::assertEquals($name, $helper->getChoiceLabel($customer));
    }

    public function testGetChoiceLabelKeepsMaxLengthNameWithNumber(): void
    {
        $helper = $this->createSut(CustomerHelper::PATTERN_NUMBER . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_NAME);

        $name = str_repeat('a', 150);
        $customer = new Customer($name);
        $customer->setNumber('1234567890');

        $label = $helper->getChoiceLabel($customer);
        self::assertEquals('1234567890 - ' . $name, $label);
        self::assertEquals(163, \strlen($label));
    }

    public function testGetChoiceLabelIsTruncated(): void
    {
        $helper = $this->createSut(CustomerHelper::PATTERN_NAME . CustomerHelper::PATTERN_SPACER . CustomerHelper::PATTERN_COMPANY);

        $name = str_repeat('a', 150);
        $customer = new Customer($name);
        $customer->setCompany(str_repeat('b', 100));

        $label = $helper->getChoiceLabel($customer);
        self::assertEquals(180, \strlen($label));
        self::assertEquals($name . ' - ' . str_repeat('b', 27), $label);
    }
}
