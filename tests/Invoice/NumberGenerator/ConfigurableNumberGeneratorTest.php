<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Invoice\NumberGenerator;

use App\Entity\Customer;
use App\Entity\User;
use App\Invoice\NumberGenerator\ConfigurableNumberGenerator;
use App\Repository\InvoiceRepository;
use App\Tests\Invoice\DebugFormatter;
use App\Tests\Mocks\InvoiceModelFactoryFactory;
use App\Tests\Mocks\SystemConfigurationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Invoice\NumberGenerator\ConfigurableNumberGenerator
 * @covers \App\Utils\NumberGenerator
 */
class ConfigurableNumberGeneratorTest extends TestCase
{
    private function getSut(string $format, int $counter = 1): ConfigurableNumberGenerator
    {
        $config = SystemConfigurationFactory::createStub(['invoice' => ['number_format' => $format]]);

        $repository = $this->createMock(InvoiceRepository::class);
        $repository
            ->expects($this->any())
            ->method('getCounterForCustomerAllTime')
            ->willReturn($counter);
        $repository
            ->expects($this->any())
            ->method('getCounterForUserAllTime')
            ->willReturn($counter);
        $repository
            ->expects($this->any())
            ->method('getCounterForYear')
            ->willReturn($counter);
        $repository
            ->expects($this->any())
            ->method('getCounterForMonth')
            ->willReturn($counter);
        $repository
            ->expects($this->any())
            ->method('getCounterForDay')
            ->willReturn($counter);

        return new ConfigurableNumberGenerator($repository, $config);
    }

