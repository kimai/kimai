<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

abstract class AbstractUserCommand extends Command
{
    protected function askForPassword(InputInterface $input, OutputInterface $output): string
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $passwordQuestion = new Question('Please enter the password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function (?string $value) {
            $password = trim($value);
            if (empty($password)) {
                throw new \Exception('The password may not be empty');
            }

            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);

        return $helper->ask($input, $output, $passwordQuestion);
    }
}
