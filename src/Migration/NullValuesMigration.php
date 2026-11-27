<?php

namespace Alnv\ContaoTranslationManagerBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Replaces legacy NULL values in tl_translation before the schema diff
 * switches the columns to NOT NULL (otherwise MySQL strict mode aborts with
 * "1265 Data truncated for column ...").
 */
class NullValuesMigration extends AbstractMigration
{
    private const TABLE = 'tl_translation';

    /**
     * Column => replacement value for NULL.
     */
    private const COLUMNS = [
        'tstamp' => 0,
        'name' => '',
        'invisible' => '',
    ];

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Translation Manager: replace NULL values in tl_translation';
    }

    public function shouldRun(): bool
    {
        $columns = $this->getAffectedColumns();

        if (!$columns) {
            return false;
        }

        $conditions = array_map(
            fn (string $column): string => $this->connection->quoteIdentifier($column) . ' IS NULL',
            $columns
        );

        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ' . self::TABLE . ' WHERE ' . implode(' OR ', $conditions)
        );
    }

    public function run(): MigrationResult
    {
        $total = 0;

        foreach ($this->getAffectedColumns() as $column) {
            $quoted = $this->connection->quoteIdentifier($column);
            $total += (int) $this->connection->executeStatement(
                'UPDATE ' . self::TABLE . ' SET ' . $quoted . ' = ? WHERE ' . $quoted . ' IS NULL',
                [self::COLUMNS[$column]]
            );
        }

        return $this->createResult(true, sprintf('Replaced NULL values in %d field(s) of %s.', $total, self::TABLE));
    }

    /**
     * @return list<string>
     */
    private function getAffectedColumns(): array
    {
        $schemaManager = method_exists($this->connection, 'createSchemaManager')
            ? $this->connection->createSchemaManager()
            : $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return [];
        }

        $existing = array_change_key_case($schemaManager->listTableColumns(self::TABLE));

        return array_values(array_filter(
            array_keys(self::COLUMNS),
            static fn (string $column): bool => isset($existing[$column]) && !$existing[$column]->getNotnull()
        ));
    }
}
