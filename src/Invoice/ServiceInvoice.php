<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoice;

/**
 * A service to manage the invoice configuration:
 * - invoice number generator
 * - invoice sum calculator
 * - template renderer
 */
class ServiceInvoice
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * ServiceInvoice constructor.
     * @param array $invoiceConfig
     */
    public function __construct(array $invoiceConfig)
    {
        $this->config = $invoiceConfig;
    }

    /**
     * @return array
     */
    public function getNumberGenerator()
    {
        return $this->config['number_generator'];
    }

    /**
     * @param string $name
     * @return NumberGeneratorInterface|null
     */
    public function getNumberGeneratorByName(string $name)
    {
        foreach ($this->getNumberGenerator() as $key => $class) {
            if ($key === $name) {
                return new $class();
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getCalculator()
    {
        return $this->config['calculator'];
    }

    /**
     * @param string $name
     * @return CalculatorInterface|null
     */
    public function getCalculatorByName(string $name)
    {
        foreach ($this->getCalculator() as $key => $class) {
            if ($key === $name) {
                return new $class();
            }
        }
        return null;
    }

    /**
     * Returns an array of invoice renderer, which will consist of a unique name and a controller action.
     *
     * @return array
     * @throws \Exception
     */
    public function getRenderer()
    {
        return $this->config['renderer'];
    }

    /**
     * @param $renderer
     * @return string|null
     */
    public function getRendererActionByName($renderer)
    {
        foreach ($this->config['renderer'] as $name => $action) {
            if ($name == $renderer) {
                return $action;
            }
        }
        return null;
    }
}
