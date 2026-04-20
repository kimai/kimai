<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Doctrine\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;

/**
 * Consolidates the obsolete single-endpoint webhook config keys into a
 * single `webhook.endpoints` JSON entry. Anyone who deployed the initial
 * PR with a configured URL/secret/event-toggles keeps their endpoint; the
 * old rows are cleaned up afterward.
 */
final class Version20260417120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Consolidate single-endpoint webhook config into webhook.endpoints JSON; drop old keys';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        $rows = $conn->executeQuery(
            "SELECT name, value FROM kimai2_configuration WHERE name = 'webhook.endpoint_url' OR name = 'webhook.secret_token' OR name LIKE 'webhook.events.%'"
        )->fetchAllAssociative();

        $url = '';
        $secret = '';
        $events = [];
        $eventPrefix = 'webhook.events.';
        foreach ($rows as $row) {
            $name = (string) $row['name'];
            $value = (string) ($row['value'] ?? '');
            if ($name === 'webhook.endpoint_url') {
                $url = $value;
            } elseif ($name === 'webhook.secret_token') {
                $secret = $value;
            } elseif (str_starts_with($name, $eventPrefix) && \in_array($value, ['1', 'true'], true)) {
                $events[] = substr($name, \strlen($eventPrefix));
            }
        }

        // Only materialize an endpoint entry when a URL was actually set; empty URL → disabled.
        if ($url !== '') {
            $existing = $conn->executeQuery(
                "SELECT COUNT(*) FROM kimai2_configuration WHERE name = 'webhook.endpoints'"
            )->fetchOne();

            $payload = json_encode(
                [['url' => $url, 'secret' => $secret, 'events' => array_values($events)]],
                \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR
            );

            if ((int) $existing > 0) {
                $this->addSql(
                    "UPDATE kimai2_configuration SET value = :payload WHERE name = 'webhook.endpoints'",
                    ['payload' => $payload]
                );
            } else {
                $this->addSql(
                    "INSERT INTO kimai2_configuration (name, value) VALUES ('webhook.endpoints', :payload)",
                    ['payload' => $payload]
                );
            }
        }

        $this->addSql("DELETE FROM kimai2_configuration WHERE name = 'webhook.endpoint_url'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE name = 'webhook.secret_token'");
        $this->addSql("DELETE FROM kimai2_configuration WHERE name LIKE 'webhook.events.%'");
    }

    public function down(Schema $schema): void
    {
        // Not auto-reversible: the multi-endpoint format holds richer state than the
        // single-endpoint trio can represent. Fail loudly so a rollback doesn't leave
        // the DB in the new shape while older code expects the old shape (silent
        // webhook outage). An operator downgrading must manually copy one endpoint's
        // fields back into webhook.endpoint_url / secret_token / events.* and then
        // mark this migration as not executed.
        throw new IrreversibleMigration(
            'Version20260417120000 is not reversible: webhook config was consolidated to a JSON blob. Restore the single-endpoint rows manually before re-applying the old schema.'
        );
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
