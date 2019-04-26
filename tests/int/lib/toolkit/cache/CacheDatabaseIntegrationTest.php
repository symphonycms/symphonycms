<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers CacheDatabase
 */
final class CacheDatabaseIntegrationTest extends TestCase
{
    private $driver;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->driver = new CacheDatabase(Symphony::Database());

        // Insert test data
        Symphony::Database()->insert('tbl_cache')
            ->values([
                'hash' => 'test',
                'creation' => time(),
                'expiry' => time() * 2,
                'data' => Cacheable::compressData('test'),
                'namespace' => 'test'
            ])
            ->updateOnDuplicateKey()
            ->execute();
    }

    public function testReadEmpty()
    {
        $this->assertEmpty($this->driver->read('non existing hash'));
        $this->assertEmpty($this->driver->read(null, 'non existing namespace'));
    }

    public function testReadHash()
    {
        $this->assertNotEmpty($this->driver->read('test'));
        $this->assertNotEmpty($this->driver->read(null, 'test'));
    }
}
