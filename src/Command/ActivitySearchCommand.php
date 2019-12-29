<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\ActivityRepository;
use App\Repository\Query\ActivityQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch activity information.
 */
class ActivitySearchCommand extends Command
{
    /**
     * @var ActivityRepository
     */
    private $activities;

    public function __construct(ActivityRepository $repository)
    {
        $this->activities = $repository;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:activity:search')
            ->setDescription('Search for activities')
            ->setHelp('This command lets you search for activities')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'The customer to be filtered', null)
            ->addOption('project', null, InputOption::VALUE_OPTIONAL, 'The project to be filtered', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $query = new ActivityQuery();
        $query->setOrderBy('customer');

        if (null !== $input->getOption('customer')) {
            $query->setCustomer($input->getOption('customer'));
        }

        if (null !== $input->getOption('project')) {
            $query->setProject($input->getOption('project'));
        }

        $activities = $this->activities->getActivitiesForQuery($query);

        $rows = [];
        foreach ($activities as $activity) {
            $project = '[-] -';
            $customer = '[-] -';
            if (null !== ($prj = $activity->getProject())) {
                $project = '[' . $prj->getId() . '] ' . $prj->getName();
                $customer = '[' . $prj->getCustomer()->getId() . '] ' . $prj->getCustomer()->getName();
            }
            $rows[] = [
                $activity->getId(),
                $activity->getName(),
                $customer,
                $project
            ];
        }
        $io->table(['Id', 'Name', '[ID] Customer', '[ID] Project'], $rows);

        return 0;
    }
}
