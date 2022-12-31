<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Constants;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

/**
 * Code inspired by https://github.com/consolidation/self-update (MIT license - 03 Sept. 2022)
 */
final class ReleaseVersion
{
    /**
     * Get all releases from GitHub.
     *
     * @throws \Exception
     * @return array
     */
    private function getReleasesFromGithub(): array
    {
        $versionParser = new VersionParser();
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: ' . Constants::SOFTWARE . ' ' . Constants::VERSION . ' Update-Check (PHP)',
                ],
            ],
        ];

        $context = stream_context_create($opts);

        $releases = file_get_contents('https://api.github.com/repos/' . Constants::GITHUB_REPO . '/releases', false, $context);
        $releases = json_decode($releases);

        if (!isset($releases[0])) {
            throw new \Exception('API error - no release found at GitHub repository: ' . Constants::GITHUB_REPO);
        }
        $parsed = [];
        foreach ($releases as $release) {
            if ($release->draft || $release->prerelease) {
                continue;
            }

            try {
                $normalized = $versionParser->normalize($release->tag_name);
            } catch (\UnexpectedValueException $e) {
                continue;
            }

            if (VersionParser::parseStability($normalized) !== 'stable') {
                continue;
            }

            $date = $release->published_at;
            try {
                $date = new \DateTimeImmutable($date);
            } catch (\Exception $ex) {
                // can be ignored, we return a string
            }

            $parsed[$normalized] = [
                'version' => $release->tag_name,
                'date' => $date,
                'url' => $release->html_url,
                'download' => $release->zipball_url,
                'content' => $release->body,
            ];
        }
        $versions = Semver::rsort(array_keys($parsed));
        $releases = [];
        foreach ($versions as $version) {
            $releases[$version] = $parsed[$version];
        }

        return $releases;
    }

    /**
     * Returns an array with the keys:
     * - version (string, tag name)
     * - date (string, release date)
     * - url (string, web address)
     * - download (string, ZIP URL)
     * - content (string, release notes)
     *
     * @param bool $compatible
     * @return array|null
     * @throws \Exception
     */
    public function getLatestReleaseFromGithub(bool $compatible): ?array
    {
        foreach ($this->getReleasesFromGithub() as $release) {
            $releaseVersion = $release['version'];
            if ($compatible && !$this->satisfiesMajorVersionConstraint($releaseVersion)) {
                continue;
            }

            return $release;
        }

        return null;
    }

    private function satisfiesMajorVersionConstraint(string $releaseVersion): bool
    {
        if (preg_match('/^v?(\d+)/', Constants::VERSION, $matches)) {
            return Semver::satisfies($releaseVersion, '^' . $matches[1]);
        }

        return false;
    }
}
