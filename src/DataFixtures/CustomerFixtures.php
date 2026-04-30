<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Customer;
use App\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests or while development.
 *
 * Execute this command to load the data:
 * bin/console doctrine:fixtures:load --group customer --append
 *
 * @codeCoverageIgnore
 */
final class CustomerFixtures extends Fixture implements FixtureGroupInterface
{
    private const int MIN_BUDGET = 0;
    private const int MAX_BUDGET = 100000;
    private const int MIN_TIME_BUDGET = 0;
    private const int MAX_TIME_BUDGET = 10000000;
    private const int MIN_GLOBAL_ACTIVITIES = 5;
    private const int MAX_GLOBAL_ACTIVITIES = 30;
    private const int MIN_PROJECTS_PER_CUSTOMER = 2;
    private const int MAX_PROJECTS_PER_CUSTOMER = 25;
    private const int MIN_ACTIVITIES_PER_PROJECT = 0;
    private const int MAX_ACTIVITIES_PER_PROJECT = 25;
    /** @var array<int, list<string>> */
    private array $countrySetups = [
         ['at_AT', 'AT', 'de_AT', 'EUR'],
         ['de_DE', 'DE', 'de', 'EUR'],
         ['es_ES', 'ES', 'es', 'EUR'],
         ['fr_FR', 'FR', 'fr', 'EUR'],
         ['it_IT', 'IT', 'it', 'EUR'],
         ['nl_NL', 'NL', 'nl', 'EUR'],
         ['pt_PT', 'PT', 'pt', 'EUR'],
         ['fr_BE', 'BE', 'en', 'EUR'],
         ['bg_BG', 'BG', 'bg', 'EUR'],
         ['fi_FI', 'FI', 'fi', 'EUR'],
         ['el_GR', 'GR', 'el', 'EUR'],
         ['en_GB', 'IE', 'en_IE', 'EUR'],
         ['de_DE', 'LU', 'de_LU', 'EUR'],
         ['et_EE', 'EE', 'en', 'EUR'],
         ['hr_HR', 'HR', 'hr', 'EUR'],
         ['lv_LV', 'LV', 'en', 'EUR'],
         ['lt_LT', 'LT', 'en', 'EUR'],
         ['en_US', 'MT', 'en', 'EUR'],
         ['sk_SK', 'SK', 'sk', 'EUR'],
         ['si_SI', 'SI', 'sl', 'EUR'],
         ['he_IL', 'IL', 'he', 'ILS'],
         ['hu_HU', 'HU', 'hu', 'HUF'],
         ['pl_PL', 'PL', 'pl', 'PLN'],
         ['da_DK', 'DK', 'da', 'DKK'],
         ['de_CH', 'CH', 'de_CH', 'CHF'],
         ['en_US', 'US', 'en', 'USD'],
         ['en_GB', 'GB', 'en_GB', 'GBP'],
         ['ru_RU', 'RU', 'ru_RU', 'RUB'],
         ['uk_UA', 'UA', 'uk', 'UAH'],
         ['en_AU', 'AU', 'en_AU', 'AUD'],
         ['zh_CN', 'CN', 'zh_CN', 'CNY'],
         ['zh_CN', 'CN', 'zh_Hant', 'CNY'],
         ['ja_JP', 'JP', 'ja', 'JPY'],
         ['nb_NO', 'NO', 'nb_NO', 'NOK'],
         ['pt_BR', 'BR', 'pt_BR', 'BRL'],
         ['ro_RO', 'RO', 'ro', 'RON'],
         ['vi_VN', 'VN', 'vi', 'VND'],
         ['sv_SE', 'SE', 'sv', 'SEK'],
         ['tr_TR', 'TR', 'tr', 'TRY'],
         ['ko_KR', 'KR', 'ko', 'KRW'],
         ['cs_CZ', 'CZ', 'cs', 'CZK'],
         ['id_ID', 'ID', 'id', 'IDR'],
         ['en_IN', 'IN', 'ta', 'INR'],
        // these make no sense, the language is currently not used with customers
         // ['da_DK', 'FO', 'fo', 'DKK'], // Faroese < 50%
         // ['en_IN', 'IN', 'pa', 'INR'], // Punjabi 10%
         // ['', '', 'ar', ''], // Arabic > 90%
         // ['', '', 'eo', ''], // Esperanto > 50%
         // ['', '', 'fa', ''], // Persian > 95%
         // ['', 'ES', 'eu', ''], // Basque < 50%
    ];

