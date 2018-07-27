<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Calendar;

class Service
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Service constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return new Config($this->config);
    }

    /**
     * @return Google
     */
    public function getGoogle()
    {
        $apiKey = $this->config['google']['api_key'] ?? null;
        $sources = [];

        if (isset($this->config['google']['sources'])) {
            foreach ($this->config['google']['sources'] as $name => $config) {
                $source = new Source();
                $source
                    ->setColor($config['color'])
                    ->setUri($config['id'])
                    ->setId($name)
                ;

                $sources[] = $source;
            }
        }

        return new Google($apiKey, $sources);
    }
}
