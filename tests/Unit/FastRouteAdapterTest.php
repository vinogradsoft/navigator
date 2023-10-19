<?php

namespace Unit;

use Navigator\BadParameterException;
use Navigator\FastRouteAdapter;
use PHPUnit\Framework\TestCase;

class FastRouteAdapterTest extends TestCase
{

    private FastRouteAdapter $fastRouteAdapter;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->fastRouteAdapter = new FastRouteAdapter();
    }

    /**
     * @dataProvider getData
     */
    public function testBuildDynamicPath($pattern, $placeholders, $expected)
    {
        $result = $this->fastRouteAdapter->buildDynamicPath($pattern, $placeholders);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array[]
     */
    public function getData()
    {
        return [
            ['/user/{id:\d+}[/{name}]', ['id' => 1], ['user', 1]],
            ['/user/{id:\d+}[/{name}]', ['id' => 1, 'name' => 'test'], ['user', 1, 'test']],
            ['/user/{id:\d+}', ['id' => 1], ['user', 1]],
            ['/user/{id:\d+}/{name}', ['id' => 1, 'name' => 'test'], ['user', '1', 'test']],
            ['/user[/{id:\d+}[/{name}]]', ['id' => 1, 'name' => 'test'], ['user', 1, 'test']],
            ['/user[/{id:\d+}[/{name}]]', ['id' => 1,], ['user', 1]],
            ['/user[/{id:\d+}[/{name}]]', [], ['user']],
            ['/user/{name}/{id:[0-9]+}', ['name' => 'test', 'id' => 1,], ['user', 'test', 1]],
            ['/user/{name}/{id:[a-z]+}', ['id' => 'aaaaaaa', 'name' => 'test'], ['user', 'test', 'aaaaaaa']],

            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}]]]]', ['id' => 1, 'name' => 'test', 'ids' => 2, 'names' => 'tests'], ['user', '1', 'test', '2', 'tests']],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}]]]]', ['id' => 1, 'name' => 'test', 'ids' => 2,], ['user', '1', 'test', '2']],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}]]]]', ['id' => 1, 'name' => 'test'], ['user', '1', 'test']],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}]]]]', ['id' => 1], ['user', '1']],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}]]]]', [], ['user']],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['key' => 40, 'id' => 1, 'name' => 'test', 'ids' => 2, 'names' => 'tests'], ['user', '1', 'test', '2', 'tests', '40']],

            ['/test/{param}', ['param' => 'paramvalue'], ['test', 'paramvalue']],
            ['/test/{param1}/test2/{param2}', ['param1' => 'param1value', 'param2' => 'param2value'], ['test', 'param1value', 'test2', 'param2value']],
            ['/test/{param:\d+}', ['param' => 1], ['test', 1]],
            ['/test[/{param}]', ['param' => 'paramvalue'], ['test', 'paramvalue']],
            ['/test[/{param}]', [], ['test']],
            ['/{foo-bar}', ['foo-bar' => 'foo-barvalue'], ['foo-barvalue']],
            ['/{_foo:.*}', ['_foo' => '_foovalue'], ['_foovalue']],
            ['/te{ param }st', ['param' => 'paramvalue'], ['teparamvaluest']],
            ['/test/{ param : \d{1,9} }', ['param' => 1], ['test', '1']],

            ['[test]', ['test' => true], ['test']],
            ['[test]', ['test' => false], ['']],
            ['/{param}[opt]', ['param' => 'paramvalue', 'opt' => true], ['paramvalueopt']],
            ['/{param}[opt]', ['param' => 'paramvalue', 'opt' => false], ['paramvalue']],
            ['/{param}[opt]', ['param' => 'paramvalue'], ['paramvalue']],
            ['/{param}[opt]', ['param' => 'value', 'opt' => true], ['valueopt']],
            ['/{param}[opt]', ['param' => 'value', 'opt' => false], ['value']],
            ['/{param}[opt]', ['param' => 'value'], ['value']],
            ['/test[opt]', ['opt' => true], ['testopt']],
            ['/test[opt]', ['opt' => false], ['test']],
        ];
    }

    /**
     * @dataProvider getBadData
     */
    public function testBuildDynamicPathException($pattern, $placeholders)
    {
        $this->expectException(BadParameterException::class);
        $this->fastRouteAdapter->buildDynamicPath($pattern, $placeholders);
    }

    /**
     * @return array[]
     */
    public function getBadData()
    {
        return [
            ['/user/{id:\d+}[/{name}]', []],
            ['/user/{id:\d+}', ['f' => 1]],
            ['/user/{id:\d+}/{test}', ['test' => 1]],
            ['/user/{id:\d+}', ['ids' => 1]],
            ['/user/{name}', []],
            ['/user/{name}', ['ids' => 1]],
            ['/user/{name}', ['names' => 'test']],
            ['/user/{id:\d+}/{ids:\d+}[/{name}]', ['id' => 1]],
            ['/user/{id:\d+}/{ids:\d+}[/{name}]', ['ids' => 1]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['name' => 'test', 'ids' => 2, 'names' => 'tests', 'key' => 40,]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['ids' => 2, 'names' => 'tests', 'key' => 40,]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['names' => 'tests', 'key' => 40,]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['key' => 40,]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]', ['key' => 40,]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['id' => 1, 'ids' => 2, 'names' => 'tests', 'key' => 'key',]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['id' => 1, 'name' => 'test', 'names' => 'tests', 'key' => 'key',]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['id' => 1, 'name' => 'test', 'ids' => 2, 'key' => 'key',]],
            ['/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]', ['id' => 1, 'name' => 'test', 'key' => 'key',]],
            ['/user[/{id:\d+}][/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]', ['id' => 1, 'name' => 'test', 'key' => 'key',]],
            ['/user/[/{name}]/{id:\d+}', ['id' => 1]],
            ['/user/[/{name}]/{id:\d+}', []],
            ['/user/[/{name}]/{id:\d+}', ['id' => 1, 'name' => 'name']],
            ['/user/[/{name}]/{id:\d+}', []],
            ['/user/[/{name}][/{id:\d+}]', ['id' => 1, 'name' => 'name']],
            ['/user/[/{name}][/{id:\d+}]', ['name' => 'name']],
            ['/user/[/{name}][/{id:\d+}]', []],
            ['/test[opt', []],
            ['/test[opt', ['opt' => true,]],
            ['/test[opt', ['opt' => false,]],
            ['/test[opt[opt2]', ['opt' => true, 'opt2' => true,]],
            ['/test[opt[opt2]', ['opt' => true, 'opt2' => false,]],
            ['/test[opt[opt2]', ['opt' => false, 'opt2' => true,]],
            ['/testopt]', []],
            ['/test[]', []],
            ['/test[[opt]]', ['opt' => true]],
            ['[[test]]', ['test' => true]],
            ['/test[/opt]/required', ['opt' => true]],
        ];
    }

    /**
     * @dataProvider getStaticData
     */
    public function testBuildStaticPath($pattern, $expected)
    {
        $result = $this->fastRouteAdapter->buildStaticPath($pattern);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getStaticData()
    {
        return [
            ['[test]', ''],
            ['', ''],
            ['/test', 'test'],
            ['/test/test2', 'test/test2'],
            ['/test[opt]', 'test'],
            ['/test[/{param}]', 'test'],
        ];
    }

    /**
     * @dataProvider getBadStaticData
     */
    public function testBuildStaticPathException($pattern)
    {
        $this->expectException(BadParameterException::class);
        $this->fastRouteAdapter->buildStaticPath($pattern);
    }

    /**
     * @return array
     */
    public function getBadStaticData()
    {
        return [
            ['/user/{id:\d+}'],
            ['/user/{id:\d+}/{name}'],
            ['/user/{name}/{id:[0-9]+}'],
            ['/user/{name}/{id:[a-z]+}'],

            ['/test/{param}'],
            ['/test/{param1}/test2/{param2}'],
            ['/test/{param:\d+}'],
            ['/{foo-bar}'],
            ['/{_foo:.*}'],
            ['/te{ param }st'],
            ['/test/{ param : \d{1,9} }'],
            ['/{param}[opt]'],
        ];
    }

}