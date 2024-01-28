<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Event\EmailEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'kimai:mail:test', description: 'Send a test email')]
final class MailTestCommand extends Command
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'The email address to send the email to');
        $this->addOption('from', null, InputOption::VALUE_OPTIONAL, 'The sender of the message', 'kimai@example.org');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = new Email();
        $message->to((string) $input->getArgument('to')); // @phpstan-ignore-line
        $message->from((string) $input->getOption('from')); // @phpstan-ignore-line
        $message->subject('Kimai test email');
        $message->text('This is an email for testing the text body.');

        $this->dispatcher->dispatch(new EmailEvent($message));

        return Command::SUCCESS;
    }
}
