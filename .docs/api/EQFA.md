# Entry Query Field Adapters

- [Changes](#Changes)
- [Implementation in fields](#Implementation)
- [API Stability](#API-Stability)
- [Filtering](#Filtering)
- [Sorting](#Sorting)

New in `3.0.0`, Field now delegate filtering and sorting to another class, which must
inherits from `EntryQueryFieldAdapter`.

## Changes

This class replaces a lot of methods in the `Field` class.

1. `isFilterRegex()`
1. `buildRegexSQL()` renamed as `createFilterRegexp()`
1. `isFilterSQL()`
1. `buildFilterSQL()` renamed as `createFilterSQL()`
1. `buildDSRetrievalSQL()` renamed as `filter()`
1. `isRandomOrder()`
1. `buildSortingSQL()` renamed as `sort()`
1. `buildSortingSelectSQL()` dropped as it is not needed anymore.

The base `EntryQueryFieldAdapter` also have new helper methods that were not in the `Field` class.

1. `formatColumn()`
1. `createFilterEquality()`
1. `createFilterEqualityOrNull()`
1. `isFilterNotEqual()`
1. `createFilterNotEqual()`

The `EntryQuery` class leverages `EntryQueryFieldAdapter` and simply calls `filter()` and `sort()`, passing itself as a parameter.
`filter()` and `sort()` also bring the datasource filtering syntax deep into the core.
This makes it a lot easier to write queries against the database.

```php
$entries = (new EntryManager)
    ->select()
    ->section(1)
    ->filter('field-handle', ['test'])
    ->filter('other-field', ['not: test'])
    ->sort('system:creation-date')
    ->execute()
    ->rows();
```

## Implementation

The instance to use for filtering and sorting should be set in the Field's constructor, by setting the `$this->entryQueryFieldAdapter` property.

```php
public function __construct()
{
    parent::__construct();
    $this->entryQueryFieldAdapter = new EntryQueryFieldAdapter($this);
    ...
}
```

Developers now can create a new class that extends `EntryQueryFieldAdapter` when creating a new field.
Developers can also reuse an existing implementation.
Developers can also compose new behavior by aggregating multiple instances of different implementations.
Instances will hold a reference to the Field accessible via the protected member `$this->field`.

## API Stability

Even if the API is new, the public PHP API should be pretty stable.
Developers should not depend on public methods marked as `@internal`.
Filtering string syntax may change overtime.

## Filtering

The base implementation supports 4 filtering modes: 'not:', 'regexp:', 'sql:' and exact match (un-prefixed).

The main extension point for filtering is the `getFilterColumns()` method.
Developers should override this method in order to return an array of columns on which to filter.

```php
public function getFilterColumns()
{
    return ['value', 'handle'];
}
```

If more filtering modes are required, or if you want to remove support for the default one, overriding `filterSingle()` is the way to go.
This method is responsible for parsing and creating filters passed to `filter()` one by one.

```php
protected function filterSingle(EntryQuery $query, $filter)
{
    General::ensureType([
        'filter' => ['var' => $filter, 'type' => 'string'],
    ]);
    // Only allow equality filtering
    if ($this->isFilterNotEqual($filter)) {
        return $this->createFilterNotEqual($filter, $this->getFilterColumns());
    }
    return $this->createFilterEquality($filter, $this->getFilterColumns());
}
```

Finally, if the filtering requires checking the whole filter array, it is always possible to override `filter()`.
Doing so bypasses both `getFilterColumns()` and `filterSingle()`.
Developers are encouraged to continue using them in their custom implementation.
The following is a simplified version of the base implementation.

```php
public function filter(EntryQuery $query, array $filters, $operator = 'or')
{
    General::ensureType([
        'operator' => ['var' => $operator, 'type' => 'string'],
    ]);
    if (empty($filters)) {
        return;
    }
    $field_id = General::intval($this->field->get('id'));
    $conditions = [];

    foreach ($filters as $filter) {
        $fc = $this->filterSingle($query, $filter);
        if (is_array($fc)) {
            $conditions[] = $fc;
        }
    }
    if (empty($conditions)) {
        return;
    }

    $query->whereField($field_id, $conditions);
}
```

## Sorting

The base implementation supports 3 sorting directions: 'asc', 'desc' and 'random'.

The main extension point for filtering is the `getSortColumns()` method.
Developers should override this method in order to return an array of columns on which to sort.

```php
public function getSortColumns()
{
    return ['date'];
}
```

If you want to customize the sorting further, the `sort()` method can be overridden.
This even bypasses `getSortColumns()`.
Developers are encouraged to continue using it.
Developers should also use the `isRandomOrder()` and `formatColumn()` helpers to jump start their implementation.

```php
public function sort(EntryQuery $query, $direction = 'ASC')
{
    General::ensureType([
        'direction' => ['var' => $direction, 'type' => 'string'],
    ]);
    $field_id = General::intval($this->field->get('id'));
    if ($this->isRandomOrder($direction)) {
        $query->orderBy('RAND()');
        return;
    }
    // Customize query as needed
    $query->...
    // For each columns
    foreach ($this->getSortColumns() as $col) {
        // Order by
        $query->orderBy($this->formatColumn($col, $field_id), $direction);
    }
}
```
