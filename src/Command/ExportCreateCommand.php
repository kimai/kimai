<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Export\ServiceExport;
use App\Repository\CustomerRepository;
use App\Repository\ProjectRepository;
use App\Repository\Query\ExportQuery;
use App\Repository\Query\TimesheetQuery;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Timesheet\DateTimeFactory;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'kimai:export:create')]
final class ExportCreateCommand extends Command
{
    public function __construct(
        private readonly ServiceExport $serviceExport,
        private readonly CustomerRepository $customerRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly TeamRepository $teamRepository,
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create exports')
            ->setHelp('Create exports by several different filters and sent them via email.')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'The user to be used for generating the export (e.g. used for permissions and decimal setting)')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Start date (format: 2020-01-01, default: start of the month)', null)
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, 'End date (format: 2020-01-31, default: end of the month)', null)
            ->addOption('timezone', null, InputOption::VALUE_OPTIONAL, 'Timezone for start and end date query (fallback: server timezone)', null)
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'The locale to use', 'en')
            ->addOption('customer', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Customer IDs to filter', null)
            ->addOption('project', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Project IDs to filter', null)
            ->addOption('team', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Team IDs to filter', null)
            ->addOption('user', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'User IDs to filter', null)
            ->addOption('set-exported', null, InputOption::VALUE_NONE, 'Whether the included items should be marked as exported (default: false)')
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Export template', null)
            ->addOption('exported', null, InputOption::VALUE_OPTIONAL, 'Exported filter for export entries. By default only "not exported" items are fetched (possible values: exported, all)', null)
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'Absolute path for the rendered export documents (uses system tmp dir by default)', null)
            ->addOption('email', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Email address(es) for the recipients (email will be sent out with attached file, generated exports will be removed afterwards)', null)
            ->addOption('subject', null, InputOption::VALUE_OPTIONAL, 'Email subject (needs to be set if "email" is configured)', null)
            ->addOption('body', null, InputOption::VALUE_OPTIONAL, 'Body of the email (needs to be set if "email" is configured)', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $exportedFilter = TimesheetQuery::STATE_NOT_EXPORTED;
        switch ($input->getOption('exported')) {
            case null:
                break;

            case 'all':
                $exportedFilter = TimesheetQuery::STATE_ALL;
                break;

            case 'exported':
                $exportedFilter = TimesheetQuery::STATE_EXPORTED;
                break;

            default:
                $io->error('Unknown "exported" filter given');

                return Command::FAILURE;
        }

        $locale = $input->getOption('locale');
        \Locale::setDefault($locale);
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }

        $timezone = $input->getOption('timezone');
        if ($timezone === null) {
            $timezone = date_default_timezone_get();
        }
        $timezone = new \DateTimeZone($timezone);
        $dateFactory = new DateTimeFactory($timezone);

        $customerIDs = $this->optionToIntArray($input, 'customer');
        $customers = [];
        if (\count($customerIDs) > 0) {
            $customers = $this->customerRepository->findByIds($customerIDs);
        }

        $projectIDs = $this->optionToIntArray($input, 'project');
        $projects = [];
        if (\count($projectIDs) > 0) {
            $projects = $this->projectRepository->findByIds($projectIDs);
        }

        $teamIDs = $this->optionToIntArray($input, 'team');
        $teams = [];
        if (\count($teamIDs) > 0) {
            $teams = $this->teamRepository->findByIds($teamIDs);
        }

        $userIDs = $this->optionToIntArray($input, 'user');
        $users = [];
        if (\count($userIDs) > 0) {
            $users = $this->userRepository->findByIds($userIDs);
        }

        $template = $input->getOption('template');
        if ($template === null) {
            $io->error('You must pass the "template" option');

            return Command::FAILURE;
        }
        $renderer = $this->serviceExport->getRendererById($template);
        if ($renderer === null) {
            $io->error('Unknown export "template", available are:');
            $rows = [];
            foreach ($this->serviceExport->getRenderer() as $tmp) {
                $rows[] = [$tmp->getId()];
            }
            $io->table(['ID'], $rows);

            return Command::FAILURE;
        }

        $start = $input->getOption('start');
        if (!empty($start)) {
            try {
                $start = $dateFactory->createDateTime($start);
            } catch (\Exception $ex) {
                $io->error('Invalid start date given');

                return Command::FAILURE;
            }
        }
        if (!$start instanceof \DateTimeInterface) {
            $start = $dateFactory->getStartOfMonth();
        }

        $start = \DateTimeImmutable::createFromInterface($start);
        $start = $start->setTime(0, 0, 0);

        $end = $input->getOption('end');
        if (!empty($end)) {
            try {
                $end = $dateFactory->createDateTime($end);
            } catch (\Exception $ex) {
                $io->error('Invalid end date given');

                return Command::FAILURE;
            }
        }

        if (!$end instanceof \DateTimeInterface) {
            $end = $dateFactory->getEndOfMonth($start);
        }

        $end = \DateTimeImmutable::createFromInterface($end);
        $end = $end->setTime(23, 59, 59);

        $directory = rtrim(sys_get_temp_dir(), '/') . '/';
        if ($input->getOption('directory') !== null) {
            $directory = rtrim($input->getOption('directory'), '/') . '/';
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            $io->error('Invalid "directory" given: ' . $directory);

            return Command::FAILURE;
        }

        $subject = 'Export data available';
        $body = 'Your exported data is available, please find it attached to this email.';

        /** @var array<string> $emails */
        $emails = [];
        /** @var array<string> $tmp */
        $tmp = $input->getOption('email');
        if (\count($tmp) > 0) {
            foreach ($tmp as $email) {
                $result = filter_var($email, FILTER_VALIDATE_EMAIL);
                if ($result === false) {
                    $io->error('Invalid "email" given');

                    return Command::FAILURE;
                }
                $emails[] = (string) $email;
            }
        }

        if ($input->getOption('subject') !== null) {
            $subject = trim($input->getOption('subject'));
        }

        if ($input->getOption('body') !== null) {
            $body = trim($input->getOption('body'));
        }

        $query = new ExportQuery();

        $username = $input->getOption('username');
        if (\is_string($username) && !empty($username)) {
            try {
                $user = $this->userRepository->loadUserByIdentifier($username);
            } catch(\Exception) {
                $io->error(
                    \sprintf('The given username "%s" could not be resolved', $username)
                );

                return Command::FAILURE;
            }
            $query->setCurrentUser($user);
        }

        $query->setBegin($start);
        $query->setEnd($end);
        $query->setExported($exportedFilter);
        $query->setCustomers($customers);
        $query->setProjects($projects);
        $query->setTeams($teams);
        foreach ($users as $user) {
            $query->addUser($user);
        }

        $io = new SymfonyStyle($input, $output);

        $entries = $this->serviceExport->getExportItems($query);
        if (\count($entries) === 0) {
            $io->success('No entries found, skipping');

            return Command::SUCCESS;
        }

        $response = $renderer->render($entries, $query);
        $file = $this->savePreview($response, $directory);

        if ($input->getOption('set-exported')) {
            $this->serviceExport->setExported($entries);
        }

        if (\count($emails) > 0) {
            foreach ($emails as $to) {
                $mail = new TemplatedEmail();
                $mail->addTo($to);
                $mail->subject($subject);
                $mail->htmlTemplate('emails/default.html.twig');
                $mail->context([
                    'subject' => $subject,
                    'body' => $body,
                ]);
                $mail->attachFromPath($file);
                $this->mailer->send($mail);

                $io->success('Send email with report to: ' . $to);
            }

            unlink($file);
        } else {
            $io->success('Saved export to: ' . $file);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int>
     */
    private function optionToIntArray(InputInterface $input, string $name): array
    {
        $results = [];

        $options = $input->getOption($name);
        if (\is_array($options) && \count($options) > 0) {
            foreach ($options as $option) {
                if (is_numeric($option)) {
                    $results[] = (int) $option;
                }
            }
        }

        return $results;
    }

    private function savePreview(Response $response, string $directory): string
    {
        $filename = uniqid('invoice_');
        $directory = rtrim($directory, '/') . '/';

        if ($response->headers->has('Content-Disposition')) {
            $disposition = $response->headers->get('Content-Disposition');
            $parts = explode(';', $disposition);
            foreach ($parts as $part) {
                if (stripos($part, 'filename=') === false) {
                    continue;
                }
                $filename = explode('filename=', $part);
                if (\count($filename) > 1) {
                    $filename = $filename[1];
                }
            }
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();
            $file->move($directory, $filename);
        } else {
            (new Filesystem())->dumpFile($directory . $filename, $response->getContent());
        }

        return $directory . $filename;
    }
}
