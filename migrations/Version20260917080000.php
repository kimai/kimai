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

/**
 * @version 2.69
 */
final class Version20260917080000 extends AbstractMigration
{
    /**
     * Copy of "kimai_translated_locales" from config/services.yaml: the language is used in the URL and only
     * translated locales are accepted by the router. Hardcoded, because migrations must not depend on the container.
     */
    private const TRANSLATED_LOCALES = ['ar', 'bg', 'ca', 'cs', 'da', 'de', 'de_CH', 'el', 'en', 'eo', 'es', 'eu', 'fa', 'fi', 'fo', 'fr', 'he', 'hr', 'hu', 'id', 'it', 'ja', 'ko', 'nb_NO', 'nl', 'pa', 'pl', 'pt', 'pt_BR', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'ta', 'tr', 'uk', 'vi', 'zh_CN', 'zh_Hant', 'zh_Hant_TW'];

    public function getDescription(): string
    {
        return 'Replace user languages without translation (e.g. de_DE) by the nearest translated locale';
    }

    public function up(Schema $schema): void
    {
        $languages = $this->connection->fetchFirstColumn("SELECT DISTINCT `value` FROM kimai2_user_preferences WHERE `name` = 'language'");

        $changed = false;
        foreach ($languages as $language) {
            if (!\is_string($language) || \in_array($language, self::TRANSLATED_LOCALES, true)) {
                continue;
            }

            $this->addSql("UPDATE kimai2_user_preferences SET `value` = :new WHERE `name` = 'language' AND `value` = :old", [
                'new' => self::getNearestTranslationLocale($language),
                'old' => $language,
            ]);
            $changed = true;
        }

        if (!$changed) {
            $this->preventEmptyMigrationWarning();
        }
    }

    public function down(Schema $schema): void
    {
        $this->preventEmptyMigrationWarning();
    }

    public function isTransactional(): bool
    {
        return true;
    }

    /**
     * Same logic as LocaleService::getNearestTranslationLocale()
     */
    public static function getNearestTranslationLocale(string $locale): string
    {
        $base = explode('_', $locale)[0];
        if (\strlen($base) === 2 && \in_array($base, self::TRANSLATED_LOCALES, true)) {
            return $base;
        }

        return 'en';
    }
}
