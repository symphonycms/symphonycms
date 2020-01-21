<?php

/**
 * @package toolkit
 */

/**
 * @since Symphony 3.0.0
 */
trait DatabaseCacheableExecutionDefinition
{
    /**
     * First tries to see if there is an identical query in the cache. If it does
     * a cache data is used as the underlying values.
     * If not, the call to Database::execute() is made, and the DatabaseStatementResult
     * will be used to produce the values.
     *
     * @uses DatabaseStatement::computeHash()
     * @uses DatabaseStatement::finalize()
     * @see DatabaseStatement::execute()
     * @return DatabaseStatementCachedResult
     * @throws DatabaseException
     */
    final public function useCacheOrExecute()
    {
        $stmKey = $this->computeHash();
        $this->finalize();
        if ($this->getDB()->cache()->has($stmKey)) {
            return $this->cachedResults($stmKey, null);
        }
        $result = $this->getDB()->execute($this);
        return $this->cachedResults($stmKey, $result);
    }

    /**
     * Factory function that creates a new DatabaseStatementCachedResult based upon the $result
     * parameter.
     *
     * @param string $stmKey
     *  The statement's key, that will be used as the cache key
     * @param DatabaseStatementResult $result
     *  The underlying DatabaseStatementResult, if any.
     *  When the cache can be used, $result will be null, since it never gets created
     * @return DatabaseStatementCachedResult
     */
    public function cachedResults($stmKey, $result)
    {
        return new DatabaseStatementCachedResult(
            $result,
            $this->getDB()->cache(),
            $stmKey
        );
    }
}