    public function load(ObjectManager $manager): void
    {
        shuffle($this->countrySetups);
        $amountCustomers = \count($this->countrySetups);

        for ($c = 1; $c <= $amountCustomers; $c++) {
            $countrySetup = $this->countrySetups[$c - 1];
            $faker = Factory::create($countrySetup[0]);
            $visibleCustomer = 0 !== $c % 5;

            $customer = $this->createCustomer($faker, $visibleCustomer, $countrySetup[1], $countrySetup[3]);
            $manager->persist($customer);

            $projectForCustomer = rand(self::MIN_PROJECTS_PER_CUSTOMER, self::MAX_PROJECTS_PER_CUSTOMER);
            for ($p = 1; $p <= $projectForCustomer; $p++) {
                $visibleProject = 0 !== $p % 7;
                $project = $this->createProject($faker, $customer, $visibleProject);
                $manager->persist($project);

                $activityForProject = rand(self::MIN_ACTIVITIES_PER_PROJECT, self::MAX_ACTIVITIES_PER_PROJECT);
                for ($a = 1; $a <= $activityForProject; $a++) {
                    $visibleActivity = 0 !== $a % 6;
                    $activity = $this->createActivity($faker, $project, $visibleActivity);
                    $manager->persist($activity);
                }
            }

            $manager->flush();
            $manager->clear();
        }

        $faker = Factory::create('en_US');

        $amountGlobalActivities = rand(self::MIN_GLOBAL_ACTIVITIES, self::MAX_GLOBAL_ACTIVITIES);
        for ($c = 1; $c <= $amountGlobalActivities; $c++) {
            $visibleActivity = 0 !== $c % 4;
            $activity = $this->createActivity($faker, null, $visibleActivity);
            $manager->persist($activity);
        }

        $manager->flush();
        $manager->clear();
    }

    private function createCustomer(Generator $faker, bool $visible, string $countryCode, string $currency): Customer
    {
        $entry = new Customer($faker->company());
        $entry->setCurrency($currency);
        $entry->setAddress($faker->address());
        $entry->setEmail($faker->safeEmail());
        $entry->setComment($faker->text());
        $entry->setNumber('C-' . $faker->ean8());
        $entry->setCountry($countryCode);
        $entry->setTimezone($faker->timezone());
        $entry->setVisible($visible);
        try {
            $entry->setVatId($faker->vat(false)); // @phpstan-ignore method.notFound
        } catch (\InvalidArgumentException $e) {
            $entry->setVatId($faker->creditCardNumber());
        }
        $entry->setPostCode($faker->postcode());
        $entry->setCity($faker->city());
        $entry->setAddressLine1($faker->streetAddress());
        $entry->setAddressLine2($faker->streetAddress());

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    private function createProject(Generator $faker, Customer $customer, bool $visible): Project
    {
        $entry = new Project();

        /** @var string $name */
        $name = $faker->words(2, true);

        $entry->setName(ucfirst($name));
        $entry->setComment($faker->text());
        $entry->setCustomer($customer);
        $entry->setOrderNumber('P-' . $faker->ean8());
        $entry->setVisible($visible);

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    private function createActivity(Generator $faker, ?Project $project, bool $visible): Activity
    {
        /** @var string $name */
        $name = $faker->words(2, true);

        $entry = new Activity();
        $entry->setName(ucfirst($name));
        $entry->setProject($project);
        $entry->setComment($faker->text());
        $entry->setVisible($visible);

        if (rand(0, 3) % 3) {
            $entry->setBudget(rand(self::MIN_BUDGET, self::MAX_BUDGET));
        }

        if (rand(0, 3) % 3) {
            $entry->setTimeBudget(rand(self::MIN_TIME_BUDGET, self::MAX_TIME_BUDGET));
        }

        return $entry;
    }

    public static function getGroups(): array
    {
        return ['customer'];
    }
}
