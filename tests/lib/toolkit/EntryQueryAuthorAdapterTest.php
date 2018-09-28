<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQueryAuthorAdapter
 */
final class EntryQueryAuthorAdapterTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    /**
     * @param Field $f
     * @return EntryQueryAuthorAdapter
     */
    private function createAdapter(Field $f = null)
    {
        if (!$f) {
            $f = new \FieldAuthor();
            $f->set('id', 1);
        }
        return new \EntryQueryAuthorAdapter($f);
    }

    public function testExactFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['test']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE CONCAT_WS(:af_space_char, `af`.`first_name`, `af`.`last_name`) AS `af`.`full_name` FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE (`af`.`username` = :af_username OR `af`.`first_name` = :af_first_name OR `af`.`last_name` = :af_last_name OR `af`.`full_name` = :af_full_name)",
            $q->generateSQL(),
            'Simple exact match ->filter([test])'
        );
        $values = $q->getValues();
        $this->assertEquals('test', $values['af_first_name'], 'af_first_name is test');
        $this->assertEquals('test', $values['af_last_name'], 'af_last_name is test');
        $this->assertEquals('test', $values['af_full_name'], 'af_full_name is test');
        $this->assertEquals('test', $values['af_username'], 'af_username is test');
        $this->assertEquals(' ', $values['af_space_char'], 'af_space_char is space');
        $this->assertEquals(5, count($values), '5 values');
    }

    public function testAuthorIdFilter()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->filter($q, ['author-id: 4']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` WHERE `f1`.`author_id` = :f1_author_id",
            $q->generateSQL(),
            'Simple exact match ->filter([test])'
        );
        $values = $q->getValues();
        $this->assertEquals('4', $values['f1_author_id'], 'f1_author_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSortAsc()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->sort($q, 'asc');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `as`.`first_name`, `as`.`last_name` FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` INNER JOIN `authors` AS `as` ON `f1`.`author_id` = `as`.`id` ORDER BY `as`.`first_name` ASC , `as`.`last_name` ASC , `e`.`id` ASC",
            $q->generateSQL(),
            'Simple asc sort ->sort(asc)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortRand()
    {
        $q = (new \EntryQuery($this->db));
        $o = $this->createAdapter();
        $o->sort($q, 'random');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY RAND()",
            $q->generateSQL(),
            'Simple asc sort ->sort(random)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
