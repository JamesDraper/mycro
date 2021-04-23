<?php
declare(strict_types=1);

namespace Mycro;

use PDOException;
use PDOStatement;
use PDO;

use function gettype;
use function sprintf;

final class Connection
{
    private const DSN = 'mysql:dbname=%s;host=%s;port=%d';

    private PDO $pdo;

    /**
     * @throws Exception
     */
    public function __construct(
        string $database,
        string $username,
        string $password,
        string $host = 'localhost',
        int $port = 3306
    ) {
        $dsn = sprintf(self::DSN, $database, $host, $port);

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            $message = sprintf('Could not connect to database: %s.', $database);

            throw new Exception($message, $e);
        }
    }

    /**
     * @throws Exception
     */
    public function query(string $sql, array $params): array
    {
        return $this
            ->prepareAndExecStatement($sql, $params)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @throws Exception
     */
    public function exec(string $sql, array $params): self
    {
        $this->prepareAndExecStatement($sql, $params);

        return $this;
    }

    /**
     * @throws Exception
     */
    private function prepareAndExecStatement(string $sql, array $params): PDOStatement
    {
        $statement = $this->prepareStatement($sql);

        foreach ($params as $name => $value) {
            $this->bindParamToStatement($statement, (string) $name, $value);
        }

        try {
            $statement->execute();
        } catch (PDOException $e) {
            $message = sprintf('Could not execute SQL: %s.', $sql);

            throw new Exception($message, $e);
        }

        return $statement;
    }

    /**
     * @throws Exception
     */
    private function bindParamToStatement(PDOStatement $statement, string $param, $value): void
    {
        $success = $statement->bindValue(
            '{' . $param . '}',
            $value,
            $this->fetchPdoType($value)
        );

        if (!$success) {
            throw new Exception('Could not bind parameter to statement: %s.', $param);
        }
    }

    /**
     * @throws Exception
     */
    private function fetchPdoType($value): int
    {
        switch ($phpType = gettype($value)) {
            case 'boolean':
                return PDO::PARAM_BOOL;

            case 'integer':
                return PDO::PARAM_INT;

            case 'string':
            case 'double':
                return PDO::PARAM_STR;

            case 'NULL':
                return PDO::PARAM_NULL;

            default:
                $message = sprintf('Invalid SQL parameter type: %s.', $phpType);

                throw new Exception($message);
        }
    }

    /**
     * @throws Exception
     */
    private function prepareStatement(string $sql): PDOStatement
    {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            $message = sprintf('Could not prepare SQL statement: %s.', $sql);

            throw new Exception($message, $e);
        }
    }
}
