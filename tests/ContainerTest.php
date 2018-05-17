<?php
use Skiphog\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testGetObject()
    {
        $this->assertInstanceOf(StdClass::class, Container::get(StdClass::class));
    }

    public function testGetCachedObject()
    {
        $this->assertEquals(Container::get(StdClass::class), Container::get(StdClass::class));
    }

    public function testSet()
    {
        $class = new StdClass();
        Container::set('std', $class);
        $this->assertEquals($class, Container::get('std'));
        Container::set('std', $class);
        $this->assertEquals($class, Container::get('std'));
    }

    /**
     * @expectedException Exception
     */
    public function testNotExistsClassInContainer()
    {
        Container::get('NotExists');
    }

    public function testSetClosure()
    {
        Container::set('closure', function () {
            return new StdClass();
        });

        $this->assertInstanceOf(StdClass::class, Container::get('closure'));
    }

    public function testResolveDependencies()
    {
        $baz = Container::get(Baz::class);
        $this->assertInstanceOf('Baz', $baz);
    }

    /**
     * @expectedException Exception
     */
    public function testExeptionUnableToInstance()
    {
        $instance = Container::get(Privat::class);
    }

    /**
     * @expectedException Exception
     */
    public function testExeptionUnableToInstanceInstance()
    {
        $instance = Container::get(InstancePrivate::class);
    }


}

class Foo {}
class Bar { public function __construct(Foo $foo) {} }
class Baz { public function __construct(Bar $bar) {} }

class Privat { private function __construct() {} }
class InstancePrivate { public function __construct(Privat $privat) {} }