<?php
declare(strict_types=1);

namespace Mycro;

use PDOException;
use PDO;

use function sprintf;

final class Connection extends Transaction
{
    private const DSN = 'mysql:dbname=%s;host=%s;port=%d';

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
}
