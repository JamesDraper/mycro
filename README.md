# Mycro

A minimalist PDO wrapper for MySQL databases.

## Creating a connection

The top level class is the `\Mycro\Connection` object. It takes 5 arguments,
the last two of which are optional:

- The database name as a string
- The username as a string
- The password as a string
- The host as a string, defaults to `localhost`
- The port as an int, defaults to `3306`

If a connection cannot be made, an instance of `\Mycro\Exception` is thrown.

## Querying a database

A database can be queried using the `query` method. The first parameter is the
SQL statement with placeholders, the second is a key-value array mapping
parameter names to values.

    $connection->query('SELECT * FROM table_name where id = {id}', [
        'id' => 123,
    ]);

Parameters can be referenced in the SQL string by their name surrounded by curly
brackets. If the query cannot be run then an instance of `\Mycro\Exception`
is thrown.

## Running an SQL statement

Alternatively, to run an SQL statement from which nothing should be returned
such as an `INSERT` statement or a `CREATE TABLE` statement. The `exec` method
can be used. It behaves in the same way as the `query` method, takes the same
parameters and throws the same exception if it fails. However, rather than
return an associative array, `exec` returns `self` for method chaining.

    $connection->exec('DELETE FROM table_name where id = {id}', [
        'id' => 123,
    ]);

## Transactions

Transactions can be run by using the `transaction` method. The transaction
method takes a callable which takes 1 argument. An object will be passed into
the callable which contains the `query` method and the `exec` method, but not
the `transaction` method.

If the entire callable is run from beginning to end, then the transaction is
committed. If an exception is thrown out of the closure, then it is caught,
the transaction is rolled back, and the error is re-thrown.

The transaction method returns whatever value is returned from the closure.

    $connection->transaction(function ($transaction) {
        $connection->exec('DELETE FROM table_name where id = {id}', [
            'id' => 123,
        ]);

        return $connection->query('SELECT * FROM table_name where id = {id}', [
            'id' => 456,
        ]);
    });
