<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\License;

use ParagonIE\Halite\KeyFactory;

class LocalLicenseKey implements LicenseKeyInterface
{
    /**
     * @var string
     */
    protected $projectDirectory;

    /**
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @return string
     */
    private function getKeyDirectory(): string
    {
        return realpath($this->projectDirectory . '/var/data/');
    }

    /**
     * @param bool $private
     * @return string
     */
    private function getFullFilenameForKey($private = false): string
    {
        $dir = $this->getKeyDirectory();

        if ($private) {
            return $dir . '/kimai-private.key';
        }

        return $dir . '/kimai-public.key';
    }

    /**
     * @throws LicenseException
     */
    protected function generate()
    {
        $public = $this->getFullFilenameForKey(false);
        $private = $this->getFullFilenameForKey(true);

        if (file_exists($public) && is_readable($public) && file_exists($private) && is_readable($private)) {
            return;
        }

        if (file_exists($public) && !is_readable($public)) {
            throw new LicenseException('Cannot read public key at ' . $public);
        }

        if (file_exists($private) && !is_readable($private)) {
            throw new LicenseException('Cannot read private key at ' . $private);
        }

        if (!file_exists($public) && file_exists($private)) {
            throw new LicenseException('You have a private key, but no public');
        }

        if (file_exists($public) && !file_exists($private)) {
            throw new LicenseException('You have a public key, but no private');
        }

        $dir = $this->getKeyDirectory();

        if (!file_exists($public) && !is_writable($dir)) {
            throw new LicenseException('Cannot create public key at ' . $dir);
        }

        if (!file_exists($private) && !is_writable($dir)) {
            throw new LicenseException('Cannot create private key at ' . $dir);
        }

        try {
            $keypair = KeyFactory::generateEncryptionKeyPair();
            KeyFactory::save($keypair->getPublicKey(), $public);
            KeyFactory::save($keypair->getSecretKey(), $private);
        } catch (\Exception $ex) {
            throw new LicenseException('Failed creating public/private key for license management', $ex->getCode(), $ex);
        }
    }

    /**
     * @return string
     * @throws LicenseException
     */
    public function getPublicKey(): string
    {
        $this->generate();

        try {
            $key = KeyFactory::loadEncryptionPublicKey(
                $this->getFullFilenameForKey(false)
            );
        } catch (\Exception $ex) {
            throw new LicenseException('Failed reading public license key', $ex->getCode(), $ex);
        }

        return sodium_bin2hex($key->getRawKeyMaterial());
    }

    /**
     * @param $message
     * @return string
     * @throws LicenseException
     */
    public function decrypt($message): string
    {
        try {
            $decrypted = \ParagonIE\Halite\Asymmetric\Crypto::decrypt(
                $message,
                KeyFactory::loadEncryptionSecretKey($this->getFullFilenameForKey(true)),
                // FIXME find a better way to store public cloud key
                KeyFactory::loadEncryptionPublicKey(__DIR__ . '/kimai-cloud-public.key')
            );
        } catch (\Exception $ex) {
            throw new LicenseException('Could not decrypt message', $ex->getCode(), $ex);
        }

        return $decrypted->getString();
    }
}
