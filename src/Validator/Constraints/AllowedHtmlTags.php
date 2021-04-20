<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class AllowedHtmlTags extends Constraint
{
    public const DISALLOWED_TAGS_FOUND = 'kimai-allowed-html-tags-00';

    public $tags;

    protected static $errorNames = [
        self::DISALLOWED_TAGS_FOUND => 'The given value contains disallowed HTML tags.',
    ];

    public $message = 'This string contains invalid HTML tags.';

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'tags';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['tags'];
    }
}
