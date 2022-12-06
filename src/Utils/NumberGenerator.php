<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

final class NumberGenerator
{
    /**
     * @param string $format
     * @param callable $patternReplacer (receives the parameters: string $originalFormat, string $format, int $increaseBy)
     */
    public function __construct(private string $format, private $patternReplacer)
    {
    }

    public function getNumber(int $startWith = 0): string
    {
        $result = $this->format;

        preg_match_all('/{[^}]*?}/', $result, $matches);

        foreach ($matches[0] as $part) {
            $partialResult = $this->parseReplacer($part, $startWith);
            $result = str_replace($part, $partialResult, $result);
        }

        return $result;
    }

    private function parseReplacer(string $originalFormat, int $increaseBy): string
    {
        $formatterLength = null;
        $formatPattern = str_replace(['{', '}'], '', $originalFormat);

        $parts = preg_split('/([+\-,])+/', $formatPattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new \InvalidArgumentException('Invalid number format received');
        }
        $format = array_shift($parts);

        if (\count($parts) % 2 !== 0) {
            throw new \InvalidArgumentException('Invalid number format configuration found');
        }

        while (null !== ($tmp = array_shift($parts))) {
            switch ($tmp) {
                case '+':
                    $local = array_shift($parts);
                    if (!is_numeric($local)) {
                        throw new \InvalidArgumentException('Unknown increment found');
                    }
                    $increaseBy = $increaseBy + \intval($local);
                    break;

                case '-':
                    $local = array_shift($parts);
                    if (!is_numeric($local)) {
                        throw new \InvalidArgumentException('Unknown decrement found');
                    }
                    $increaseBy = $increaseBy - \intval($local);
                    break;

                case ',':
                    $local = array_shift($parts);
                    if (!is_numeric($local)) {
                        throw new \InvalidArgumentException('Unknown format length found');
                    }
                    $formatterLength = \intval($local);
                    if ((string) $formatterLength !== $local) {
                        throw new \InvalidArgumentException('Unknown format length found');
                    }
                    break;

                default:
                    throw new \InvalidArgumentException('Unknown pattern found');
            }
        }

        if ($increaseBy === 0) {
            $increaseBy = 1;
        }

        $partialResult = \call_user_func($this->patternReplacer, $originalFormat, $format, $increaseBy);

        if (!\is_string($partialResult) && !\is_int($partialResult) && !\is_float($partialResult)) {
            throw new \Exception('Number generator callback must return string or integer');
        }

        $partialResult = (string) $partialResult;

        if (null !== $formatterLength) {
            $partialResult = str_pad($partialResult, $formatterLength, '0', STR_PAD_LEFT);
        }

        return $partialResult;
    }
}
