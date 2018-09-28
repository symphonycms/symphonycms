<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryUploadAdapter
 */
final class EntryQueryUploadAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryUploadAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \Field();
            $f->set('id', 1);
        }
        return new \EntryQueryUploadAdapter($f);
    }

    public function testExactFileFilter()
    {
        $q = (new \EntryQuery($this->db));
        $this->defaultState = null;
        $o = $this->createAdapter();
        $o->filter($q, ['yes']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`file` = :f1_file",
            $q->generateSQL(),
            'Simple exact match ->filter([yes])'
        );
        $values = $q->getValues();
        $this->assertEquals('yes', $values['f1_file'], 'f1_file is yes');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testExactSizeFilter()
    {
        $q = (new \EntryQuery($this->db));
        $this->defaultState = null;
        $o = $this->createAdapter();
        $o->filter($q, ['size: 1']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`size` = :f1_size",
            $q->generateSQL(),
            'Simple exact match ->filter([size: 1])'
        );
        $values = $q->getValues();
        $this->assertEquals('1', $values['f1_size'], 'f1_size is 1');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testExactMimeTypeRegexpFilter()
    {
        $q = (new \EntryQuery($this->db));
        $this->defaultState = null;
        $o = $this->createAdapter();
        $o->filter($q, ['mimetype: regexp: jpe?g']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`mimetype` REGEXP :f1_mimetype",
            $q->generateSQL(),
            'Simple exact match ->filter([mimetype: regexp: jpe?g])'
        );
        $values = $q->getValues();
        $this->assertEquals('jpe?g', $values['f1_mimetype'], 'f1_mimetype is jpe?g');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSortAsc()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->sort($q, 'asc');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f1`.`file` FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` ORDER BY `f1`.`file` ASC , `e`.`id` ASC",
            $q->generateSQL(),
            'Simple asc sort ->sort(asc)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
