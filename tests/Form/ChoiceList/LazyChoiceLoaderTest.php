<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\ChoiceList;

use App\Form\ChoiceList\LazyChoiceLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;

#[CoversClass(LazyChoiceLoader::class)]
class LazyChoiceLoaderTest extends TestCase
{
    private \stdClass $first;
    private \stdClass $second;
    private LazyChoiceLoader $sut;

    protected function setUp(): void
    {
        $this->first = (object) ['id' => 1];
        $this->second = (object) ['id' => 2];
        $choices = [$this->first, $this->second];

        $this->sut = new LazyChoiceLoader(new CallbackChoiceLoader(fn () => $choices));
    }

    private function getValue(): \Closure
    {
        return fn (?\stdClass $choice) => $choice === null ? '' : (string) $choice->id;
    }

    public function testListIsEmptyWithoutData(): void
    {
        self::assertSame([], $this->sut->loadChoiceList($this->getValue())->getChoices());
    }

    public function testListContainsOnlyTheDataChoice(): void
    {
        self::assertSame(['2'], $this->sut->loadValuesForChoices([$this->second], $this->getValue()));
        self::assertSame(['2' => $this->second], $this->sut->loadChoiceList($this->getValue())->getChoices());
    }

    public function testListIsEmptyForNullData(): void
    {
        self::assertSame([''], $this->sut->loadValuesForChoices([null], $this->getValue()));
        self::assertSame([], $this->sut->loadChoiceList($this->getValue())->getChoices());
    }

    public function testSubmittedValueIsResolvedByTheDecoratedLoader(): void
    {
        // never listed, but known to the decorated loader
        $this->sut->loadValuesForChoices([$this->second], $this->getValue());

        self::assertSame([$this->first], $this->sut->loadChoicesForValues(['1'], $this->getValue()));
        self::assertSame(['1' => $this->first], $this->sut->loadChoiceList($this->getValue())->getChoices());
    }

    public function testUnknownSubmittedValueIsRejected(): void
    {
        self::assertSame([], $this->sut->loadChoicesForValues(['3'], $this->getValue()));
        self::assertSame([], $this->sut->loadChoiceList($this->getValue())->getChoices());
    }
}
