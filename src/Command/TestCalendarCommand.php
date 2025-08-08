<?php

namespace App\Command;

use App\Calendar\CalendarService;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'kimai:test:calendar',
    description: 'Test calendar sources and ICAL integration'
)]
final class TestCalendarCommand extends Command
{
    public function __construct(
        private CalendarService $calendarService,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing Calendar Sources');

        // Get the susan_super user who has the user ICAL link configured
        $user = $this->userRepository->findOneBy(['username' => 'susan_super']);
        if (!$user) {
            $io->error('User susan_super not found in the system');
            return Command::FAILURE;
        }

        $io->section('Testing Calendar Sources for user: ' . $user->getUserIdentifier());

        try {
            $sources = $this->calendarService->getSources($user);
            
            $io->text('Found ' . count($sources) . ' calendar sources:');
            
            foreach ($sources as $source) {
                $io->text(sprintf(
                    '- %s (type: %s, uri: %s, color: %s)',
                    $source->getId(),
                    $source->getTypeName(),
                    $source->getUri(),
                    $source->getColor() ?? 'default'
                ));
            }

            // Test ICAL sources specifically
            $icalSources = array_filter($sources, fn($s) => $s->getTypeName() === 'ical');
            $io->section('ICAL Sources: ' . count($icalSources));

            foreach ($icalSources as $source) {
                $io->text('Testing ICAL source: ' . $source->getId());
                try {
                    // Check if the source has a getEvents method
                    if (method_exists($source, 'getEvents')) {
                        $events = $source->getEvents();
                        $io->text(sprintf('  - Found %d events', count($events)));
                        
                        if (count($events) > 0) {
                            $io->text('  - First event: ' . ($events[0]['title'] ?? 'No title'));
                        }
                    } else {
                        $io->text('  - Source does not have getEvents method');
                    }
                } catch (\Exception $e) {
                    $io->error('  - Error getting events: ' . $e->getMessage());
                }
            }

            $io->success('Calendar test completed successfully');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error testing calendar: ' . $e->getMessage());
            $io->text('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
} 