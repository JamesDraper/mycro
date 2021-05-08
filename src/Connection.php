<?php
declare(strict_types=1);

namespace Mycro;

use PDOException;
use PDO;

use Throwable;

use function call_user_func;
use function sprintf;

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
