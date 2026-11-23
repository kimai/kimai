<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Repository;

use App\Entity\Customer;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ProjectRepository::class)]
#[Group('integration')]
class ProjectRepositoryTest extends AbstractRepositoryTestCase
{
    private function createProject(\DateTimeImmutable $lockedUntil, string $suffix): int
    {
        $em = $this->getEntityManager();

        $customer = new Customer('lock customer ' . $suffix);
        $customer->setCountry('DE');
        $customer->setCurrency('EUR');
        $customer->setTimezone(date_default_timezone_get());
        $em->persist($customer);

        $project = new Project();
        $project->setName('lock project ' . $suffix);
        $project->setCustomer($customer);
        $project->setLockedUntil($lockedUntil);
        $em->persist($project);
        $em->flush();

        $id = $project->getId();
        self::assertIsInt($id);

        return $id;
    }

    /**
     * @return \Generator<array{0: string, 1: string}>
     */
    public static function getTimezones(): \Generator
    {
        yield ['UTC', '00:00:00'];
        yield ['Europe/Vienna', '23:59:59'];
        yield ['Europe/Vienna', '00:00:00'];
        yield ['Pacific/Tahiti', '23:59:59'];
        yield ['Pacific/Tahiti', '00:00:00'];
        yield ['Pacific/Kiritimati', '23:59:59'];
        yield ['Asia/Tokyo', '12:00:00'];
    }

    /**
     * The lock date is a calendar day: neither the time nor the timezone that is handed over
     * may move it to another day, no matter which timezone the reading user works in.
     */
    #[DataProvider('getTimezones')]
    public function testLockedUntilIsStoredAsPlainDay(string $timezone, string $time): void
    {
        $default = date_default_timezone_get();

        try {
            date_default_timezone_set($timezone);
            $id = $this->createProject(
                new \DateTimeImmutable('2026-08-06 ' . $time, new \DateTimeZone($timezone)),
                $timezone . $time
            );

            $em = $this->getEntityManager();

            // the raw database value must be the plain day
            $raw = $em->getConnection()->fetchOne('SELECT locked_until FROM kimai2_projects WHERE id = ?', [$id]);
            self::assertEquals('2026-08-06', $raw);

            // and it must read back as the same day from every timezone
            foreach (['UTC', 'Europe/Vienna', 'Pacific/Tahiti', 'Pacific/Kiritimati'] as $readerTimezone) {
                $em->clear();
                date_default_timezone_set($readerTimezone);

                $project = $em->getRepository(Project::class)->find($id);
                self::assertInstanceOf(Project::class, $project);
                self::assertEquals('2026-08-06', $project->getLockedUntil()?->format('Y-m-d'), $readerTimezone);

                $zone = new \DateTimeZone($readerTimezone);
                self::assertTrue($project->isLockedAtDate(new \DateTime('2026-08-06 23:00:00', $zone)), $readerTimezone);
                self::assertFalse($project->isLockedAtDate(new \DateTime('2026-08-07 00:00:00', $zone)), $readerTimezone);
            }
        } finally {
            date_default_timezone_set($default);
        }
    }
}
