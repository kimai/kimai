<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DatatableExtensions extends AbstractExtension
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var array
     */
    protected $cookies = [];

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('is_visible_column', [$this, 'isColumnVisible']),
            new TwigFunction('is_datatable_configured', [$this, 'isDatatableConfigured']),
        ];
    }

    /**
     * @param string $dataTable
     * @return bool
     */
    public function isDatatableConfigured(string $dataTable)
    {
        $cookie = $this->getVisibilityCookieName($dataTable);

        return $this->requestStack->getCurrentRequest()->cookies->has($cookie);
    }

    /**
     * @param string $dataTable
     * @return string
     */
    protected function getVisibilityCookieName(string $dataTable)
    {
        return $dataTable . '_visibility';
    }

    /**
     * This is only for datatables, do not use it outside this context.
     *
     * @param string $dataTable
     * @param string $column
     * @param array $columns
     * @return bool
     */
    public function isColumnVisible(string $dataTable, string $column, array $columns)
    {
        // name handling is spread between here and datatables.html.twig (data_table_column_modal)
        $cookie = $this->getVisibilityCookieName($dataTable);

        if (!isset($this->cookies[$cookie])) {
            $visibility = false;
            if ($this->requestStack->getCurrentRequest()->cookies->has($cookie)) {
                $visibility = json_decode($this->requestStack->getCurrentRequest()->cookies->get($cookie), true);
            }
            $this->cookies[$cookie] = $visibility;
        }
        $values = $this->cookies[$cookie];

        if (empty($values) || !\is_array($values)) {
            return $this->checkInColumDefinition($columns, $column);
        }

        if (!isset($values[$column])) {
            return $this->checkInColumDefinition($columns, $column);
        }

        if ($values[$column] === false) {
            return false;
        }

        return true;
    }

    private function checkInColumDefinition(array $columns, string $column)
    {
        if (\array_key_exists($column, $columns)) {
            $tmp = $columns[$column];
            if (\is_array($tmp)) {
                $tmp = $tmp['class'];
            }
            foreach (explode(' ', $tmp) as $class) {
                if ($class === 'hidden') {
                    return false;
                }
            }
        }

        return true;
    }
}
