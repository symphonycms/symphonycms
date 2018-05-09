# Managers

Managers are responsible for the CRUD operations of Symphony objects such as `Author`, `Entry`,
`Field`, `Section`, `Page`, `Extension`, `EmailGateway`, `TextFormatter`, `DataSource` and `Event`.
Some managers only use the database as their storage while others use the disk.
Hence, there are data managers (database) and resources managers (disk).
Some managers even uses both storage facilities.

## Data Managers

The data objects are  `Author`, `Entry`, `Field`, `Section`, `Page` and `Extension`.

Data managers do not implement any specific interface, but they share common method signatures.
Under the hood, they rely on `Symphony::Database()` to execute their statements.
Writing to the database is done via the manipulations made on the objects directly and then
calling `commit()` as usual.

Fetching is made easier by deprecating `fetch()` and implementing specialized `DatabaseQuery` classes.
This allows for an infinite number of possible configurations and also make it possible to set default values,
like the name of the table or a default sort.
The specialized `DatabaseQuery` objects are created by calling the `select()` method.

```php
$query = (new EntryManager)->select();
```

The specialized [`DatabaseQuery`](DATABASE.md#API) objects offer quick ways to filter and sort the data it is responsible for.
This makes it even easier to use, since you do not have to remember (or know at all) the name of the columns.

```php
$query = (new EntryManager)
            ->select()
            ->section(1)
            ->sort('system:creation-date');
```

These specialized `DatabaseQuery` objects also come with a specialized `DatabaseQueryResult` class.
This make it easy to return fully constructed objects instead of plain rows from the database.

```php
$entries = (new EntryManager)
            ->select()
            ->section(1)
            ->sort('system:creation-date')
            ->execute()
            ->rows();
```

To learn more about the database API, [please read this document](DATABASE.md).

## Resources Managers

The resource objects are `Page`, `Extension`, `EmailGateway`, `TextFormatter`, `DataSource` and `Event`.

Resource managers implement the `FileResource` interface, which provides a `listAll()` and `create()` methods.
Writing new objects to the disk is specific to each manager.
