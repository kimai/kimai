<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form\ChoiceList;

/**
 * A form type supporting the "lazy_load" option.
 */
interface LazyChoiceTypeInterface
{
    /**
     * The API search; after a parent change the parent's reload URL is used with the order of these params.
     *
     * - parent_param: parent filter, also the parent field in API results and choice attributes, e.g. "customer"
     * - parent: parent entity or ID, e.g. the "customers" option
     * - all_without_parent: whether the reload without parent lists all choices or only some (global activities)
     *
     * @param array<string, mixed> $options
     * @return array{route: string, params: array<string, string|int>, parent_param: string, parent: mixed, all_without_parent: bool}
     */
    public function getLazyLoadApi(array $options): array;
}
