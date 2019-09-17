<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository\Loader;

final class DefaultLoader implements LoaderInterface
{
    /**
     * @param array $results
     */
    public function loadResults(array $results): void
    {
        // nothing to do here, the results are already fully populated

        // if your entities have lazy collections or other data that needs population,
        // consider to create a custom loader!
    }
}
