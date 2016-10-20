<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\Command;

use AppBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * A command console that lists all the existing users. To use this command, open
 * a terminal window, enter into your project directory and execute the following:
 *
 *     $ php bin/console app:list-users
 *
 * See http://symfony.com/doc/current/cookbook/console/console_command.html
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class ListUsersCommand extends ContainerAwareCommand
{
    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            // a good practice is to use the 'app:' prefix to group all your custom application commands
            ->setName('app:list-users')
            ->setDescription('Lists all the existing users')
            ->setHelp(<<<HELP
The <info>%command.name%</info> command lists all the users registered in the application:

  <info>php %command.full_name%</info>

By default the command only displays the 50 most recent users. Set the number of
results to display with the <comment>--max-results</comment> option:

  <info>php %command.full_name%</info> <comment>--max-results=2000</comment>

In addition to displaying the user list, you can also send this information to
the email address specified in the <comment>--send-to</comment> option:

  <info>php %command.full_name%</info> <comment>--send-to=fabien@symfony.com</comment>

HELP
            )
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see http://symfony.com/doc/current/components/console/console_arguments.html
            ->addOption('max-results', null, InputOption::VALUE_OPTIONAL, 'Limits the number of users listed', 50)
            ->addOption('send-to', null, InputOption::VALUE_OPTIONAL, 'If set, the result is sent to the given email address')
        ;
    }

    /**
     * This method is executed before the the execute() method. It's main purpose
     * is to initialize the variables used in the rest of the command methods.
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $maxResults = $input->getOption('max-results');
        // Use ->findBy() instead of ->findAll() to allow result sorting and limiting
        $users = $this->entityManager->getRepository(User::class)->findBy([], ['id' => 'DESC'], $maxResults);

        // Doctrine query returns an array of objects and we need an array of plain arrays
        $usersAsPlainArrays = array_map(function (User $user) {
            return [$user->getId(), $user->getUsername(), $user->getEmail(), implode(', ', $user->getRoles())];
        }, $users);

        // In your console commands you should always use the regular output type,
        // which outputs contents directly in the console window. However, this
        // particular command uses the BufferedOutput type instead.
        // The reason is that the table displaying the list of users can be sent
        // via email if the '--send-to' option is provided. Instead of complicating
        // things, the BufferedOutput allows to get the command output and store
        // it in a variable before displaying it.
        $bufferedOutput = new BufferedOutput();

        $table = new Table($bufferedOutput);
        $table
            ->setHeaders(['ID', 'Username', 'Email', 'Roles'])
            ->setRows($usersAsPlainArrays)
        ;
        $table->render();

        // instead of displaying the table of users, store it in a variable
        $tableContents = $bufferedOutput->fetch();

        if (null !== $email = $input->getOption('send-to')) {
            $this->sendReport($tableContents, $email);
        }

        $output->writeln($tableContents);
    }

    /**
     * Sends the given $contents to the $recipient email address.
     *
     * @param string $contents
     * @param string $recipient
     */
    private function sendReport($contents, $recipient)
    {
        // See http://symfony.com/doc/current/cookbook/email/email.html
        $mailer = $this->getContainer()->get('mailer');

        $message = $mailer->createMessage()
            ->setSubject(sprintf('app:list-users report (%s)', date('Y-m-d H:i:s')))
            ->setFrom($this->getContainer()->getParameter('app.notifications.email_sender'))
            ->setTo($recipient)
            ->setBody($contents, 'text/plain')
        ;

        $mailer->send($message);
    }
}
