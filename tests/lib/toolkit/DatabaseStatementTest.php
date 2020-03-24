<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers DatabaseStatement
 */
final class DatabaseStatementTest extends TestCase
{
    public function testGetDB()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('getDB');
        $method->setAccessible(true);
        $this->assertInstanceOf('\Database', $method->invoke($sql));
        $this->assertEquals($db, $method->invoke($sql));
    }

    public function testGetSQLParts()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->unsafeAppendSQLPart('statement', 'part');
        $this->assertNotEmpty($sql->getSQLParts('statement'));
        $this->assertNotEmpty($sql->getSQLParts(['statement', 'test']));
        $this->assertEmpty($sql->getSQLParts('test'));
        $this->assertEmpty($sql->getSQLParts(['test1', 'test']));
    }

    public function testContainsSQLParts()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->unsafeAppendSQLPart('statement', 'part');
        $this->assertTrue($sql->containsSQLParts('statement'));
        $this->assertTrue($sql->containsSQLParts(['statement', 'test']));
        $this->assertFalse($sql->containsSQLParts('test'));
        $this->assertFalse($sql->containsSQLParts(['test1', 'test']));
    }

    public function testGetValues()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $this->assertTrue(is_array($sql->getValues()));
        $this->assertEmpty($sql->getValues());
    }

    public function testAppendValuesStringKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('appendValues');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [[
            'test' => 1,
            'null' => null,
            'string' => 'test',
        ]]);
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(3, count($sql->getValues()));
        $this->assertArrayHasKey('test', $sql->getValues());
        $this->assertArrayHasKey('_null_', $sql->getValues());
        $this->assertArrayHasKey('string', $sql->getValues());
    }

    public function testAppendValuesStringKeysMultipleCalls()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('appendValues');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [[
            'test' => 1,
        ]]);
        $method->invokeArgs($sql, [[
            'null' => null,
        ]]);
        $method->invokeArgs($sql, [[
            'string' => 'test',
        ]]);
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(3, count($sql->getValues()));
        $this->assertArrayHasKey('test', $sql->getValues());
        $this->assertArrayHasKey('_null_', $sql->getValues());
        $this->assertArrayHasKey('string', $sql->getValues());
    }

    public function testAppendValuesNumericKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('appendValues');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [[1, null, 'test']]);
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(3, count($sql->getValues()));
        $this->assertArrayHasKey(0, $sql->getValues());
        $this->assertArrayHasKey(1, $sql->getValues());
        $this->assertArrayHasKey(2, $sql->getValues());
    }

    public function testAppendValuesNumericKeysMultipleCalls()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('appendValues');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [[1]]);
        $method->invokeArgs($sql, [[null]]);
        $method->invokeArgs($sql, [['test']]);
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(3, count($sql->getValues()));
        $this->assertArrayHasKey(0, $sql->getValues());
        $this->assertArrayHasKey(1, $sql->getValues());
        $this->assertArrayHasKey(2, $sql->getValues());
    }

    public function testAppendValuesStringKeysMultipleCallsWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->usePlaceholders();
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('appendValues');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [[
            'test' => 1,
        ]]);
        $method->invokeArgs($sql, [[
            'null' => null,
        ]]);
        $method->invokeArgs($sql, [[
            'string' => 'test',
        ]]);
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(3, count($sql->getValues()));
        $this->assertArrayHasKey(0, $sql->getValues());
        $this->assertArrayHasKey(1, $sql->getValues());
        $this->assertArrayHasKey(2, $sql->getValues());
    }

    public function testSetValue()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->setValue('test', 1);
        $sql->setValue('null', null);
        $sql->setValue('string', 'test');
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(3, count($sql->getValues()));
        $this->assertArrayHasKey('test', $sql->getValues());
        $this->assertArrayHasKey('null', $sql->getValues());
        $this->assertArrayHasKey('string', $sql->getValues());
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSetValueDuplicate()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->setValue('test', 1);
        $sql->setValue('test', 2);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSetValueArrayAsKey()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->setValue([], 1);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSetValueObjectAsKey()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->setValue($sql, 1);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSetValueBoolAsKey()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->setValue(true, 1);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSetValueIntAsKey()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $sql->setValue(0, 1);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSetValueStringAsKeyWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->usePlaceholders();
        $sql->setValue('test', 1);
    }

    public function testSetValueIntAsKeyWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->usePlaceholders();
        $sql->setValue(0, 1);
        $sql->setValue(1, 0);
        $this->assertNotEmpty($sql->getValues());
        $this->assertEquals(2, count($sql->getValues()));
        $this->assertArrayHasKey(0, $sql->getValues());
        $this->assertArrayHasKey(1, $sql->getValues());
    }

    public function testUsePlaceholders()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->usePlaceholders();
        $this->assertTrue($sql->isUsingPlaceholders());
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testUsePlaceholdersAfterValuesAdded()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('appendValues');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [['x' => 'x']]);
        $sql->usePlaceholders();
    }

    public function testUnsafe()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->unsafe();
        $this->assertFalse($sql->isSafe());
    }

    public function testComputeHash()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $hash = $sql->computeHash();
        $this->assertEquals('ef31f4b846ebb16ee1074ee4808f22b3', $hash);
        $hash = $sql->unsafeAppendSQLPart('statement', 'test')->computeHash();
        $this->assertEquals('cef614ec23649e2f63942c8a65e7dca5', $hash);
        $hash = $sql->usePlaceholders()->computeHash();
        $this->assertEquals('efdab66e5a15b1349571827625e81de5', $hash);
        $hash = $sql->setValue(0, 'test')->computeHash();
        $this->assertEquals('d758c817957aa56d8f51c6faacca3829', $hash);
    }

    public function testFinalize()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $this->assertInstanceOf('\DatabaseStatement', $sql->finalize());
        $this->assertEquals($sql, $sql->finalize());
    }

    public function testReplaceTablePrefix()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $this->assertEquals('test', $sql->replaceTablePrefix('tbl_test'));
        $db->setPrefix('sym_');
        $this->assertEquals('sym_test', $sql->replaceTablePrefix('tbl_test'));
        $this->assertEquals('sym_tbl_test', $sql->replaceTablePrefix('tbl_tbl_test'));
        $this->assertEquals('sym_tbl_test sym_test2', $sql->replaceTablePrefix('tbl_tbl_test tbl_test2'));
        $db->setPrefix('tbl_');
        $this->assertEquals('tbl_test', $sql->replaceTablePrefix('tbl_test'));
    }

    public function testAsPlaceholderStringStringAsKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $this->assertEquals(':name', $sql->asPlaceholderString('name', 1));
        $this->assertEquals(':test', $sql->asPlaceholderString('test', ''));
        $this->assertEquals(':_null_', $sql->asPlaceholderString('null', null));
    }

    public function testAsPlaceholderStringIntAsKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $this->assertEquals(':name', $sql->asPlaceholderString('name', 1));
        $this->assertEquals(':test', $sql->asPlaceholderString('test', ''));
        $this->assertEquals(':_null_', $sql->asPlaceholderString('null', null));
    }

    public function testAsPlaceholderStringIntAsKeysWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->usePlaceholders();
        $this->assertEquals('?', $sql->asPlaceholderString(0, 1));
        $this->assertEquals('?', $sql->asPlaceholderString(1, ''));
        $this->assertEquals('?', $sql->asPlaceholderString(2, null));
    }

    public function testAsPlaceholderStringStringAsKeysWithPlaceholders()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST')->usePlaceholders();
        $this->assertEquals('?', $sql->asPlaceholderString('name', 1));
        $this->assertEquals('?', $sql->asPlaceholderString('test', ''));
        $this->assertEquals('?', $sql->asPlaceholderString('null', null));
    }

    public function testAsPlaceholdersListStringAsKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $list = $sql->asPlaceholdersList([
            'name' => 1,
            'null' => null,
            'test' => '',
        ]);
        $this->assertEquals(':name, :_null_, :test', $list);
    }

    public function testAsPlaceholdersListIntAsKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('TEST');
        $list = $sql->asPlaceholdersList([1, null, '']);
        $this->assertEquals('?, ?, ?', $list);
    }

    public function testAsTickedString()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x`', $sql->asTickedString('x'));
        $this->assertEquals('`x`', $sql->asTickedString(['x']));
        $this->assertEquals('`x`, `y`', $sql->asTickedString(['x', 'y']));
        $this->assertEquals('`x`, `y`', $sql->asTickedString(['x,y']));
        $this->assertEquals('*', $sql->asTickedString('*'));
        $this->assertEquals(null, $sql->asTickedString(null));
        $this->assertEquals('`x`', $sql->asTickedString('x`'));
        $this->assertEquals('`x`', $sql->asTickedString('`x`'));
        $this->assertEquals('`xtest`', $sql->asTickedString('x`test'));
        $this->assertEquals('`x`.`y`', $sql->asTickedString('x.y'));
        $this->assertEquals('`x`.`y`', $sql->asTickedString('x.`y`'));
        $this->assertEquals('`x`.`y`.`z`', $sql->asTickedString('x.y.z'));
        $this->assertEquals('`x-test`', $sql->asTickedString('x-test'));
        $this->assertEquals('`x_test`', $sql->asTickedString('x_test'));
        $this->assertEquals('COUNT(*)', $sql->asTickedString('COUNT(*)'));
        $this->assertEquals('COUNT(`x`)', $sql->asTickedString('COUNT(x)'));
        $this->assertEquals('COUNT(`x`) AS `c`', $sql->asTickedString('COUNT(x)', 'c'));
        $this->assertEquals(':x', $sql->asTickedString(':x'));
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testAsTickedStringInvalidParameterName()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals(':é', $sql->asTickedString(':é'));
    }

    public function testAsTickedStringWithOperator()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x` - 1', $sql->asTickedString('x - 1'));
        $this->assertEquals('`x` + 1', $sql->asTickedString('x + 1'));
        $this->assertEquals('`x` * 1', $sql->asTickedString('x * 1'));
        $this->assertEquals('`x` / 1', $sql->asTickedString('x / 1'));
        $this->assertEquals('(`x` + 10) AS `t`', $sql->asTickedString('x + 10', 't'));
    }

    public function testAsTickedListStringAsKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x` AS `x`', $sql->asTickedList(['x' => 'x']));
        $this->assertEquals('`x` AS `a`, `y` AS `b`', $sql->asTickedList(['x' => 'a', 'y' => 'b']));
    }

    public function testAsTickedListIntAsKeys()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('`x`', $sql->asTickedList(['x']));
        $this->assertEquals('`x`, `y`', $sql->asTickedList(['x', 'y']));
    }

    public function testAsProjectionList()
    {
        $db = new Database([]);
        $sql = $db->select();
        $sub = $sql->select();
        $this->assertEquals('`x`, (SELECT) AS `y`', $sql->asProjectionList(['x', 'y' => $sub]));
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateFieldName()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('validateFieldName');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [' ']);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateTickedStringWithSpace()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('validateTickedString');
        $method->setAccessible(true);
        $method->invokeArgs($sql, [' ']);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateTickedStringWithValidNonFirstChar()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('validateTickedString');
        $method->setAccessible(true);
        $method->invokeArgs($sql, ['-']);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testValidateTickedStringWithLeadingDidgit()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('validateTickedString');
        $method->setAccessible(true);
        $method->invokeArgs($sql, ['0']);
    }

    public function testConvertToParameterName()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $this->assertEquals('x', $sql->convertToParameterName('x', 'x'));
        $this->assertEquals('x', $sql->convertToParameterName('x', 'x'));
        $this->assertEquals('x2', $sql->convertToParameterName('x', 'xx'));
        $this->assertEquals('x', $sql->convertToParameterName('x', 'x'));
        $this->assertEquals('_null_', $sql->convertToParameterName('x', null));
        $this->assertEquals('x_x', $sql->convertToParameterName('x-x', 'x'));
        $this->assertEquals('x_x', $sql->convertToParameterName('x.x', 'x'));
    }

    public function testFormatParameterName()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $class = new ReflectionClass($sql);
        $method = $class->getMethod('formatParameterName');
        $method->setAccessible(true);
        $this->assertEquals('x', $method->invokeArgs($sql, ['x']));
    }

    public function testGenerateSQL()
    {
        $db = new Database([]);
        $sql = $db->statement('1')->unsafeAppendSQLPart('statement', '2');
        $this->assertEquals('1 2', $sql->generateSQL());
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testGenerateSQLWithInvalidPart()
    {
        $db = new Database([]);
        $sql = $db->statement('1')->unsafeAppendSQLPart('statemen', '2');
        $this->assertEquals('1 2', $sql->generateSQL());
    }

    public function testGenerateFormattedSQL()
    {
        $db = new Database([]);
        $sql = $db->statement('1')->unsafeAppendSQLPart('statement', '2');
        $this->assertEquals('1 2', $sql->generateFormattedSQL());
        $this->assertEquals($sql->generateSQL(), $sql->generateFormattedSQL(), 'Formatted == Unformatted');
    }

    public function testSplitFunctionArguments()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments('X(a,  b,c),T(Z(R(a))), r,   GHZAS(a,G(b ),c),');
        $this->assertEquals('X(a,b,c)', $args[0]);
        $this->assertEquals('T(Z(R(a)))', $args[1]);
        $this->assertEquals('r', $args[2]);
        $this->assertEquals('GHZAS(a,G(b),c)', $args[3]);
        $this->assertEquals(4, count($args));
    }

    public function testSplitFunctionArgumentsFullyQualifiedWithTrailingZero()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments('XYZ(a130.super0,  test.b,c)');
        $this->assertEquals('XYZ(a130.super0,test.b,c)', $args[0]);
        $this->assertEquals(1, count($args));
    }

    public function testSplitFunctionArgumentsFullyQualifiedWithLeadingZero()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments('XYZ(0130a.0super11,  test.b,c)');
        $this->assertEquals('XYZ(0130a.0super11,test.b,c)', $args[0]);
        $this->assertEquals(1, count($args));
    }

    public function testSplitFunctionArgumentsFullyQualifiedWithWhitespaces()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments("XYZ(\ra130\t .super, \r\n \0test . b,\0c\x0B)");
        $this->assertEquals('XYZ(a130.super,test.b,c)', $args[0]);
        $this->assertEquals(1, count($args));
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSplitFunctionArgumentsImbalanced()
    {
        $db = new Database([]);
        $sql = $db->statement('');
        $args = $sql->splitFunctionArguments('X(a, ( b,c)');
        $this->assertEquals(0, count($args));
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionSimpleComment()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES('test')--,'test')");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionSharp()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES(:test)#,:test)");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionDoubleQuote()
    {
        $db = new Database([]);
        $sql = $db->statement('INSERT INTO `tbl` VALUES("test","test")');
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionSingleQuote()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES('test','test')");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionSemiColon()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES(:test,:est);");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionMultilineCommentStart()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES(/*:test,:est);");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLInjectionMultilineCommentEnd()
    {
        $db = new Database([]);
        $sql = $db->statement("INSERT INTO `tbl` VALUES(*/:test,:est);");
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql);
    }

    public function testSQLUnsafeInjection()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES('test')";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, false);
        $this->assertEquals($injectedSql, $sql);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLUnsafeInjectionFirstVariant()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES(:test'--)";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, false);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLUnsafeInjectionSecondVariant()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES(:test';--)";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, false);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLUnsafeInjectionThirdVariant()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES(:test' --)";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, false);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLUnsafeInjectionFourthVariant()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES(:test'/*)";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, false);
    }

    /**
     * @expectedException DatabaseStatementException
     */
    public function testSQLStrictUnsafeInjection()
    {
        $db = new Database([]);
        $injectedSql = "INSERT INTO `tbl` VALUES('test')";
        $sql = $db->statement($injectedSql);
        $sql = $sql->generateSQL();
        $db->validateSQLQuery($sql, true);
    }
}
