<?php
$DB_HOST = $argv[1];
$DB_BASE = $argv[2];
$DB_PORT = $argv[3];
$DB_USER = $argv[4];
$DB_PASS = $argv[5];

echo "Testing DB:";
echo "*";
echo "* new \PDO(mysql:host=$DB_HOST;dbname=$DB_BASE;port=$DB_PORT, $DB_USER, $DB_PASS, [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ]);";
echo "*";

try {
    $pdo = new \PDO("mysql:host=$DB_HOST;dbname=$DB_BASE;port=$DB_PORT", "$DB_USER", "$DB_PASS", [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
    ]);
} catch(\Exception $ex) {
    switch ($ex->getCode()) {
        // we can immediately stop startup here and show the error message
        case 1045:
            echo 'Access denied (1045)';
            die(1);
        // we can immediately stop startup here and show the error message
        case 1049:
            echo 'Unknown database (1049)';
            die(2);
        // a lot of errors share the same meaningless error code zero
        case 0:
            // this error includes the database name, so we can only search for the static part of the error message
            if (stripos($ex->getMessage(), 'SQLSTATE[HY000] [1049] Unknown database') !== false) {
                echo 'Unknown database (0-1049)';
                die(3);
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
