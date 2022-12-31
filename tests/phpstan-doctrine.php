<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->loadEnv(dirname(__DIR__) . '/.env');

$env = $_SERVER['APP_ENV'] ?? 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? (in_array($env, ['dev', 'test'])));

$kernel = new Kernel($env, $debug);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
