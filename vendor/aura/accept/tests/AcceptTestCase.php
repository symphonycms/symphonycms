<?php
namespace Aura\Accept;

class AcceptTestCase extends \PHPUnit_Framework_TestCase
{
    protected function assertAcceptValues($actual, $expect, $negotiator_class, $value_class)
    {
        $this->assertInstanceOf($negotiator_class, $actual);
        $this->assertSameSize($actual->get(), $expect);

        foreach ($actual as $key => $item) {
            $this->assertInstanceOf($value_class, $item);
            foreach ($expect[$key] as $func => $value) {
                $func = 'get' . $func;
                $this->assertEquals($value, $item->$func());
            }
        }
    }
}
