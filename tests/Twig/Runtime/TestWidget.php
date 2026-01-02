<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Twig\Runtime;

use App\Widget\Type\AbstractWidget;

class TestWidget extends AbstractWidget
{
    /**
     * @var non-empty-string
     */
    private string $id = 'demo';
    /**
     * @var non-empty-string
     */
    private string $title = 'Demo';

    /**
     * @param non-empty-string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getData(array $options = []): mixed
    {
        return array_merge([
            'data' => null,
            'options' => $options
        ]);
    }
}
