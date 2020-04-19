<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use PackageVersions\Versions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/doctor")
 * @Security("is_granted('system_information')")
 */
class DoctorController extends AbstractController
{
    /**
     * PHP extensions which Kimai needs for runtime.
     * Some are not a hard requiremenet, but some functions might not work as expected.
     */
    public const REQUIRED_EXTENSIONS = [
        'intl',
        'json',
        'mbstring',
        'pdo',
        'zip',
        'gd',
        'xml'
    ];

    /**
     * Directories which need to be writable by the webserver.
     */
    public const DIRECTORIES_WRITABLE = [
        'var/cache/',
        'var/data/',
        'var/log/',
        'var/sessions/',
        'public/avatars/',
    ];

    /**
     * @var string
     */
    private $projectDirectory;

    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @Route(path="/flush-log", name="doctor_flush_log", methods={"GET"})
     * @Security("is_granted('system_configuration')")
     */
    public function deleteLogfileAction(): Response
    {
        $logfile = $this->getLogFilename();

        if (file_exists($logfile)) {
            if (!is_writable($logfile)) {
                $this->flashError('action.delete.error', ['%reason%' => 'Logfile cannot be written']);
            } else {
                if (false === file_put_contents($logfile, '')) {
                    $this->flashError('action.delete.error', ['%reason%' => 'Failed writing to logfile']);
                } else {
                    $this->flashSuccess('action.delete.success');
                }
            }
        }

        return $this->redirectToRoute('doctor');
    }

    /**
     * @Route(path="", name="doctor", methods={"GET"})
     */
    public function index(): Response
    {
        $logLines = 100;

        $canDeleteLogfile = $this->isGranted('system_configuration') && is_writable($this->getLogFilename());

        return $this->render('doctor/index.html.twig', array_merge(
            [
                'modules' => get_loaded_extensions(),
                'dotenv' => $this->getEnvVars(),
                'info' => $this->getPhpInfo(),
                'settings' => $this->getIniSettings(),
                'extensions' => $this->getLoadedExtensions(),
                'directories' => $this->getFilePermissions(),
                'log_delete' => $canDeleteLogfile,
                'logs' => $this->getLog(),
                'logLines' => $logLines,
                'logSize' => $this->getLogSize(),
                'composer' => Versions::VERSIONS,
            ]
        ));
    }

    private function getLoadedExtensions()
    {
        $results = [];

        foreach (self::REQUIRED_EXTENSIONS as $extName) {
            $results[$extName] = false;
            if (\extension_loaded($extName)) {
                $results[$extName] = true;
            }
        }

        $results['Freetype Support'] = true;
        // @see AvatarService::hasDependencies()
        if (!\function_exists('imagettfbbox')) {
            $results['Freetype Support'] = false;
        }

        return $results;
    }

    private function getLogSize()
    {
        $logfileName = 'var/log/' . getenv('APP_ENV') . '.log';
        $logfile = $this->projectDirectory . '/' . $logfileName;

        return filesize($logfile);
    }

    private function getLogFilename(): string
    {
        // why is this check here ???
        if (!\in_array(getenv('APP_ENV'), ['test', 'dev', 'prod'])) {
            throw new \RuntimeException('Unsupported log environment');
        }

        $logfileName = 'var/log/' . getenv('APP_ENV') . '.log';

        return $this->projectDirectory . '/' . $logfileName;
    }

    private function getLog(int $lines = 100)
    {
        try {
            $logfile = $this->getLogFilename();
        } catch (\Exception $ex) {
            return ['ATTENTION: ' . $ex->getMessage()];
        }

        if (!file_exists($logfile)) {
            return ['ATTENTION: Missing logfile'];
        }

        if (!is_readable($logfile)) {
            return ['ATTENTION: Cannot read log file'];
        }

        $file = new \SplFileObject($logfile, 'r');

        if ($file->getSize() === 0) {
            return ['Empty log'];
        }

        $file->seek($file->getSize());
        $last_line = $file->key();
        while ($last_line - $lines < 0) {
            $lines--;
        }
        $iterator = new \LimitIterator($file, $last_line - $lines, $last_line);

        $result = [];

        try {
            $result = iterator_to_array($iterator);
        } catch (\Exception $ex) {
            $result = ['ATTENTION: Failed reading log file'];
        }

        if (!is_writable($logfile)) {
            $result[] = 'ATTENTION: Cannot write log file';
        }

        return $result;
    }

    private function getFilePermissions()
    {
        $results = [];

        foreach (self::DIRECTORIES_WRITABLE as $path) {
            $results[$path] = false;
            $fullPath = $this->projectDirectory . '/' . $path;
            $fullUri = realpath($fullPath);

            if ($fullUri === false && !file_exists($fullPath)) {
                @mkdir($fullPath);
                $fullUri = realpath($fullPath);
            }

            if ($fullUri !== false && is_readable($fullUri) && is_writable($fullUri)) {
                $results[$path] = true;
            }
        }

        return $results;
    }

    private function getEnvVars()
    {
        return [
            'APP_ENV' => getenv('APP_ENV'),
            'CORS_ALLOW_ORIGIN' => getenv('CORS_ALLOW_ORIGIN'),
        ];
    }

    private function getIniSettings()
    {
        $ini = [
            'allow_url_fopen',
            'allow_url_include',
            'default_charset',
            'default_mimetype',
            'display_errors',
            'error_log',
            'error_reporting',
            'log_errors',
            'max_execution_time',
            'memory_limit',
            'open_basedir',
            'post_max_size',
            'sys_temp_dir',
            'date.timezone',
        ];

        $settings = [];
        foreach ($ini as $name) {
            try {
                $settings[$name] = ini_get($name);
            } catch (\Exception $ex) {
                $settings[$name] = "Couldn't load ini setting: " . $ex->getMessage();
            }
        }

        return $settings;
    }

    /**
     * @author https://php.net/manual/en/function.phpinfo.php#117961
     * @return array
     */
    private function getPhpInfo()
    {
        $plainText = function ($input) {
            return trim(html_entity_decode(strip_tags($input)));
        };

        ob_start();
        phpinfo(1);

        $phpinfo = ['phpinfo' => []];

        if (preg_match_all(
            '#(?:<h2.*?>(?:<a.*?>)?(.*?)(?:<\/a>)?<\/h2>)|' .
            '(?:<tr.*?><t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>(?:<t[hd].*?>(.*?)\s*</t[hd]>)?)?</tr>)#s',
            ob_get_clean(),
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $fn = $plainText;
                if (isset($match[3])) {
                    $keys1 = array_keys($phpinfo);
                    $phpinfo[end($keys1)][$fn($match[2])] = isset($match[4]) ? [$fn($match[3]), $fn($match[4])] : $fn($match[3]);
                } else {
                    $keys1 = array_keys($phpinfo);
                    $phpinfo[end($keys1)][] = $fn($match[2]);
                }
            }
        }

        $phpInfo = $phpinfo['phpinfo'];
        unset($phpInfo[0]);
        unset($phpInfo[1]);

        return $phpInfo;
    }
}
