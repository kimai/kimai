<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

class Google
{
    /**
     * @var Source[]
     */
    protected $sources = [];
    /**
     * @var string
     */
    protected $apiKey = null;

    /**
     * @param string $apiKey
     * @param Source[] $sources
     */
    public function __construct($apiKey, $sources = [])
    {
        $this->apiKey = $apiKey;
        $this->sources = $sources;
    }

    /**
     * @return Source[]
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }
}
