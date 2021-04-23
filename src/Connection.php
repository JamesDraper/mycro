<?php
declare(strict_types=1);

namespace Mycro;

use PDOException;
use PDOStatement;
use PDO;

use Throwable;

use function call_user_func;
use function gettype;
use function sprintf;

class Transaction
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

    protected function fetchPDO(): PDO
    {
        return $this->pdo;
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

final class Connection extends Transaction
{
    private const DSN = 'mysql:dbname=%s;host=%s;port=%d';

    private ?Transaction $transaction = null;

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
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            $message = sprintf('Could not connect to database: %s.', $database);

            throw new Exception($message, $e);
        }

        parent::__construct($pdo);
    }

    /**
     * @throws Throwable
     * @return mixed
     */
    public function transaction(callable $func)
    {
        $pdo = $this->fetchPDO();

        $pdo->beginTransaction();

        try {
            $result = call_user_func($func, $this->fetchTransaction());
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        $pdo->commit();

        return $result;
    }

    private function fetchTransaction(): Transaction
    {
        if (null === $this->transaction) {
            $this->transaction = new Transaction($this->fetchPDO());
        }

        return $this->transaction;
    }
}
