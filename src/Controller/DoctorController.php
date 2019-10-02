<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

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
    protected $projectDirectory;

    /**
     * @param string $projectDirectory
     */
    public function __construct(string $projectDirectory)
    {
        $this->projectDirectory = $projectDirectory;
    }

    /**
     * @Route(path="", name="doctor", methods={"GET"})
     */
    public function index(): Response
    {
        $logLines = 100;

        return $this->render('doctor/index.html.twig', array_merge(
            [
                'modules' => get_loaded_extensions(),
                'dotenv' => $this->getEnvVars(),
                'info' => $this->getPhpInfo(),
                'settings' => $this->getIniSettings(),
                'extensions' => $this->getLoadedExtensions(),
                'directories' => $this->getFilePermissions(),
                'logs' => $this->getLog(),
                'logLines' => $logLines,
                'logSize' => $this->getLogSize(),
            ]
        ));
    }

    private function getLoadedExtensions()
    {
        $results = [];

        foreach (self::REQUIRED_EXTENSIONS as $extName) {
            $results[$extName] = false;
            if (extension_loaded($extName)) {
                $results[$extName] = true;
            }
        }

        return $results;
    }

    private function getLogSize()
    {
        $logfileName = 'var/log/' . getenv('APP_ENV') . '.log';
        $logfile = $this->projectDirectory . '/' . $logfileName;

        return filesize($logfile);
    }

    private function getLog(int $lines = 100)
    {
        if (!in_array(getenv('APP_ENV'), ['test', 'dev', 'prod'])) {
            return [
                'Unsupported log environment'
            ];
        }

        $logfileName = 'var/log/' . getenv('APP_ENV') . '.log';
        $logfile = $this->projectDirectory . '/' . $logfileName;

        if (!file_exists($logfile)) {
            return [
                'Could not find logfile: ' . $logfileName
            ];
        }

        $file = new \SplFileObject($logfile, 'r');
        $file->seek($file->getSize());
        $last_line = $file->key();
        while ($last_line - $lines < 0) {
            $lines--;
        }
        $lines = new \LimitIterator($file, $last_line - $lines, $last_line);

        return iterator_to_array($lines);
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
            'MAILER_FROM' => getenv('MAILER_FROM'),
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
