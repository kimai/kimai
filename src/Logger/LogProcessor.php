<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;

final class LogProcessor
{
    #[AsMonologProcessor]
    public function __invoke(LogRecord $record): LogRecord
    {
        if (\array_key_exists('bundle', $record->context)) {
            $record->extra['channel'] = strtoupper($record->context['bundle']);
        } else {
            $record->extra['channel'] = $record->channel;
        }

        return $record;
    }
}
