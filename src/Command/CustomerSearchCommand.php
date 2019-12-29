<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\CustomerRepository;
use App\Repository\Query\CustomerQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch customer information.
 */
class CustomerSearchCommand extends Command
{
    /**
     * @var CustomerRepository
     */
    private $customers;

    public function __construct(CustomerRepository $repository)
    {
        $this->customers = $repository;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:customer:search')
            ->setDescription('Search for customers')
            ->setHelp('This command lets you search for customer')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $query = new CustomerQuery();

        $customers = $this->customers->getCustomersForQuery($query);

        $rows = [];
        foreach ($customers as $customer) {
            $rows[] = [
                $customer->getId(),
                $customer->getName(),
            ];
        }
        $io->table(['Id', 'Name'], $rows);

        return 0;
    }
}
