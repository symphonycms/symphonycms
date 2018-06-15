# Database API

The database API shipped with Symphony leverages the capabilities given by php's
[PDO database abstraction extension](http://php.net/manual/en/book.pdo.php).
But, since it is not possible to get truly safe dynamic SQL statement, even with PDO, it was
important to build a new API with Symphony's needs in mind, which requires dynamic table and column names.

Hence, the API was designed with the following goals, in that order:

1. Safety/security
2. Ease of use
3. Performance
4. Almost 100% retro-compatible with LTS APIs.

## Breaking changes in `3.0.0`

Before `3.0.0`, all database operations resided in the `MySQL` class.
This class as been renamed to `Database` in `3.0.0`.
In order to make it easier to migrate to `3.0.0`, it still ships a `MySQL` class, but does not use it.
In future versions, it will be removed, so make sure that you access everything via `Symphony::Database()` instance.

There used to be two ways to execute SQL queries: `query()` and `fetch()`.
`fetch()` was used when we wanted to get tabular results.
In all other cases, `query()` was used.

Light wrappers around those two methods did exist.
Developers could either call `fetchCol()`, `fetchRow()` or `fetchVar()` to reduce the tabular data in a single call.
This was less than optimal, because the signatures of those functions where hard to remember and error prone.
Developers could also call `update()`, `insert()` and `delete()` to issue quick write statements.
But again, this was not flexible.
Those six functions still exist and work as they used to, but they got refactored.
This compatibility layer will be removed in future versions, so make sure to update your code with the new API.

## API

In order to execute SQL statements, the first thing to do is to create a `DatabaseStatement` object.
The `Database` class provides factory methods that allow the creation of all derived `DatabaseStatement` classes.
Once the statement object is created, configuration and execution of the statement are left to the caller.
This makes it possible to pass statements around before the statement gets executed.
It also removes the need to concat and parse strings, since all this heavy lifting is done under the hood.

The API uses chains of command that return the current instance.
This often eliminates the need to create statement variables and go straight to the result.

In order to execute the statement, developers needs to call the `execute()` method, which returns
a `DatabaseStatementResult` object.
The result exposes a `success()` method and a direct access to the underlying `PDO` result.

```php
$stm = Symphony::Database()->statement('This is a raw SQL statement');
$result = $stm->execute();
if (!$result->success()) {
    die('It did not worked');
}
```

When the query returns tabular data, `execute()` will return a `DatabaseTabularResult` instance.
This class inherits from `DatabaseStatementResult` and add methods to be able to access the data with ease.
When possible, the old vocabulary is used.
This makes `DatabaseTabularResult` expose methods like `rows()`, `next()`, `column()`, `rowsIndexedByColumn()`, `variable()`, `integer()`, `float()`, `boolean()` and `string()`.

Note that if there was a SQL error, `PDO` is configured to throw an exception.
We wrap those errors in a `DatabaseStatementException` in order to provide more context.

Each specialized `DatabaseStatement` exposes methods that are specific to its usage.
For example, `DatabaseQuery`, `DatabaseUpdate` and `DatabaseDelete` all share a [`where()`](#where) method.
Most methods are named by the associated SQL keywords they create.

Specialized `DatabaseStatement` are created via factory methods on the `Database` object.
Here is the list of all factory methods, the type of object they create and their basic usage:

### `select()` -> `DatabaseQuery`
```php
Symphony::Database()
    ->select(['t.*'])
    ->from('table')
    ->as('t')
    ->leftJoin('join', 'j')
    ->on(['t.id' => '$j.other_id'])
    ->where(['j.x' => 2])
    ->limit(2)
    ->execute()
    ->rows();
```

### `show()` -> `DatabaseShow`
```php
Symphony::Database()->show()->like('tbl_%')->execute()->rows();
```

### `showColumns()` -> `DatabaseShow`
```php
Symphony::Database()->showColumns()->where(['x' => 'name'])->execute()->rows();
```

### `showIndex()` -> `DatabaseShow`
```php
Symphony::Database()->showIndex()->where(['x' => 'name'])->execute()->rows();
```

### `insert()` -> `DatabaseInsert`
```php
Symphony::Database()
    ->insert('table')
    ->values([
        'x' => 1,
        'y' => 'TEST',
        'z' => true
    ])
    ->execute()
    ->success();
```

### `update()` -> `DatabaseUpdate`
```php
Symphony::Database()
    ->update('table')
    ->set([
        'x' => 1,
        'y' => 'TEST',
    ])
    ->execute()
    ->success();
```

### `delete()` -> `DatabaseDelete`
```php
Symphony::Database()
    ->delete('table')
    ->where(['id' => 2])
    ->limit(1)
    ->execute()
    ->success();
```

### `drop()` -> `DatabaseDrop`
```php
Symphony::Database()->drop('table')->table('table2')->execute()->success();
```

### `dropIfExists()` -> `DatabaseDrop`
```php
Symphony::Database()->dropIfExists('table')->execute()->success();
```

### `describe()` -> `DatabaseDescribe`
```php
Symphony::Database()->describe('table')->field('field')->execute()->rows();
```

### `create()` -> `DatabaseCreate`
```php
Symphony::Database()
    ->create('table')
    ->fields([
        'x' => [
            'type' => 'varchar(100)',
            'default' => 'TATA',
        ],
        'y' => [
            'type' => 'datetime',
            'default' => '2012-01-01 12:12:12'
        ],
        'z' => [
            'type' => 'enum',
            'values' => ['yes', 'no'],
            'default' => 'yes',
        ],
        'id' => [
            'type' => 'int(11)',
            'auto' => true
        ]
    ])
    ->keys([
        'id' => 'primary',
        'x1' => [
            'type' => 'unique',
            'cols' => ['x', 'y']
        ],
    ])
    ->execute()
    ->success();
```

### `alter()` -> `DatabaseAlter`
```php
Symphony::Database()
    ->alter('table')
    ->add([
        'x' => 'varchar(100)'
    ])
    ->first()
    ->change('z', [
        'y' => 'varchar(200)'
    ])
    ->dropKey('x')
    ->execute()
    ->success();
```

### `rename()` -> `DatabaseRename`
```php
Symphony::Database()
    ->rename('table')
    ->to('new_table_name')
    ->execute()
    ->success();
```

### `optimize()` -> `DatabaseOptimize`
```php
Symphony::Database()->optimize('table')->execute()->success();
```

### `truncate()` -> `DatabaseTruncate`
```php
Symphony::Database()->truncate('table')->execute()->success();
```

### `set()` -> `DatabaseSet`
```php
Symphony::Database()->set('variable')->value('value')->execute()->success();
```

### `transaction()` -> `DatabaseTransaction`
```php
Symphony::Database()
    ->transaction(function (\Database $db){
        $db->insert(...);
        $db->update(...);
    })
    ->execute()
    ->success();
```

### `statement()` -> `DatabaseStatement`
```php
// THIS IS UNSAFE!!! Make sure that user submitted data is properly escaped!
// Theoretically, you should not need it.
Symphony::Database()->statement('Raw SQL')->execute()->success();
```

## Statements safety

All `DatabaseStatement` make sure that they generate SAFE SQL, by escaping all values and checking the
resulting SQL string to invalid characters, such as `'`, `\` and `--`.
In order to make the compatibility layer work, SQL strings can be added using the `unsafeAppendSQLPart()` method and then flagged as unsafe by calling `unsafe()`.
This turns off most of the validity checks.
Developers should always try to avoid using `statement()` and `unsafeAppendSQLPart()`.

## Migration

Replace all `MySQL` references. Instead use `Symphony::Database()`.
We now try to avoid static members, so it is always safer to use the instance instead.

Replace `fetch()`, `fetchCol()`, `fetchRow()` and `fetchVar()` with `select()`.
Then use the `DatabaseQueryResult` returned by `execute()` to manipulate the actual result
by either calling `rows()`, `next()`, `column()`, `variable()`.

Update calls to `update()`, `insert()` and `delete()` and pass a single argument to them: the table name.
Keeping the multiple parameters will make the compatibility layer delegate the calls to the old
implementation that has been moved in `_update()`, `_insert()` and `_delete()`.

Please note that `delete()` used to work with a single argument, deleting ALL records in the table.
This is not supported by the compatibility layer.

Update calls to `query()` must be replaced with their safe equivalent, using the provided factory methods.

With `mysqli` all values needed to be properly escaped manually by the developers.
Calls to `quote()`, `quoteFields()`, `cleanValue()`, `cleanFields()` can be removed since escaping
is made automatically when using the safe methods.
You can now pass user submitted input directly to the database API (except when using raw SQL statement).

Calls to `determineQueryType()` must be removed, since it shouldn't be needed anymore.
Finally, `debug()` has been renamed `getLogs()`.

## API Stability

Even if the API is new, the public PHP API should be pretty stable.
Developers should not depend on public methods marked as `@internal`.
Developers should also not depend on the string of generated SQL statement, as this can change overtime.

## Array syntax

Almost all methods expect a single argument, which is either a value or an array.
The basic rule of thumb when dealing with arrays is that keys represent column names and values are literals.

There are 3 more complex syntaxes, that are defined in `DatabaseWhereDefinition`,
`DatabaseKeyDefinition` and `DatabaseColumnDefinition`, which respectively describe
`WHERE`, `KEY` and `COLUMN` definitions.

For a more complete overview of the possibilities, you should check the tests under the `tests` directory.

### where()

`where()` uses an "array syntax" to express the filters.
For more information, check the PHP doc.

```php
// With a basic query
$stm = Symphony::Database()->select(['col'])->from('table');

// By default, the key is the column name and the value is the literal value to look for.
$stm->where(['x' => 'y']);

// The value can be also be a [operator => value] pair
$stm->where(['x' => ['<' => 1]]);

// It supports many many operators
$stm->where(['or' => [
    ['x' => ['<' => 1]],
    ['y' => 'x']
]]);
$stm->where(['or' => [
    'in' => ['x' => ['y', 'z']]
]]);

// Even dates!
$stm->where(['or' => [
    'date' => [
        'start' => 'today',
        'end' => 'tomorrow',
    ]
]]);

// To use a column name as the value, simple prefix it with `$`
$stm->where(['x' => '$id']);

// Functions are also supported!
$stm->where(['x' => ['<=' => 'SUM(total)']]);

// Sub queries are first class citizens
$sub = $stm->select(['col'])->from('table');
$stm->where(['x' => ['in' => $sub]]);

// Note that calling where() multiple times on the same statement will
// join all filters with an AND operation.
```

## keys()

`keys()` uses an "array syntax" to express all the values needed to create table keys.
For more information, check the PHP doc.

```php
// With a statement (alter or create)
$stm = Symphony::Database()->create('table');

// You can create simple keys with a single column (the name of the column will be used as the key name)
$stm->keys(['col' => 'type']);

// There are 5 valid types: 'primary', 'key', 'index', 'unique', 'fulltext'
$stm->keys([
    'id' => 'primary',
    'text' => 'fulltext',
    'ssn' => 'unique'
]);

// Using a string as the array value is a shortcut to the complete form, which can specify options
$stm->keys(['col' => [
    'type' => 'type',
    'cols' => ['col']
]]);

// Keys can reference multiple columns
$stm->keys(['xid' => [
    'type' => 'index',
    'cols' => ['x', 'id']
]]);

// Which can also accept a size definition
$stm->keys(['xid' => [
    'type' => 'index',
    'cols' => [
        'x' => 333,
        'id' => 333
    ]
]]);

// When using alter
$stm = Symphony::Database()->alter('table');

// The syntax for the key definition is the same, but the right method must be used
$stm->addKey([...])
    ->dropKey('key')
    ->addIndex([...])
    ->dropIndex('index')
    ->addPrimaryKey('id')
    ->dropPrimaryKey();
```

## fields()

`fields()` uses an "array syntax" to express all the values needed to create table fields.
For more information, check the PHP doc.

```php
// With a statement (alter or create)
$stm = Symphony::Database()->create('table');

// You can create simple columns
$stm->fields(['col' => 'type']);

// There are 5 valid types: 'varchar', 'text', 'enum()', 'int()', 'datetime'
$stm->fields([
    'id' => 'int(11)',
    'text' => 'text',
    'ssn' => 'varchar(16)'
]);

// Using a string as the array value is a shortcut to the complete form, which can specify options
$stm->fields(['id' => [
    'type' => 'int(11)',
    'signed' => false,
    'null' => false,
    'collate' => 'utf8',
    'default' => 'DEF',
]]);

// Integer be auto-increment (which disable null and default values)
$stm->fields(['id' => [
    'type' => 'int(11)',
    'auto' => true
]]);

// The default collate of the table will be used when not set otherwise
$stm->collate('utf8')
    ->fields([
        'text' => 'text', // Will use utf8 collate
    ]);

// When using alter
$stm = Symphony::Database()->alter('table');

// The syntax for the key definition is the same, but the right method must be used
$stm->add([...])->after('col')
    ->drop('col')
    ->change('col', [...])
    ->modify([...]);
```
