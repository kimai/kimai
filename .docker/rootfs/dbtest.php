<?php
$DATABASE_HOST = urldecode($argv[1]);
$DATABASE_BASE = urldecode($argv[2]);
$DATABASE_PORT = $argv[3];
$DATABASE_USER = urldecode($argv[4]);
$DATABASE_PASS = urldecode($argv[5]);

echo "Testing DB:";

try {
    $pdo = new \PDO("mysql:host=$DATABASE_HOST;dbname=$DATABASE_BASE;port=$DATABASE_PORT", "$DATABASE_USER", "$DATABASE_PASS", [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ]);
} catch(\Exception $ex) {
    switch ($ex->getCode()) {
        case 1045:
            // we can immediately stop here and show the error message
            echo 'Access denied (1045)';
            die(1);
        case 1049:
            // error "Unknown database (1049)" can be ignored, the database will be created by Kimai
            return;
        // a lot of errors share the same meaningless error code zero
        case 0:
            // this error includes the database name, so we can only search for the static part of the error message
            if (stripos($ex->getMessage(), 'SQLSTATE[HY000] [1049] Unknown database') !== false) {
                // error "Unknown database (1049)" can be ignored, the database will be created by Kimai
                return;
            }
            switch ($ex->getMessage()) {
                // eg. no response (fw) - the startup script should retry it a couple of times
                case 'SQLSTATE[HY000] [2002] Operation timed out':
                    echo 'Operation timed out (0-2002)';
                    die(4);
                // special case "localhost" with a stopped db server (should not happen in docker compose setup)
                case 'SQLSTATE[HY000] [2002] No such file or directory':
                    echo 'Connection could not be established (0-2002)';
                    die(5);
                // using IP with stopped db server - the startup script should retry it a couple of times
                case 'SQLSTATE[HY000] [2002] Connection refused':
                    echo 'Connection refused (0-2002)';
                    die(5);
            }
            echo $ex->getMessage() . " (0)";
            die(7);
        default:
            // unknown error
            echo $ex->getMessage() . " (?)";
            die(10);
    }
}
