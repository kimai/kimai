<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Utils\FileHelper;
use App\Utils\PageSetup;
use App\Utils\ReleaseVersion;
use Composer\InstalledVersions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route(path: '/doctor')]
#[IsGranted('system_information')]
final class DoctorController extends AbstractController
{
    /**
     * Required PHP extensions for Kimai.
     */
    public const REQUIRED_EXTENSIONS = [
        'intl',
        'json',
        'mbstring',
        'pdo',
        'xml',
        'xsl',
        'zip',
    ];

    /**
     * Directories which need to be writable by the webserver.
     */
    public const DIRECTORIES_WRITABLE = [
        'var/cache/',
        'var/log/',
    ];

    public function __construct(private string $projectDirectory, private string $kernelEnvironment, private FileHelper $fileHelper, private CacheInterface $cache)
    {
    }

    #[Route(path: '/flush-log/{token}', name: 'doctor_flush_log', methods: ['GET'])]
    #[IsGranted('system_configuration')]
    public function deleteLogfileAction(string $token, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('doctor.flush_log', $token))) {
            $this->flashError('action.csrf.error');

            return $this->redirectToRoute('doctor');
        }

        $csrfTokenManager->refreshToken('doctor.flush_log');

        $logfile = $this->getLogFilename();

        if (file_exists($logfile)) {
            if (!is_writable($logfile)) {
                $this->flashError('action.delete.error', 'Logfile cannot be written');
            } else {
                if (false === file_put_contents($logfile, '')) {
                    $this->flashError('action.delete.error', 'Failed writing to logfile');
                } else {
                    $this->flashSuccess('action.delete.success');
                }
            }
        }

        return $this->redirectToRoute('doctor');
    }

    #[Route(path: '', name: 'doctor', methods: ['GET'])]
    public function index(): Response
    {
        $logLines = 100;
        $canDeleteLogfile = $this->isGranted('system_configuration') && is_writable($this->getLogFilename());
        $page = new PageSetup('Doctor');
        $page->setHelp('doctor.html');

        return $this->render('doctor/index.html.twig', [
            'page_setup' => $page,
            'modules' => get_loaded_extensions(),
            'environment' => $this->kernelEnvironment,
            'info' => $this->getPhpInfo(),
            'settings' => $this->getIniSettings(),
            'extensions' => $this->getLoadedExtensions(),
            'directories' => $this->getFilePermissions(),
            'log_delete' => $canDeleteLogfile,
            'logs' => $this->getLog(),
            'logLines' => $logLines,
            'logSize' => $this->getLogSize(),
            'composer' => $this->getComposerPackages(),
            'release' => $this->getNextUpdateVersion()
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function getComposerPackages(): array
    {
        $versions = [];

        if (class_exists(InstalledVersions::class)) {
            $rootPackage = InstalledVersions::getRootPackage()['name'];
            foreach (InstalledVersions::getInstalledPackages() as $package) {
                $versions[$package] = InstalledVersions::getPrettyVersion($package);
            }

            // remove kimai from the package list
            $versions = array_filter($versions, function ($version, $name) use ($rootPackage): bool {
                if ($name === $rootPackage) {
                    return false;
                }

                if ($version === null || $version === '*') {
                    return false;
                }

                return true;
            }, ARRAY_FILTER_USE_BOTH);

            ksort($versions);
        }

        return $versions;
    }

    /**
     * @return array<string, bool>
     */
    private function getLoadedExtensions(): array
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
        $logfileName = 'var/log/' . $this->kernelEnvironment . '.log';

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

    private function getFilePermissions(): array
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
            if (is_readable($fullUri) && is_writable($fullUri)) {
                $results[$tmp] = true;
            } else {
                $results[$tmp] = false;
            }
        }

        return $results;
    }

    private function getIniSettings(): array
    {
        $ini = [
            'memory_limit',
            'session.gc_maxlifetime',
            'max_execution_time',
            'date.timezone',
            'allow_url_fopen',
            'allow_url_include',
            'default_charset',
            'default_mimetype',
            'display_errors',
            'error_log',
            'error_reporting',
            'log_errors',
            'open_basedir',
            'post_max_size',
            'sys_temp_dir',
            'date.timezone',
            'session.gc_maxlifetime',
        ];

        $settings = [];
        foreach ($ini as $name) {
            try {
                $settings[$name] = \ini_get($name);
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
    private function getPhpInfo(): array
    {
        $plainText = function ($input): string {
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

    private function getNextUpdateVersion(): ?array
    {
        return $this->cache->get('kimai.update_release', function (ItemInterface $item) {
            // we cache the result, no matter if the call failed: at the end, this is "just"
            // an update note but an expensive call

            $item->expiresAfter(86400); // one day

            try {
                $version = new ReleaseVersion();

                return $version->getLatestReleaseFromGithub(true);
            } catch (\Exception $ex) {
                // something failed, retry tomorrow
            }

            return null;
        });
    }
}
