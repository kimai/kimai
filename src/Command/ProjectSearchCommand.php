<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\ProjectRepository;
use App\Repository\Query\ProjectQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command used to fetch project information.
 */
class ProjectSearchCommand extends Command
{
    /**
     * @var ProjectRepository
     */
    private $projects;

    public function __construct(ProjectRepository $repository)
    {
        $this->projects = $repository;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:project:search')
            ->setDescription('Search for projects')
            ->setHelp('This command lets you search for projects')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, 'The customer to be filtered', null)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $query = new ProjectQuery();
        $query->setOrderBy('customer');

        if (null !== $input->getOption('customer')) {
            $query->setCustomer($input->getOption('customer'));
        }

        $projects = $this->projects->getProjectsForQuery($query);

        $rows = [];
        foreach ($projects as $project) {
            $customer = $project->getCustomer();

            $rows[] = [
                $project->getId(),
                $project->getName(),
                '[' . $customer->getId() . '] ' . $customer->getName(),
            ];
        }
        $io->table(['Id', 'Name', '[ID] Customer'], $rows);

        return 0;
    }
}
