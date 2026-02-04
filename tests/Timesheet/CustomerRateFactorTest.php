<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Timesheet;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\User;
use App\Entity\UserPreference;
use App\Tests\Mocks\RateServiceFactory;
use PHPUnit\Framework\TestCase;

class CustomerRateFactorTest extends TestCase
{
    private function getTestUser(float $hourlyRate = 100.0, float $internalRate = 80.0): User
    {
        $user = new User();
        $user->setPreferences([
            new UserPreference(UserPreference::HOURLY_RATE, $hourlyRate),
            new UserPreference(UserPreference::INTERNAL_RATE, $internalRate),
        ]);
        return $user;
    }

    public function testCalculateAppliesCustomerFactorToHourlyRate(): void
    {
        $customer = new Customer('Test Customer');
        $customer->setRateFactor(1.5);

        $project = new Project();
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setProject($project);

        $record = new Timesheet();
        $record->setUser($this->getTestUser(100.0, 80.0));
        $record->setActivity($activity);
        $record->setProject($project);
        $record->setBegin(new \DateTime('2024-02-04 10:00:00'));
        $record->setEnd(new \DateTime('2024-02-04 11:00:00'));
        $record->setDuration(3600);

        $factory = new RateServiceFactory($this);
        $sut = $factory->create();

        $rate = $sut->calculate($record);

        // Standard 100 EUR * 1.5 Factor = 150 EUR
        self::assertEquals(150.0, $rate->getRate(), 'Hourly rate should be multiplied by customer factor');
        // Internal 80 EUR * 1.5 Factor = 120 EUR
        self::assertEquals(120.0, $rate->getInternalRate(), 'Internal rate should be multiplied by customer factor');
    }

    public function testCalculateAppliesCustomerFactorToFixedRate(): void
    {
        $customer = new Customer('Test Customer');
        $customer->setRateFactor(2.0);

        $project = new Project();
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setProject($project);

        $record = new Timesheet();
        $record->setUser($this->getTestUser());
        $record->setActivity($activity);
        $record->setProject($project);
        $record->setFixedRate(50.0);
        $record->setEnd(new \DateTime());
        $record->setDuration(3600);

        $factory = new RateServiceFactory($this);
        $sut = $factory->create();

        $rate = $sut->calculate($record);

        // Fixed 50 EUR * 2.0 Factor = 100 EUR
        self::assertEquals(100.0, $rate->getRate(), 'Fixed rate should be multiplied by customer factor');
    }
    
    public function testCalculateWithDefaultFactor(): void
    {
        $customer = new Customer('Test Customer');
        // Default factor is 1.0

        $project = new Project();
        $project->setCustomer($customer);

        $activity = new Activity();
        $activity->setProject($project);

        $record = new Timesheet();
        $record->setUser($this->getTestUser(100.0));
        $record->setActivity($activity);
        $record->setProject($project);
        $record->setBegin(new \DateTime('2024-02-04 10:00:00'));
        $record->setEnd(new \DateTime('2024-02-04 11:00:00'));
        $record->setDuration(3600);

        $factory = new RateServiceFactory($this);
        $sut = $factory->create();

        $rate = $sut->calculate($record);

        self::assertEquals(100.0, $rate->getRate(), 'Default factor 1.0 should not change the rate');
    }
}