    /**
     * @return array<int, array<int, string|\DateTime|int>>
     */
    public function getTestData(): array
    {
        $invoiceDate = new \DateTime();

        return [
            // simple tests for single calls
            ['my-{date} is+cool,really', 'my-' . $invoiceDate->format('ymd') . ' is+cool,really', $invoiceDate],
            ['{date}', $invoiceDate->format('ymd'), $invoiceDate],
            ['{Y}', $invoiceDate->format('Y'), $invoiceDate],
            ['{y}', $invoiceDate->format('y'), $invoiceDate],
            ['{M}', $invoiceDate->format('m'), $invoiceDate],
            ['{m}', $invoiceDate->format('n'), $invoiceDate],
            ['{D}', $invoiceDate->format('d'), $invoiceDate],
            ['{d}', $invoiceDate->format('j'), $invoiceDate],
            ['{c}', '2', $invoiceDate],
            ['{cy}', '2', $invoiceDate],
            ['{cm}', '2', $invoiceDate],
            ['{cd}', '2', $invoiceDate],
            // customer
            ['{cc}', '2', $invoiceDate],
            ['{ccy}', '2', $invoiceDate],
            ['{ccm}', '2', $invoiceDate],
            ['{ccd}', '2', $invoiceDate],
            ['{cname}', 'Acme company', $invoiceDate],
            ['{cnumber}', '0815', $invoiceDate],
            // user
            ['{cu}', '2', $invoiceDate],
            ['{cuy}', '2', $invoiceDate],
            ['{cum}', '2', $invoiceDate],
            ['{cud}', '2', $invoiceDate],
            ['{ustaff}', '0815', $invoiceDate],
            ['{uid}', '13', $invoiceDate],
            // number formatting (not testing the lower case versions, as the tests might break depending on the date)
            ['{date,10}', '0000' . $invoiceDate->format('ymd'), $invoiceDate],
            ['{Y,6}', '00' . $invoiceDate->format('Y'), $invoiceDate],
            ['{M,3}', '0' . $invoiceDate->format('m'), $invoiceDate],
            ['{D,3}', '0' . $invoiceDate->format('d'), $invoiceDate],
            // counter across all invoices
            ['{c,2}', '02', $invoiceDate],
            ['{cy,2}', '02', $invoiceDate],
            ['{cm,2}', '02', $invoiceDate],
            ['{cd,2}', '02', $invoiceDate],
            // customer specific
            ['{cc,2}', '02', $invoiceDate],
            ['{ccy,2}', '02', $invoiceDate],
            ['{ccm,2}', '02', $invoiceDate],
            ['{ccd,2}', '02', $invoiceDate],
            // with incrementing counter
            ['{c+13,3}', '014', $invoiceDate],
            ['{c+13,2}', '14', $invoiceDate],
            ['{c+13}', '14', $invoiceDate],
            ['{cd+111}', '112', $invoiceDate],
            ['{cd+111,5}', '00112', $invoiceDate],
            ['{cd+111,2}', '113', $invoiceDate, 2],
            ['{cm+0,2}', '03', $invoiceDate, 2], // zero is not allowed and set to 1
            ['{cm+0}', '2', $invoiceDate], // zero is not allowed and set to 1
            ['{cy+2}', '3', $invoiceDate],
            ['{cc+4}', '5', $invoiceDate],
            ['{ccy+2,2}', '03', $invoiceDate],
            ['{ccm+2,2}', '03', $invoiceDate],
            ['{ccd+2,2}', '03', $invoiceDate],
            // mixing identifiers
            ['{Y}{cy}', $invoiceDate->format('Y') . '2', $invoiceDate],
            ['{Y}{cy}{m}', $invoiceDate->format('Y') . '2' . $invoiceDate->format('n'), $invoiceDate],
            ['{Y}-{cy}/{m}', $invoiceDate->format('Y') . '-2/' . $invoiceDate->format('n'), $invoiceDate],
            ['{Y}-{cy}/{m}', $invoiceDate->format('Y') . '-2/' . $invoiceDate->format('n'), $invoiceDate],
            ['{ustaff}|{cuy}_{Y}-{cy}/{m}', '0815|2_' . $invoiceDate->format('Y') . '-2/' . $invoiceDate->format('n'), $invoiceDate],
            ['{Y,5}/{cy,5}', '0' . $invoiceDate->format('Y') . '/00002', $invoiceDate],
            // with decrementing counter
            ['{c-1,2}', '00', $invoiceDate],
            ['{c-2,2}', '-1', $invoiceDate],
            // with incrementing and decrementing counter
            ['{c-5+13,1}', '9', $invoiceDate],
            ['{c+13-5,2}', '09', $invoiceDate],
            // undefined behaviour - can change at any time
            ['{cm+-1,2}', '00', $invoiceDate],
            ['{cm+-1}', '0', $invoiceDate],
            ['{cm-+1,2}', '02', $invoiceDate],
            ['{cm-+1}', '2', $invoiceDate],
            ['{a-+1+-1+++2}', '{a-+1+-1+++2}', $invoiceDate],
            ['{date+2+2+2}', $invoiceDate->format('ymd'), $invoiceDate],
            ['{cm+22+2+2-1-13-4}', '9', $invoiceDate],
            ['{cm+22+2-2-22}', '6', $invoiceDate, 5],
            ['{cm-21+22+2-2}', '6', $invoiceDate, 5],
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testGetInvoiceNumber(string $format, string $expectedInvoiceNumber, \DateTime $invoiceDate, int $counter = 1): void
    {
        $customer = new Customer('Acme company');
        $customer->setNumber('0815');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(13);
        $user->method('getAccountNumber')->willReturn('0815');

        $sut = $this->getSut($format, $counter);
        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter());
        $model->setInvoiceDate($invoiceDate);
        $model->setCustomer($customer);
        $model->setUser($user);
        $sut->setModel($model);

        $this->assertEquals($expectedInvoiceNumber, $sut->getInvoiceNumber());
        $this->assertEquals('default', $sut->getId());
    }

    /**
     * @return array<int, array<int, string|\DateTime>>
     */
    public function getInvalidTestData(): array
    {
        $invoiceDate = new \DateTime();

        return [
            ['{cm-}', $invoiceDate, 'decrement'],
            ['{cm+}', $invoiceDate, 'increment'],
            ['{cm+-}', $invoiceDate, 'decrement'],
            ['{cm-+}', $invoiceDate, 'increment'],
            ['{cy,}', $invoiceDate, 'format length'],
            ['{date,a}', $invoiceDate, 'format length'],
            ['{M,#}', $invoiceDate, 'format length'],
            ['{,a}', $invoiceDate, 'format length'],
            ['{Y,!}/{cy,o}', $invoiceDate, 'format length'],
            ['{cm,}', $invoiceDate, 'format length'],
            ['{cd+111,050}', $invoiceDate,  'format length'],
            ['{-+}', $invoiceDate, 'increment'],
            ['{+}', $invoiceDate, 'increment'],
            ['{+-}', $invoiceDate, 'decrement'],
        ];
    }

    /**
     * @dataProvider getInvalidTestData
     */
    public function testInvalidGetInvoiceNumber(string $format, \DateTime $invoiceDate, string $brokenPart): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unknown %s found', $brokenPart));

        $sut = $this->getSut($format);
        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter());
        $model->setInvoiceDate($invoiceDate);
        $model->setCustomer(new Customer('foo'));
        $sut->setModel($model);

        $sut->getInvoiceNumber();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function getMissingFieldTestData(): array
    {
        return [
            ['{Y}/{cnumber}_{ccy,3}', 'Customer has no number, replacer {cnumber} failed evaluation'],
            ['{Y}/{cname}_{ccy,3}', 'Customer has no name, replacer {cname} failed evaluation'],
        ];
    }

    /**
     * @dataProvider getMissingFieldTestData
     */
    public function testCustomerHasMissingField(string $format, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(13);
        $user->method('getAccountNumber')->willReturn('0815');

        $customer = new Customer('');
        $customer->setName(null);

        $sut = $this->getSut($format);
        $model = (new InvoiceModelFactoryFactory($this))->create()->createModel(new DebugFormatter());
        $model->setInvoiceDate(new \DateTime());
        $model->setCustomer($customer);
        $model->setUser($user);
        $sut->setModel($model);

        $sut->getInvoiceNumber();
    }
}
