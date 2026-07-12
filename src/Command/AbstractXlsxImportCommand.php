<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Shared skeleton of the one-off XLSX import commands (old `SUS\SiteBundle\Command\*`).
 *
 * The old commands copy-pasted these private helpers per file and loaded the workbook through
 * the abandoned liuggio/excelbundle (`xls.load_xls2007`, PHPExcel); the port centralizes them
 * here on phpoffice/phpspreadsheet. Despite the "CSV" names, all importers read XLSX files
 * (sheet 0, header row 1, data from row 2 — the old `ignoreFirstLine` option was never used).
 *
 * `findEntityFromMMDictionary()` in the old code opened a raw PDO connection with HARDCODED
 * credentials (root@localhost/mmsch, or the app's own DB). The port replaces that with:
 *  - a lazily-opened PDO connection configured via MMSCH_DB_DSN / MMSCH_DB_USERNAME /
 *    MMSCH_DB_PASSWORD env vars (lookups against the MM database), and
 *  - parameterized queries instead of the old string-interpolated SQL.
 * The connection is only opened when a lookup actually runs (in the old code several commands
 * connected eagerly without ever using it).
 */
abstract class AbstractXlsxImportCommand
{
    private ?\PDO $mmDictionaryPdo = null;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        #[Autowire(env: 'MMSCH_DB_DSN')]
        private readonly string $mmDbDsn = '',
        #[Autowire(env: 'MMSCH_DB_USERNAME')]
        private readonly string $mmDbUsername = '',
        #[Autowire(env: 'MMSCH_DB_PASSWORD')]
        private readonly string $mmDbPassword = '',
    ) {
    }

    /**
     * Old `parseCSV()`: resolves the --file option and returns sheet 0 of the workbook.
     */
    protected function loadWorksheet(?string $file): Worksheet
    {
        if ($file === null || $file === '') {
            throw new \RuntimeException('The --file option is required (xls file to import from).');
        }
        $path = realpath($file);
        if ($path === false || !is_file($path)) {
            throw new \RuntimeException('File not found: ' . $file);
        }

        return IOFactory::load($path)->getSheet(0);
    }

    /**
     * @return list<mixed> header row values, in column order
     */
    protected function parseHeadersToArray(Row $headersRow): array
    {
        $cellIterator = $headersRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $result = [];
        foreach ($cellIterator as $cell) {
            $result[] = $cell->getValue();
        }

        return $result;
    }

    /**
     * @param list<mixed> $headers
     *
     * @return array<mixed> row values keyed by header
     */
    protected function parseRowToArray(Row $row, array $headers): array
    {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $result = [];
        $i = 0;
        foreach ($cellIterator as $cell) {
            $result[$headers[$i]] = $cell->getValue();
            ++$i;
        }

        return $result;
    }

    /**
     * Old `findEntityFromMMDictionary()`: look the value up in a dictionary table of the MM
     * database, then resolve the corresponding LOCAL Doctrine entity by the mapped field.
     *
     * @param class-string $repo
     */
    protected function findEntityFromMMDictionary(string $table, string $idField, mixed $value, string $repo, string $fieldToSearchDb, string $fieldToSearchRepo): ?object
    {
        if ($value == '') {
            return null;
        }
        $query = 'SELECT * FROM `' . $table . '` WHERE ' . $idField . ' = ?';
        $stmt = $this->getMMDictionaryPdo()->prepare($query);
        $stmt->execute([$value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \Exception($query . "\n" . var_export($stmt->errorInfo(), true));
        }
        $entity = $this->em->getRepository($repo)->findOneBy([$fieldToSearchRepo => $row[$fieldToSearchDb]]);
        if (!isset($entity)) {
            throw new \Exception('Entity not found: ' . $table . '.' . $value);
        }

        return $entity;
    }

    protected function getMMDictionaryPdo(): \PDO
    {
        if ($this->mmDictionaryPdo === null) {
            if ($this->mmDbDsn === '') {
                throw new \RuntimeException('MMSCH_DB_DSN is not configured — this import needs a connection to the MM database for its dictionary lookups (e.g. "mysql:host=localhost;dbname=mmsch;charset=utf8").');
            }
            $this->mmDictionaryPdo = new \PDO($this->mmDbDsn, $this->mmDbUsername, $this->mmDbPassword);
        }

        return $this->mmDictionaryPdo;
    }

    protected function str(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    protected function intOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
