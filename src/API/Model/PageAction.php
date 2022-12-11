<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\API\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * @internal
 */
#[Serializer\ExclusionPolicy('all')]
final class PageAction
{
    /**
     * ID of the action
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    public readonly string $id;
    /**
     * Translated title to show the user
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    public readonly string $title;
    /**
     * URL of the action
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    public readonly ?string $url;
    /**
     * HTML classes to be used
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    public readonly ?string $class;
    /**
     * HTML (data) attributes to render the action
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'array<string, string>')]
    public readonly array $attr;
    /**
     * Whether to render a divider before this item
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    public readonly bool $divider;

    public function __construct(string $title, array $settings = [])
    {
        $this->id = $title;
        $this->title = $settings['title'] ?? $title;
        $this->url = $settings['url'] ?? null;
        $this->class = $settings['class'] ?? null;
        $this->attr = $settings['attr'] ?? [];

        $this->divider = ($title === 'trash' || (str_contains($title, 'divider') && ($this->url === null || $this->url === '')));
    }
}
