<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

/**
 * @template T
 */
interface LoaderInterface
{
    /**
     * Prepares the given database results, to prevent lazy loading.
     *
     * @param array<array-key, T> $results
     */
    public function loadResults(array $results): void;
}
