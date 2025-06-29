<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Constants;
use App\Event\EmailEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'kimai:mail:test', description: 'Send a test email using MAILER_URL and MAILER_FROM')]
final class MailTestCommand extends Command
{
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'The email address to send the email to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = $input->getArgument('to');
        if (!\is_string($to) || $to === '') {
            throw new \InvalidArgumentException('Need a non-empty "to" address');
        }

        $message = new Email();
        $message->to($to);
        $message->subject('Test email - ' . Constants::SOFTWARE);
        $message->text('This is a test email from your time-tracker');

        $this->dispatcher->dispatch(new EmailEvent($message));

        return Command::SUCCESS;
    }
}
