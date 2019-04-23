<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Constants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/about")
 */
class AboutController extends AbstractController
{
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
     * @Route(path="/debug", name="about_debug", methods={"GET"})
     *
     * @Security("is_granted('system_information')")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->getAboutView();
    }

    protected function getAboutView(array $additional = [])
    {
        $phpInfo = $this->getPhpInfo();
        unset($phpInfo[0]);
        unset($phpInfo[1]);

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

        return $this->render('about/system.html.twig', array_merge(
            [
            'modules' => get_loaded_extensions(),
            'dotenv' => [
                'APP_ENV' => getenv('APP_ENV'),
                'MAILER_FROM' => getenv('MAILER_FROM'),
                'CORS_ALLOW_ORIGIN' => getenv('CORS_ALLOW_ORIGIN'),
            ],
            'info' => $phpInfo,
            'settings' => $settings,
            ],
            $additional
        ));
    }

    /**
     * @Route(path="", name="about", methods={"GET"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function license()
    {
        $filename = $this->projectDirectory . '/LICENSE';

        try {
            $license = file_get_contents($filename);
        } catch (\Exception $ex) {
            $license = false;
        }

        if (false === $license) {
            $license = 'Failed reading license file: ' . $filename . '. ' .
                'Check this instead: ' . Constants::GITHUB . 'blob/master/LICENSE';
        }

        return $this->render('about/license.html.twig', [
            'license' => $license
        ]);
    }

    /**
     * @Route(path="/flush-cache", name="system_flush_cache", methods={"GET"})
     *
     * @Security("is_granted('system_actions')")
     */
    public function rebuildContainer(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear',
            '--env' => $kernel->getEnvironment(),
            '-n',
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $this->getAboutView(['content_action' => $output->fetch()]);
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

        return $phpinfo['phpinfo'];
    }
}
