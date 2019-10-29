<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers EntryQuery
 */
final class EntryQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = (new \EntryQuery($this->db));
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e`",
            $q->generateSQL(),
            'Simple new EntryQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjection()
    {
        $q = (new \EntryQuery($this->db))->disableDefaultSort()->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `e`.* FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery with Default schema and Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaNoDefaultProjection()
    {
        $q = (new \EntryQuery($this->db))->disableDefaultProjection()->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with Default schema without Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testCustomProjectionNoDefaultProjection()
    {
        $q = (new \EntryQuery($this->db));
        $q->projection($q->getMinimalProjection())->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `e`.`id`, `e`.`creation_date`, `e`.`modification_date` FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with Minimal Projection without Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaDefaultProjectionDefaultSort()
    {
        $q = (new \EntryQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `e`.* FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with Default schema Default projection and Default sort'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testCountProjection()
    {
        $q = (new \EntryQuery($this->db))->projection(['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testCount()
    {
        $q = (new \EntryQuery($this->db))->count();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery test with ->count()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSectionFilter()
    {
        $q = (new \EntryQuery($this->db))->section(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`section_id` = :e_section_id",
            $q->generateSQL(),
            'new EntryQuery with ->section()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['e_section_id'], 'e_section_id is 4');
        $this->assertEquals(1, count($values), '1 value');
        $this->assertEquals(4, $q->sectionId(), 'Section id is 4');
    }

    public function testEntryFilter()
    {
        $q = (new \EntryQuery($this->db))->entry(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`id` = :e_id",
            $q->generateSQL(),
            'new EntryQuery test ->entry()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['e_id'], 'e_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testEntriesFilter()
    {
        $q = (new \EntryQuery($this->db))->entries([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`id` IN (:e_id, :e_id2, :e_id3)",
            $q->generateSQL(),
            'new EntryQuery test ->entries()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['e_id'], 'e_id is 4');
        $this->assertEquals(5, $values['e_id2'], 'e_id2 is 5');
        $this->assertEquals(6, $values['e_id3'], 'e_id3 is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testInnerJoinField()
    {
        $q = (new \EntryQuery($this->db))->innerJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` INNER JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->innerJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
        $q->innerJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` INNER JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with DOUBLE ->innerJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testJoinField()
    {
        $q = (new \EntryQuery($this->db))->joinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->joinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
        $q->joinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with DOUBLE ->joinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testLeftJoinField()
    {
        $q = (new \EntryQuery($this->db))->leftJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->leftJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
        $q->leftJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with DOUBLE ->leftJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testOuterJoinField()
    {
        $q = (new \EntryQuery($this->db))->outerJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` OUTER JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->outerJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
        $q->outerJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` OUTER JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with DOUBLE ->outerJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testRightJoinField()
    {
        $q = (new \EntryQuery($this->db))->rightJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` RIGHT JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with ->rightJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
        $q->rightJoinField(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` RIGHT JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id`",
            $q->generateSQL(),
            'new EntryQuery test with DOUBLE ->rightJoinField()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testWhereField()
    {
        $q = (new \EntryQuery($this->db))->whereField(4, ['f4.value' => 4]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id` WHERE `f4`.`value` = :f4_value",
            $q->generateSQL(),
            'new EntryQuery with ->whereField() with a single filter'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f4_value'], 'f4_value is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testWhereFieldComplexFilter()
    {
        $q = (new \EntryQuery($this->db))->whereField(4, [
            'or' => [
                'f4.value' => ['!=' => 4]
            ]
        ]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` LEFT JOIN `entries_data_4` AS `f4` ON `e`.`id` = `f4`.`entry_id` WHERE (`f4`.`value` != :f4_value)",
            $q->generateSQL(),
            'new EntryQuery with ->whereField() with a complex filter'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['f4_value'], 'f4_value is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testFilterEmpty()
    {
        $q = (new \EntryQuery($this->db))->filter('', []);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery with ->filter("", [])'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testFilterSystemIdOR()
    {
        $q = (new \EntryQuery($this->db))->filter('system:id', [1,2], 'or');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE (`e`.`id` = :e_id OR `e`.`id` = :e_id2)",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:id, or, [])'
        );
        $values = $q->getValues();
        $this->assertEquals(1, $values['e_id'], 'e_id is 1');
        $this->assertEquals(2, $values['e_id2'], 'e_id2 is 2');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testFilterSystemIdAND()
    {
        $q = (new \EntryQuery($this->db))->filter('system:id', [1, 2], 'and');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE (`e`.`id` = :e_id AND `e`.`id` = :e_id2)",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:id, or, [])'
        );
        $values = $q->getValues();
        $this->assertEquals(1, $values['e_id'], 'e_id is 1');
        $this->assertEquals(2, $values['e_id2'], 'e_id2 is 2');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testFilterSystemIdNotAND()
    {
        $q = (new \EntryQuery($this->db))->filter('system:id', ['not: 1', 2, 0, ''], 'and');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE (`e`.`id` != :e_id AND `e`.`id` != :e_id2)",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:id, or, [])'
        );
        $values = $q->getValues();
        $this->assertEquals(1, $values['e_id'], 'e_id is 1');
        $this->assertEquals(2, $values['e_id2'], 'e_id2 is 2');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testFilterSystemIdEmpty()
    {
        $q = (new \EntryQuery($this->db))->filter('system:id', [], 'and');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:id, [])'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testFilterSystemIdInvalid()
    {
        $q = (new \EntryQuery($this->db))->filter('system:id', ['sss'], 'and');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`id` = :e_id",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:id, [ssss])'
        );
        $values = $q->getValues();
        // Invalid filters create a ` = 0` filter to make sure it returns nothing
        $this->assertEquals(1, count($values), '1 value');
        $this->assertEquals(0, $values['e_id'], 'e_id is 0');
    }

    public function testFilterSystemIdNull()
    {
        $q = (new \EntryQuery($this->db))->filter('system:id', [null], 'and');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE `e`.`id` = :e_id",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:id, [null])'
        );
        $values = $q->getValues();
        // Invalid filters create a ` = 0` filter to make sure it returns nothing
        $this->assertEquals(1, count($values), '1 value');
        $this->assertEquals(0, $values['e_id'], 'e_id is 0');
    }

    public function testFilterSystemCreationDate()
    {
        $q = (new \EntryQuery($this->db))->filter('system:creation-date', ['2018-03-16']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE ((`e`.`creation_date` >= :e_creation_date AND `e`.`creation_date` <= :e_creation_date2))",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:creation-date, [])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-03-16 00:00:00', $values['e_creation_date'], 'e_creation_date is 2018-03-16 00:00:00');
        $this->assertEquals('2018-03-16 23:59:59', $values['e_creation_date2'], 'e_creation_date2 is 2018-03-16 23:59:59');
        $this->assertEquals(2, count($values), '2 values');
    }

    public function testFilterSystemModificationDate()
    {
        $q = (new \EntryQuery($this->db))->filter('system:modification-date', ['2018-03-16']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` WHERE ((`e`.`modification_date` >= :e_modification_date AND `e`.`modification_date` <= :e_modification_date2))",
            $q->generateSQL(),
            'new EntryQuery with ->filter(system:modification-date, [])'
        );
        $values = $q->getValues();
        $this->assertEquals('2018-03-16 00:00:00', $values['e_modification_date'], 'e_modification_date is 2018-03-16 00:00:00');
        $this->assertEquals('2018-03-16 23:59:59', $values['e_modification_date2'], 'e_modification_date2 is 2018-03-16 23:59:59');
        $this->assertEquals(2, count($values), '2 values');
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testFilterFieldObject()
    {
        $q = (new \EntryQuery($this->db))->filter(new \Field(), [1]);
    }

    public function testSortRand()
    {
        $q = (new \EntryQuery($this->db))->sort('system:id', 'RAND');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY RAND()",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id, RAND)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortCreationDateDesc()
    {
        $q = (new \EntryQuery($this->db))->sort('system:creation-date', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`creation_date` DESC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:creation-date, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortModificationDate()
    {
        $q = (new \EntryQuery($this->db))->sort('system:modification-date');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`modification_date` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:modification-date)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortSystemId()
    {
        $q = (new \EntryQuery($this->db))->sort('system:id');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSortSystemId()
    {
        $q = (new \EntryQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `e`.* FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSortEmpty()
    {
        $q = (new \EntryQuery($this->db))->sort('');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery with ->sort("")'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSortInvalid()
    {
        $q = (new \EntryQuery($this->db))->sort(new \stdClass);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(<invalid>)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSystemSortStillAddsTheDefaultProjection()
    {
        $q = (new \EntryQuery($this->db))->sort('system:creation-date')->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `e`.* FROM `entries` AS `e` ORDER BY `e`.`creation_date` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort("created-date")->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSortStillAddsTheDefaultProjection()
    {
        $q = (new \EntryQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `e`.* FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with default sort from ->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testFieldSortStillAddsTheDefaultProjection()
    {
        $q = (new \EntryQuery($this->db));
        $f = new \FieldInput();
        $f->set('id', 1);
        $f->getEntryQueryFieldAdapter()->sort($q);
        $q->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f1`.`value` , `e`.* FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` ORDER BY `f1`.`value` ASC , `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with field sort and ->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testFieldSortStillAddsTheDefaultProjectionWithASchema()
    {
        $q = (new \EntryQuery($this->db))->schema(['field']);
        $f = new \FieldInput();
        $f->set('id', 1);
        $f->getEntryQueryFieldAdapter()->sort($q);
        $q->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f1`.`value` , `e`.* FROM `entries` AS `e` LEFT JOIN `entries_data_1` AS `f1` ON `e`.`id` = `f1`.`entry_id` ORDER BY `f1`.`value` ASC , `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with schema, field sort and ->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testCustomProjectionStillAddsTheDefaultProjection()
    {
        $q = (new \EntryQuery($this->db));
        $q->projection(['f.test']);
        $q->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f`.`test` , `e`.* FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with projection and ->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testCustomProjectionStillAddsTheDefaultProjectionWithoutSort()
    {
        $q = (new \EntryQuery($this->db));
        $q->projection(['f.test']);
        $q->disableDefaultSort();
        $q->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f`.`test` , `e`.* FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery with projection, disableDefaultSort() and ->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultProjectionAddedWithSchema()
    {
        $q = (new \EntryQuery($this->db, ['schema'], ['f.test']));
        $q->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f`.`test` , `e`.* FROM `entries` AS `e` ORDER BY `e`.`id` ASC",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultProjectionAddedWithSchemaNorDefaultSort()
    {
        $q = (new \EntryQuery($this->db, ['schema'], ['f.test']));
        $q->disableDefaultSort();
        $q->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `f`.`test` , `e`.* FROM `entries` AS `e`",
            $q->generateSQL(),
            'new EntryQuery with ->sort(system:id)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
