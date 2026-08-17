<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\WebhookEndpointsJsonTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

#[CoversClass(WebhookEndpointsJsonTransformer::class)]
class JsonEndpointsTransformerTest extends TestCase
{
    public function testTransformEmptyString(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        self::assertSame([], $t->transform(''));
        self::assertSame([], $t->transform('[]'));
        self::assertSame([], $t->transform(null));
    }

    public function testTransformMalformedJsonYieldsEmpty(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        self::assertSame([], $t->transform('{not valid'));
        self::assertSame([], $t->transform('"just a string"'));
        self::assertSame([], $t->transform('42'));
    }

    public function testTransformDecodesAndNormalizes(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        $in = json_encode([
            ['url' => 'https://a.example.com', 'secret' => 'sa', 'events' => ['timesheet']],
            ['url' => 'https://b.example.com', 'events' => ['customer', 'invoice']],
            'not-an-object',
        ], \JSON_THROW_ON_ERROR);

        $out = $t->transform($in);

        self::assertCount(2, $out);
        self::assertSame('https://a.example.com', $out[0]['url']);
        self::assertSame('sa', $out[0]['secret']);
        self::assertSame(['timesheet'], $out[0]['events']);
        self::assertSame('https://b.example.com', $out[1]['url']);
        self::assertSame('', $out[1]['secret']);
        self::assertSame(['customer', 'invoice'], $out[1]['events']);
    }

    public function testReverseTransformEncodesArray(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        $result = $t->reverseTransform([
            ['url' => 'https://a.example.com', 'secret' => 'sa', 'events' => ['timesheet']],
            // skipped due to empty secret
            ['url' => '  https://b.example.com  ', 'secret' => '', 'events' => ['customer']],
            // skipped due to empty URL
            ['url' => '   ', 'secret' => 'asdasdf', 'events' => ['customer']],
            // trimmed URL
            ['url' => '  https://b.example.com  ', 'secret' => '1wefd', 'events' => ['customer']],
        ]);

        $decoded = json_decode($result, true);
        self::assertIsArray($decoded);
        self::assertCount(2, $decoded);

        self::assertIsArray($decoded[0]);
        self::assertArrayHasKey('url', $decoded[0]);
        self::assertSame('https://a.example.com', $decoded[0]['url']);

        self::assertIsArray($decoded[1]);
        self::assertArrayHasKey('url', $decoded[1]);
        self::assertSame('https://b.example.com', $decoded[1]['url']);
    }

    public function testReverseTransformNullReturnsEmptyArray(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        self::assertSame('[]', $t->reverseTransform(null));
    }

    public function testReverseTransformRejectsNonArray(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        $this->expectException(TransformationFailedException::class);
        $t->reverseTransform('not an array');
    }

    public function testTransformArrayPassthroughNormalizesLikeJsonPath(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        $raw = [
            ['url' => 'https://a.example.com', 'events' => ['timesheet', 42, 'customer', null]],
            'not-an-object',
            ['url' => 'https://b.example.com'],
        ];
        $out = $t->transform($raw);

        self::assertCount(2, $out, 'Non-array entries must be dropped on passthrough too');
        self::assertSame('https://a.example.com', $out[0]['url']);
        self::assertSame('', $out[0]['secret']);
        self::assertSame(['timesheet', 'customer'], $out[0]['events'], 'Non-string event items must be filtered out');
        self::assertSame('https://b.example.com', $out[1]['url']);
        self::assertSame([], $out[1]['events']);
    }

    public function testTransformArrayPassthroughMatchesJsonDecodeBehavior(): void
    {
        $t = new WebhookEndpointsJsonTransformer();
        $data = [
            ['url' => 'https://a.example.com', 'secret' => 's', 'events' => ['timesheet']],
            ['url' => 'https://b.example.com', 'secret' => 't', 'events' => ['invoice']],
        ];
        $jsonPath = $t->transform(json_encode($data, \JSON_THROW_ON_ERROR));
        $arrayPath = $t->transform($data);

        self::assertEquals($jsonPath, $arrayPath);
    }
}
