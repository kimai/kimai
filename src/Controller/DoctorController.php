<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Utils\FileHelper;
use Composer\InstalledVersions;
use PackageVersions\Versions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
        'var/log/',
    ];

    private $projectDirectory;
    private $environment;
    private $fileHelper;

    public function __construct(string $projectDirectory, string $kernelEnvironment, FileHelper $fileHelper)
    {
        $this->projectDirectory = $projectDirectory;
        $this->environment = $kernelEnvironment;
        $this->fileHelper = $fileHelper;
    }

    /**
     * @Route(path="/flush-log/{token}", name="doctor_flush_log", methods={"GET"})
     * @Security("is_granted('system_configuration')")
     */
    public function deleteLogfileAction(string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('doctor.flush_log', $token))) {
            $this->flashError('action.delete.error');

            return $this->redirectToRoute('doctor');
        }

        $csrfTokenManager->refreshToken($token);

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
                'environment' => $this->environment,
                'info' => $this->getPhpInfo(),
                'settings' => $this->getIniSettings(),
                'extensions' => $this->getLoadedExtensions(),
                'directories' => $this->getFilePermissions(),
                'log_delete' => $canDeleteLogfile,
                'logs' => $this->getLog(),
                'logLines' => $logLines,
                'logSize' => $this->getLogSize(),
                'composer' => $this->getComposerPackages(),
            ]
        ));
    }

    private function getComposerPackages(): array
    {
        $versions = [];

        if (class_exists(InstalledVersions::class)) {
            $rootPackage = InstalledVersions::getRootPackage()['name'];
            foreach (InstalledVersions::getInstalledPackages() as $package) {
                $versions[$package] = InstalledVersions::getPrettyVersion($package);
            }
        } else {
            @trigger_error('Please upgrade your Composer to 2.x', E_USER_DEPRECATED);

            // @deprecated since 1.14, will be removed with 2.0
            $rootPackage = Versions::rootPackageName();
            foreach (Versions::VERSIONS as $name => $version) {
                $versions[$name] = explode('@', $version)[0];
            }
        }

        // remove kimai from the package list
        $versions = array_filter($versions, function ($version, $name) use ($rootPackage) {
            if ($name === $rootPackage) {
                return false;
            }

            if ($version === null || $version === '*') {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

        ksort($versions);

        return $versions;
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

        return $results;
    }

    private function getLogSize(): int
    {
        $logfile = $this->getLogFilename();

        return file_exists($logfile) ? filesize($logfile) : 0;
    }

    private function getLogFilename(): string
    {
        $logfileName = 'var/log/' . $this->environment . '.log';

        return $this->projectDirectory . '/' . $logfileName;
    }

    private function getLog(int $lines = 100): array
    {
        $logfile = $this->getLogFilename();

        if (!file_exists($logfile)) {
            return ['Missing logfile'];
        }

        if (!is_readable($logfile)) {
            return ['ATTENTION: Cannot read log file'];
        }

        $file = new \SplFileObject($logfile, 'r');

        if ($file->getSize() === 0) {
            return ['Empty logfile'];
        }

        $file->seek($file->getSize());
        $last_line = $file->key();
        while ($last_line - $lines < 0) {
            $lines--;
        }
        $iterator = new \LimitIterator($file, $last_line - $lines, $last_line);

        try {
            $result = iterator_to_array($iterator);
        } catch (\Exception $ex) {
            $result = ['ATTENTION: Failed reading log file'];
        }

        if (!is_writable($logfile)) {
            $result[] = 'ATTENTION: Logfile is not writable';
        }

        return $result;
    }

    private function getFilePermissions()
    {
        $testPaths = [];
        $baseDir = $this->projectDirectory . DIRECTORY_SEPARATOR;

        foreach (self::DIRECTORIES_WRITABLE as $path) {
            $fullPath = $baseDir . $path;
            $fullUri = realpath($fullPath);

            if ($fullUri === false && !file_exists($fullPath)) {
                @mkdir($fullPath);
                clearstatcache(true);
                $fullUri = realpath($fullPath);
            }

            $testPaths[] = $fullUri;
        }

        $results = [];
        $testPaths[] = $this->fileHelper->getDataDirectory();
        foreach ($testPaths as $fullUri) {
            $fullUri = rtrim($fullUri, DIRECTORY_SEPARATOR);
            $tmp = str_replace($baseDir, '', $fullUri) . DIRECTORY_SEPARATOR;
            if ($fullUri !== false && is_readable($fullUri) && is_writable($fullUri)) {
                $results[$tmp] = true;
            } else {
                $results[$tmp] = false;
            }
        }

        return $results;
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
