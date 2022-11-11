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
class PageAction
{
    /**
     * ID of the action
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private ?string $id = null;
    /**
     * Translated title to show the user
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private ?string $title = null;
    /**
     * URL of the action
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private ?string $url = null;
    /**
     * HTML classes to be used
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'string')]
    private ?string $class = null;
    /**
     * HTML (data) attributes to render the action
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'array<string, string>')]
    private array $attr = [];
    /**
     * Whether to render a divider before this item
     */
    #[Serializer\Expose]
    #[Serializer\Groups(['Default'])]
    #[Serializer\Type(name: 'boolean')]
    private bool $divider = false;

    public function __construct(string $title, ?array $settings = null)
    {
        $this->id = $title;
        if ($settings !== null) {
            $this->title = $settings['title'] ?? $title;
            $this->url = $settings['url'] ?? '';
            $this->class = $settings['class'] ?? '';
            $this->attr = $settings['attr'] ?? [];
        }
        if ($title === 'trash' || (str_contains($title, 'divider') && empty($this->url))) {
            $this->divider = true;
        }
    }
}
