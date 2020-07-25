<?php

declare(strict_types=1);

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Command used to generate the icon alias list.
 *
 * Only for development environments!
 *
 * @codeCoverageIgnore
 */
final class FontAwesomeCommand extends Command
{
    /**
     * Pre-defined icon aliases that are used in Kimai
     *
     * @var string[]
     */
    private static $icons = [
        'about' => 'fas fa-info-circle',
        'activity' => 'fas fa-tasks',
        'admin' => 'fas fa-wrench',
        'audit' => 'fas fa-history',
        'avatar' => 'fas fa-user',
        'back' => 'fas fa-long-arrow-alt-left',
        'calendar' => 'far fa-calendar-alt',
        'clock' => 'far fa-clock',
        'comment' => 'far fa-comment',
        'configuration' => 'fas fa-cogs',
        'copy' => 'far fa-copy',
        'create' => 'far fa-plus-square',
        'csv' => 'fas fa-table',
        'customer' => 'fas fa-user-tie',
        'dashboard' => 'fas fa-tachometer-alt',
        'debug' => 'far fa-file-alt',
        'delete' => 'far fa-trash-alt',
        'doctor' => 'fas fa-medkit',
        'dot' => 'fas fa-circle',
        'download' => 'fas fa-download',
        'duration' => 'far fa-hourglass',
        'edit' => 'far fa-edit',
        'end' => 'fas fa-stopwatch',
        'export' => 'fas fa-file-export',
        'fax' => 'fas fa-fax',
        'filter' => 'fas fa-filter',
        'help' => 'far fa-question-circle',
        'home' => 'fas fa-home',
        'invoice' => 'fas fa-file-invoice-dollar',
        'invoice-template' => 'fas fa-file-signature',
        'left' => 'fas fa-chevron-left',
        'list' => 'fas fa-list',
        'locked' => 'fas fa-lock',
        'login' => 'fas fa-sign-in-alt',
        'logout' => 'fas fa-sign-out-alt',
        'mail' => 'fas fa-envelope-open',
        'mail-sent' => 'fas fa-paper-plane',
        'manual' => 'fas fa-book',
        'mobile' => 'fas fa-mobile',
        'money' => 'far fa-money-bill-alt',
        'ods' => 'fas fa-table',
        'off' => 'fas fa-toggle-off',
        'on' => 'fas fa-toggle-on',
        'pin' => 'fas fa-thumbtack',
        'pdf' => 'fas fa-file-pdf',
        'pause' => 'fas fa-pause',
        'pause-small' => 'far fa-pause-circle',
        'permissions' => 'fas fa-user-lock',
        'phone' => 'fas fa-phone',
        'plugin' => 'fas fa-plug',
        'print' => 'fas fa-print',
        'profile' => 'fas fa-user-edit',
        'profile-stats' => 'far fa-chart-bar',
        'project' => 'fas fa-briefcase',
        'repeat' => 'fas fa-redo-alt',
        'reporting' => 'far fa-chart-bar',
        'right' => 'fas fa-chevron-right',
        'roles' => 'fas fa-user-shield',
        'search' => 'fas fa-search',
        'settings' => 'fas fa-cog',
        'shop' => 'fas fa-shopping-cart',
        'start' => 'fas fa-play',
        'start-small' => 'far fa-play-circle',
        'stop' => 'fas fa-stop',
        'stop-small' => 'far fa-stop-circle',
        'success' => 'fas fa-check',
        'tag' => 'fas fa-tags',
        'team' => 'fas fa-users',
        'timesheet' => 'fas fa-clock',
        'timesheet-team' => 'fas fa-user-clock',
        'trash' => 'far fa-trash-alt',
        'unlocked' => 'fas fa-unlock-alt',
        'upload' => 'fas fa-upload',
        'user' => 'fas fa-user-friends',
        'visibility' => 'far fa-eye',
        'warning' => 'fas fa-exclamation-triangle',
        'xlsx' => 'fas fa-file-excel',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('kimai:font-awesome')
            ->setDescription('Regenerate icon list')
            ->setHelp('Will regenerate the icon mapping file.')
        ;
    }

    /**
     * Make sure that this command CANNOT be executed in production.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return getenv('APP_ENV') !== 'prod';
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $baseDir = \dirname(\dirname(__DIR__));
        $iconsLocation = $baseDir . '/node_modules/@fortawesome/fontawesome-free/metadata/icons.yml';
        $targetLocation = $baseDir . '/config/icons.php';

        $icons = self::$icons;
        $yaml = new Yaml();

        // extract all icons from the official collection
        $result = $yaml->parseFile($iconsLocation);
        foreach ($result as $name => $config) {
            // do not overwrite pre-defined aliases
            if (\array_key_exists($name, $icons)) {
                continue;
            }

            $class = 'fa';

            $style = $config['styles'][0];
            switch ($style) {
                case 'brands':
                    $class .= 'b';
                    break;

                case 'solid':
                    $class .= 's';
                    break;

                case 'regular':
                    $class .= 'r';
                    break;

                default:
                    throw new \Exception('Unknown icon style: ' . $style);
            }

            $class .= ' fa-' . $name;

            $icons[$name] = $class;
        }

        $prefix = '<?php

declare(strict_types=1);

/**
 * AUTOMATICALLY GENERATED WITH: bin/console kimai:font-awesome  
 */

return ';

        $suffix = ';' . PHP_EOL;

        $icons = array_merge(self::$icons, $icons);

        $export = var_export($icons, true);
        file_put_contents($targetLocation, $prefix . $export . $suffix);

        $io->success(
            'Parsed FontAwesome icon files' . PHP_EOL .
            ' - Icons: ' . $iconsLocation . PHP_EOL .
            PHP_EOL .
            'Written to: ' . $targetLocation
        );

        return 0;
    }
}
