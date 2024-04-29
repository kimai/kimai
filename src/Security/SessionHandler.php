<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class SessionHandler extends PdoSessionHandler
{
    public function __construct(
        Connection $connection,
        private readonly RateLimiterFactory $sessionPredictionLimiter,
        private readonly RequestStack $requestStack
    )
    {
        parent::__construct($connection->getNativeConnection(), [
            'db_table' => 'kimai2_sessions',
            'db_id_col' => 'id',
            'db_data_col' => 'data',
            'db_lifetime_col' => 'lifetime',
            'db_time_col' => 'time',
            'lock_mode' => PdoSessionHandler::LOCK_ADVISORY,
        ]);
    }

    public function garbageCollection(): void
    {
        $connection = $this->getConnection();
        $sql = 'DELETE FROM kimai2_sessions WHERE lifetime < :time';
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
        $stmt->execute();
    }

    public function validateId(#[\SensitiveParameter] string $sessionId): bool
    {
        $result = parent::validateId($sessionId);

        if ($result === false) {
            $limiter = $this->sessionPredictionLimiter->create($this->requestStack->getMainRequest()?->getClientIp());
            $limit = $limiter->consume();

            if (false === $limit->isAccepted()) {
                throw new BadRequestHttpException('Too many requests with invalid Session ID. Prediction attack?');
            }
        }

        return $result;
    }
}
