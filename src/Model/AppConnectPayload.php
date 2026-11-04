<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

/**
 * The payload that is encoded into the QR code, which is shown once after an API token was created.
 *
 * This is a public contract with the Kimai apps:
 * - fields may only be added, they must never be renamed or removed
 * - VERSION has to be raised, if the meaning of an existing field changes
 *
 * The "url" field is the Kimai base URL: the very same value that a user would enter manually.
 * It never ends with a slash and it does NOT contain the "/api" path, because apps append that
 * themselves - exactly like they do it with a manually entered URL.
 *
 * Example: {"type":"kimai","version":1,"url":"https://127.0.0.1:8000","token":"6ccc2932be3a7e8fa1dd2c254"}
 */
final class AppConnectPayload
{
    public const TYPE = 'kimai';
    public const VERSION = 1;

    private readonly string $url;

    public function __construct(string $url, private readonly string $token)
    {
        $this->url = rtrim($url, '/');
    }

    public function toJson(): string
    {
        // JSON_UNESCAPED_SLASHES keeps the URL readable and the QR code as small as possible
        return json_encode([
            'type' => self::TYPE,
            'version' => self::VERSION,
            'url' => $this->url,
            'token' => $this->token,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
