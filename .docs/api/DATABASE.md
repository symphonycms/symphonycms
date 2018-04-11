# Database API

The database API shipped with Symphony leverages the capabilities given by php's
[PDO database abstraction extension](http://php.net/manual/en/book.pdo.php).
But, since it is not possible to get truly safe dynamic SQL statement, even with PDO, the API
was designed with the following goals, in that order:

1. Safety/security
2. Ease of use
3. Performance
4. Almost 100% retro-compatible with LTS APIs.
