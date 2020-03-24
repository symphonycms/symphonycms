<?php

namespace Symphony\Toolkit\Tests;

use ArrayReducer;
use PHPUnit\Framework\TestCase;

/**
 * @covers ArrayReducer
 */
final class ArrayReducerTest extends TestCase
{
    public function testColumnNumeric()
    {
        $r = new ArrayReducer([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ]);
        $this->assertEquals([2, 5, 8], $r->column(1));
        $this->assertEquals([1, 4, 7], $r->column(0));
        $this->assertEquals([3, 6, 9], $r->column(2));
    }

    public function testColumnKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 4, 'b' => 5, 'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
        ]);
        $this->assertEquals([2, 5, 8], $r->column('b'));
        $this->assertEquals([1, 4, 7], $r->column('a'));
        $this->assertEquals([3, 6, 9], $r->column('c'));
    }

    public function testColumnKeyWithHole()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 4,           'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
        ]);
        $this->assertEquals([2, null, 8], $r->column('b'));
    }

    /**
     * @expectedException Exception
     */
    public function testColumnInvalidColType()
    {
        $r = new ArrayReducer([]);
        $r->column([]);
    }

    public function testEmptyArray()
    {
        $r = new ArrayReducer([]);
        $this->assertEquals([], $r->column(0));
        $this->assertEquals([], $r->column('b'));
        $this->assertEquals(null, $r->variable(0));
        $this->assertEquals(null, $r->variable('b'));
        $this->assertEquals('', $r->string(0));
        $this->assertEquals('', $r->string('b'));
        $this->assertEquals(0, $r->integer(0));
        $this->assertEquals(0, $r->integer('b'));
        $this->assertEquals(0.0, $r->float(0));
        $this->assertEquals(0.0, $r->float('b'));
        $this->assertEquals(false, $r->boolean(0));
        $this->assertEquals(false, $r->boolean('b'));
    }

    public function testRowsIndexedByColumnNumeric()
    {
        $data = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ];
        $r = new ArrayReducer($data);
        $i = $r->rowsIndexedByColumn(1);

        $this->assertEquals($data[0], $i[2]);
        $this->assertEquals($data[1], $i[5]);
        $this->assertEquals($data[2], $i[8]);

        $i = $r->rowsIndexedByColumn(0);
        $this->assertEquals($data[0], $i[1]);
        $this->assertEquals($data[1], $i[4]);
        $this->assertEquals($data[2], $i[7]);
    }

    public function testRowsIndexedByColumnKeys()
    {
        $data = [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 4, 'b' => 5, 'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
        ];
        $r = new ArrayReducer($data);
        $i = $r->rowsIndexedByColumn(1);

        $this->assertEquals(array_values($data[0]), $i[2]);
        $this->assertEquals(array_values($data[1]), $i[5]);
        $this->assertEquals(array_values($data[2]), $i[8]);

        $i = $r->rowsIndexedByColumn(0);
        $this->assertEquals(array_values($data[0]), $i[1]);
        $this->assertEquals(array_values($data[1]), $i[4]);
        $this->assertEquals(array_values($data[2]), $i[7]);
    }

    /**
     * @expectedException Exception
     */
    public function testRowsIndexedByColumnInvalidColType()
    {
        $r = new ArrayReducer([]);
        $r->rowsIndexedByColumn([]);
    }

    /**
     * @expectedException Exception
     */
    public function testRowsIndexedByColumnDuplicateIndex()
    {
        $r = new ArrayReducer([
            [1, 2, 3],
            [4, 5, 6],
            [1, 8, 9],
        ]);
        $r->rowsIndexedByColumn(0);
    }

    /**
     * @expectedException Exception
     */
    public function testRowsIndexedByColumnNullIndex()
    {
        $r = new ArrayReducer([
            [1, 2, 3],
            [4, 5, 6],
            [null, 8, 9],
        ]);
        $r->rowsIndexedByColumn(0);
    }

    public function testRowsGroupedByColumnNumeric()
    {
        $data = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
            [2, 2, 3],
            [3, 3, 3],
        ];
        $r = new ArrayReducer($data);
        $g = $r->rowsGroupedByColumn(1);
        $this->assertEquals(4, count($g));
        $this->assertEquals(2, count($g[2]));
        $this->assertEquals($data[0], $g[2][0]);
        $this->assertEquals($data[1], $g[5][0]);
        $this->assertEquals($data[2], $g[8][0]);
        $this->assertEquals($data[3], $g[2][1]);
        $this->assertEquals($data[4], $g[3][0]);

        $g = $r->rowsGroupedByColumn(2);
        $this->assertEquals(3, count($g));
        $this->assertEquals(3, count($g[3]));
        $this->assertEquals($data[0], $g[3][0]);
        $this->assertEquals($data[1], $g[6][0]);
        $this->assertEquals($data[2], $g[9][0]);
        $this->assertEquals($data[3], $g[3][1]);
        $this->assertEquals($data[4], $g[3][2]);
    }

    public function testRowsGroupedByColumnKey()
    {
        $data = [
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 4, 'b' => 5, 'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
            ['a' => 2, 'b' => 2, 'c' => 3],
            ['a' => 3, 'b' => 3, 'c' => 3],
        ];
        $r = new ArrayReducer($data);
        $g = $r->rowsGroupedByColumn('b');
        $this->assertEquals(4, count($g));
        $this->assertEquals(2, count($g[2]));
        $this->assertEquals($data[0], $g[2][0]);
        $this->assertEquals($data[1], $g[5][0]);
        $this->assertEquals($data[2], $g[8][0]);
        $this->assertEquals($data[3], $g[2][1]);
        $this->assertEquals($data[4], $g[3][0]);

        $g = $r->rowsGroupedByColumn('c');
        $this->assertEquals(3, count($g));
        $this->assertEquals(3, count($g[3]));
        $this->assertEquals($data[0], $g[3][0]);
        $this->assertEquals($data[1], $g[6][0]);
        $this->assertEquals($data[2], $g[9][0]);
        $this->assertEquals($data[3], $g[3][1]);
        $this->assertEquals($data[4], $g[3][2]);
    }

    /**
     * @expectedException Exception
     */
    public function testRowsGroupedByColumnInvalidColType()
    {
        $r = new ArrayReducer([]);
        $r->rowsGroupedByColumn([]);
    }

    /**
     * @expectedException Exception
     */
    public function testRowsGroupedByColumnHole()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 4,           'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
        ]);
        $r->rowsIndexedByColumn('b');
    }

    public function testVariableNumeric()
    {
        $r = new ArrayReducer([
            [1, true, 'test'],
            [4, 5, 6],
            [7, 8, 9],
        ]);
        $this->assertEquals(true, $r->variable(1));
        $r->reset();
        $this->assertEquals(1, $r->variable(0));
        $r->reset();
        $this->assertEquals('test', $r->variable(2));
    }

    public function testVariableKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => true, 'c' => 'test'],
        ]);
        $this->assertEquals(true, $r->variable('b'));
        $r->reset();
        $this->assertEquals(1, $r->variable('a'));
        $r->reset();
        $this->assertEquals('test', $r->variable('c'));
    }

    public function testVariableHole()
    {
        $r = new ArrayReducer([
            ['a' => 1,           'c' => 3],
            ['a' => 4, 'b' => 5, 'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
        ]);
        $this->assertEquals(null, $r->variable('b'));
    }

    public function testVariableMultipleKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => 2, 'c' => 3],
            ['a' => 4, 'b' => 5, 'c' => 6],
            ['a' => 7, 'b' => 8, 'c' => 9],
        ]);
        $this->assertEquals(2, $r->variable('b'));
        $this->assertEquals(5, $r->variable('b'));
        $this->assertEquals(8, $r->variable('b'));
        $this->assertEquals(null, $r->variable('b'));
    }

    /**
     * @expectedException Exception
     */
    public function testVariableInvalidColType()
    {
        $r = new ArrayReducer([]);
        $r->variable([]);
    }

    public function testStringKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => true, 'c' => 'test'],
        ]);
        $this->assertEquals('1', $r->string('b'));
        $r->reset();
        $this->assertEquals('1', $r->string('a'));
        $r->reset();
        $this->assertEquals('test', $r->string('c'));
    }

    public function testIntegerKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => true, 'c' => 'test'],
        ]);
        $this->assertEquals(1, $r->integer('b'));
        $r->reset();
        $this->assertEquals(1, $r->integer('a'));
        $r->reset();
        $this->assertEquals(0, $r->integer('c'));
    }

    public function testFloatKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => true, 'c' => '1.23'],
        ]);
        $this->assertEquals(1.0, $r->float('b'));
        $r->reset();
        $this->assertEquals(1.0, $r->float('a'));
        $r->reset();
        $this->assertEquals(1.23, $r->float('c'));
    }

    public function testBooleanKey()
    {
        $r = new ArrayReducer([
            ['a' => 1, 'b' => false, 'c' => 'yes'],
        ]);
        $this->assertEquals(false, $r->boolean('b'));
        $r->reset();
        $this->assertEquals(true, $r->boolean('a'));
        $r->reset();
        $this->assertEquals(true, $r->boolean('c'));
    }
}
