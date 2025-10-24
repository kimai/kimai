<?php

/*
 * This file is part of the "Customer-Portal plugin" for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace KimaiPlugin\CustomerPortalBundle\Service;

use KimaiPlugin\CustomerPortalBundle\Entity\SharedProjectTimesheet;
use KimaiPlugin\CustomerPortalBundle\Repository\SharedProjectTimesheetRepository;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class ManageService
{
    public const PASSWORD_DO_NOT_CHANGE_VALUE = '__DO_NOT_CHANGE__';

    public function __construct(
        private readonly SharedProjectTimesheetRepository $repository,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory
    )
    {
    }

    public function create(SharedProjectTimesheet $sharedPortal, ?string $password = null): SharedProjectTimesheet
    {
        if ($sharedPortal->getShareKey() === null) {
            $i = 0;
            do {
                $newKey = substr(preg_replace('/[^A-Za-z0-9]+/', '', $this->getUuidV4()), 0, 20); // @phpstan-ignore argument.type
                $existingEntry = $this->repository->findByShareKey($newKey);

                // make sure we exit in case we cannot generate a new key
                if ($i++ > 50 && $existingEntry !== null) {
                    throw new \RuntimeException('Could not create unique share key');
                }
            } while ($existingEntry !== null);
            $sharedPortal->setShareKey($newKey);
        }

        return $this->update($sharedPortal, $password);
    }

    public function update(SharedProjectTimesheet $sharedProjectTimesheet, ?string $newPassword = null): SharedProjectTimesheet
    {
        // Check if updatable
        if ($sharedProjectTimesheet->getShareKey() === null) {
            throw new \InvalidArgumentException('Cannot update shared project timesheet with share key equals null');
        }

        // Handle password
        $currentHashedPassword = $sharedProjectTimesheet->hasPassword() ? $sharedProjectTimesheet->getPassword() : null;

        if ($newPassword !== self::PASSWORD_DO_NOT_CHANGE_VALUE) {
            if (\is_string($newPassword) && $newPassword !== '') {
                $encodedPassword = $this->passwordHasherFactory->getPasswordHasher('customer_portal')->hash($newPassword);
                $sharedProjectTimesheet->setPassword($encodedPassword);
            } else {
                $sharedProjectTimesheet->setPassword(null);
            }
        } else {
            $sharedProjectTimesheet->setPassword($currentHashedPassword);
        }

        $this->repository->save($sharedProjectTimesheet);

        return $sharedProjectTimesheet;
    }

    /**
     * @see https://www.php.net/manual/en/function.uniqid.php#94959
     */
    private function getUuidV4(): string
    {
        return \sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
