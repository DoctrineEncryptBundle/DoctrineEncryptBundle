<?php

namespace Ambta\DoctrineEncryptBundle\Tests\Functional;

use Ambta\DoctrineEncryptBundle\Tests\DoctrineCompatibilityTrait;
use PHPUnit\Framework\Constraint\LogicalNot;
use PHPUnit\Framework\Constraint\StringContains;
use PHPUnit\Framework\TestCase;

abstract class AbstractFunctionalTestCase extends TestCase
{
    use DoctrineCompatibilityTrait;

    /**
     * Get all queries.
     */
    protected function getAllDebugQueries(): array
    {
        if (!\Composer\InstalledVersions::satisfies(new \Composer\Semver\VersionParser(), 'symfony/doctrine-bridge', '>=6.4')) {
            return $this->sqlLoggerStack->queries;
        }

        $data = $this->debugDataHolder->getData();

        return isset($data['default']) ? $data['default'] : [];
    }

    /**
     * Get all queries, except ones containing the word 'SAVEPOINT'.
     *
     * The use of savepoints changes between different versions of doctrine/dbal, so let's ignore those.
     */
    protected function getDebugQueries(): array
    {
        return array_filter(
            $this->getAllDebugQueries(),
            static function ($queryData) {
                return stripos($queryData['sql'], 'SAVEPOINT') === false;
            }
        );
    }

    protected function getLatestInsertQuery(): ?array
    {
        $insertQueries = array_values(array_filter($this->getDebugQueries(), static function ($queryData) {
            return stripos($queryData['sql'], 'INSERT ') === 0;
        }));

        return current(array_reverse($insertQueries)) ?: null;
    }

    protected function getLatestUpdateQuery(): ?array
    {
        $updateQueries = array_values(array_filter($this->getDebugQueries(), static function ($queryData) {
            return stripos($queryData['sql'], 'UPDATE ') === 0;
        }));

        return current(array_reverse($updateQueries)) ?: null;
    }

    /**
     * Using the SQL Logger Stack this method retrieves the current query count executed in this test.
     */
    protected function getCurrentQueryCount(): int
    {
        return count($this->getDebugQueries());
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param string $string
     * @param string $message
     */
    public function assertStringDoesNotContain($needle, $string, $ignoreCase = false, $message = ''): void
    {
        static::assertIsString($needle, $message);
        static::assertIsString($string, $message);
        static::assertIsBool($ignoreCase, $message);

        $constraint = new LogicalNot(new StringContains(
            $needle,
            $ignoreCase
        ));

        static::assertThat($string, $constraint, $message);
    }
}
