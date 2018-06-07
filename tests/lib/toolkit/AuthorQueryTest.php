<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers AuthorQuery
 */
final class AuthorQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \AuthorQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `authors` AS `a`",
            $q->generateSQL(),
            'Simple new AuthorQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaWithDefaultProjection()
    {
        $q = (new \AuthorQuery($this->db))->disableDefaultSort()->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a`.* FROM `authors` AS `a`",
            $q->generateSQL(),
            'new AuthorQuery with Default schema and Default projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSchemaWithDefaultProjectionDefaultSort()
    {
        $q = (new \AuthorQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a`.* FROM `authors` AS `a` ORDER BY `a`.`id` ASC",
            $q->generateSQL(),
            'new AuthorQuery with Default schema, Default projection and default sort'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \AuthorQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `authors` AS `a`",
            $q->generateSQL(),
            'new AuthorQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testAuthorFilter()
    {
        $q = (new \AuthorQuery($this->db))->author(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `authors` AS `a` WHERE `a`.`id` = :a_id",
            $q->generateSQL(),
            'new AuthorQuery with ->author()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['a_id'], 'a_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testAuthorsFilter()
    {
        $q = (new \AuthorQuery($this->db))->authors([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `authors` AS `a` WHERE `a`.`id` IN (:a_id, :a_id2, :a_id3)",
            $q->generateSQL(),
            'new AuthorQuery with ->authors()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['a_id'], 'a_id is 4');
        $this->assertEquals(5, $values['a_id2'], 'a_id2 is 5');
        $this->assertEquals(6, $values['a_id3'], 'a_id3 is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testUsernameFilter()
    {
        $q = (new \AuthorQuery($this->db))->username('user');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `authors` AS `a` WHERE `a`.`username` = :a_username",
            $q->generateSQL(),
            'new AuthorQuery with ->username()'
        );
        $values = $q->getValues();
        $this->assertEquals('user', $values['a_username'], 'a_username is user');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testEmailFilter()
    {
        $q = (new \AuthorQuery($this->db))->email('email');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `authors` AS `a` WHERE `a`.`email` = :a_email",
            $q->generateSQL(),
            'new AuthorQuery with ->email()'
        );
        $values = $q->getValues();
        $this->assertEquals('email', $values['a_email'], 'a_email is email');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSort()
    {
        $q = (new \AuthorQuery($this->db))->sort('email');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `authors` AS `a` ORDER BY `a`.`email` ASC",
            $q->generateSQL(),
            'new AuthorQuery with ->sort()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultSort()
    {
        $q = (new \AuthorQuery($this->db))->finalize();
        $this->assertEquals(
            "SELECT SQL_NO_CACHE `a`.* FROM `authors` AS `a` ORDER BY `a`.`id` ASC",
            $q->generateSQL(),
            'new AuthorQuery with ->finalize()'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
