<?php

namespace Unit\UrlBuilder;

use Compass\Exception\InvalidUrlException;
use Navigator\ArrayRulesProvider;
use Navigator\BadParameterException;
use Navigator\UrlBuilder;
use PHPUnit\Framework\TestCase;
use Test\Cases\Dummy\ReferralUrlStrategy;

class BuildTest extends TestCase
{

    const BASE_URL = 'https://vinograd.soft';

    protected UrlBuilder $urlBuilder;

    /**
     * @param string|null $name
     * @param array $data
     * @param $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->urlBuilder = new UrlBuilder(
            self::BASE_URL,
            new ArrayRulesProvider([
                'user1' => '/user/{id:\d+}[/{name}]',
                'user2' => '/user[/{id:\d+}[/{name}]]',
                'user3' => '/user/{id:\d+}/{name}',
                'user4' => '/user/{name}/{id:[0-9]+}',
                'user5' => '/user/{name}/{id:[a-z]+}',
                'user6' => '/user/{id:\d+}',
                'user7' => '/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}]]]]',
                'user8' => '/user[/{id:\d+}[/{name}[/{ids:\d+}[/{names}/{key:\d+}]]]]',

                'test1' => '/test/{param}',
                'test2' => '/test[/{param}]',
                'test3' => '/test/{param:\d+}',
                'test4' => '/test/{param1}/test2/{param2}',
                'test5' => '/test/{ param : \d{1,4} }',
                'test6' => '[test]',
                'test7' => '/test[opt]',

                'foo-bar' => '/{foo-bar}',
                '_foo' => '/{_foo:.*}',
                'te' => '/te{ param }st',
                'param/opt' => '/{param}[opt]',

                'static1' => '[test]',
                'static2' => '',
                'static3' => '/test',
                'static4' => '/test/test2',
                'static5' => '/test[opt]',
                'static6' => '/test[/{param}]',
            ])
        );
    }

    /**
     * @return void
     */
    public function testGetBaseUrl()
    {
        $baseUrl = $this->urlBuilder->getBaseUrl();
        self::assertEquals(self::BASE_URL, $baseUrl);
    }

    /**
     * @dataProvider getData
     */
    public function testBuild($routName, $placeholders, $expected, $absolute, $slash)
    {
        $result = $this->urlBuilder->build($slash ? '/' . $routName : $routName, $placeholders, $absolute);
        if (!$absolute) {
            if ($expected === '') {
                self::assertEquals('/', $result);
            } else {
                self::assertEquals($slash ? '/' . $expected : $expected, $result);
            }
        } else {
            if ($expected === '') {
                self::assertEquals(self::BASE_URL, $result);
            } else {
                self::assertEquals(self::BASE_URL . '/' . $expected, $result);
            }
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return array_merge(
            $this->getTestData(true),
            $this->getTestData(true, true),
            $this->getTestData(false),
            $this->getTestData(false, true),
        );
    }

    /**
     * @param bool $absolute
     * @param bool $slash
     * @return array[]
     */
    protected function getTestData(bool $absolute, bool $slash = false): array
    {
        return [
            ['user1', ['id' => 1], 'user/1', $absolute, $slash],
            ['user1', ['id' => 1, 'name' => 'test'], 'user/1/test', $absolute, $slash],
            ['user6', ['id' => 1], 'user/1', $absolute, $slash],
            ['user3', ['id' => 1, 'name' => 'test'], 'user/1/test', $absolute, $slash],
            ['user2', ['id' => 1, 'name' => 'test'], 'user/1/test', $absolute, $slash],
            ['user2', ['id' => 1,], 'user/1', $absolute, $slash],
            ['user2', [], 'user', $absolute, $slash],
            ['user4', ['name' => 'test', 'id' => 1,], 'user/test/1', $absolute, $slash],
            ['user5', ['id' => 'aaaaaaa', 'name' => 'test'], 'user/test/aaaaaaa', $absolute, $slash],
            ['user7', ['id' => 1, 'name' => 'test', 'ids' => 2, 'names' => 'tests'], 'user/1/test/2/tests', $absolute, $slash],
            ['user7', ['id' => 1, 'name' => 'test', 'ids' => 2,], 'user/1/test/2', $absolute, $slash],
            ['user7', ['id' => 1, 'name' => 'test'], 'user/1/test', $absolute, $slash],
            ['user7', ['id' => 1], 'user/1', $absolute, $slash],
            ['user7', [], 'user', $absolute, $slash],
            ['user8', ['key' => 40, 'id' => 1, 'name' => 'test', 'ids' => 2, 'names' => 'tests'], 'user/1/test/2/tests/40', $absolute, $slash],

            ['test1', ['param' => 'paramvalue'], 'test/paramvalue', $absolute, $slash],
            ['test4', ['param1' => 'param1value', 'param2' => 'param2value'], 'test/param1value/test2/param2value', $absolute, $slash],
            ['test3', ['param' => 1], 'test/1', $absolute, $slash],
            ['test2', ['param' => 'paramvalue'], 'test/paramvalue', $absolute, $slash],
            ['test2', [], 'test', $absolute, $slash],
            ['foo-bar', ['foo-bar' => 'foo-barvalue'], 'foo-barvalue', $absolute, $slash],
            ['_foo', ['_foo' => '_foovalue'], '_foovalue', $absolute, $slash],
            ['te', ['param' => 'paramvalue'], 'teparamvaluest', $absolute, $slash],
            ['test5', ['param' => 1], 'test/1', $absolute, $slash],

            ['test6', ['test' => true], 'test', $absolute, $slash],
            ['test6', ['test' => false], '', $absolute, $slash],

            ['param/opt', ['param' => 'paramvalue', 'opt' => true], 'paramvalueopt', $absolute, $slash],
            ['param/opt', ['param' => 'paramvalue', 'opt' => false], 'paramvalue', $absolute, $slash],
            ['param/opt', ['param' => 'paramvalue'], 'paramvalue', $absolute, $slash],
            ['param/opt', ['param' => 'value', 'opt' => true], 'valueopt', $absolute, $slash],
            ['param/opt', ['param' => 'value', 'opt' => false], 'value', $absolute, $slash],
            ['param/opt', ['param' => 'value'], 'value', $absolute, $slash],
            ['test7', ['opt' => true], 'testopt', $absolute, $slash],
            ['test7', ['opt' => false], 'test', $absolute, $slash],

            ['static1', null, '', $absolute, $slash],
            ['static2', null, '', $absolute, $slash],

            ['static3', null, 'test', $absolute, $slash],
            ['static4', null, 'test/test2', $absolute, $slash],
            ['static5', null, 'test', $absolute, $slash],
            ['static6', null, 'test', $absolute, $slash],
        ];
    }

    /**
     * @dataProvider getBadData
     */
    public function testBuildException1($routName, $placeholders)
    {
        $this->expectException(BadParameterException::class);
        $this->urlBuilder->build($routName, $placeholders);
    }

    /**
     * @dataProvider getBadData
     */
    public function testBuildException2($routName, $placeholders)
    {
        $this->expectException(BadParameterException::class);
        $this->urlBuilder->build('/' . $routName, $placeholders);
    }

    /**
     * @dataProvider getBadData
     */
    public function testBuildException3($routName, $placeholders)
    {
        $this->expectException(BadParameterException::class);
        $this->urlBuilder->build($routName, $placeholders, true);
    }

    /**
     * @dataProvider getBadData
     */
    public function testBuildException4($routName, $placeholders)
    {
        $this->expectException(BadParameterException::class);
        $this->urlBuilder->build('/' . $routName, $placeholders, true);
    }

    /**
     * @return array
     */
    public function getBadData()
    {
        return [
            ['user1', []],
            ['user1', ['name' => 'test']],
            ['user1', ['id' => 'test']],

            ['user2', ['name' => 'test']],
            ['user2', ['id' => 'test', 'name' => 'test']],
            ['user2', ['id' => 'test']],

            ['user3', []],
            ['user3', ['id' => 'test']],
            ['user3', ['id' => 1]],
            ['user3', ['id' => 'test', 'name' => 'test']],
            ['user3', ['name' => 'test']],

            ['user4', []],
            ['user4', ['id' => 'test']],
            ['user4', ['id' => 1]],
            ['user4', ['id' => 'ids', 'name' => 'test']],
            ['user4', ['name' => 'test']],

            ['user5', []],
            ['user5', ['id' => 1]],
            ['user5', ['id' => 'test']],
            ['user5', ['id' => 1, 'name' => 'test']],
            ['user5', ['name' => 'test']],

            ['user6', []],
            ['user6', ['id' => 'test']],

            ['user7', ['name' => 'test', 'ids' => 1, 'names' => 'tests']],
            ['user7', ['id' => 1, 'ids' => 1, 'names' => 'tests']],
            ['user7', ['id' => 1, 'name' => 'test', 'names' => 'tests']],
            ['user7', ['name' => 'test', 'ids' => 1, 'names' => 'tests']],
            ['user7', ['ids' => 1, 'names' => 'tests']],
            ['user7', ['names' => 'tests']],
            ['user7', ['id' => 'ids', 'name' => 'test', 'ids' => 1, 'names' => 'tests']],
            ['user7', ['id' => 'ids', 'ids' => 1, 'names' => 'tests']],
            ['user7', ['id' => 'ids', 'name' => 'test', 'names' => 'tests']],
            ['user7', ['id' => 'ids', 'ids' => 'bad', 'names' => 'tests']],

            ['user8', ['id' => 1, 'name' => 'test', 'ids' => 'bad', 'names' => 'tests', 'key' => 'bad-key']],
            ['user8', ['id' => 1, 'name' => 'test', 'ids' => 'bad', 'names' => 'tests']],
            ['user8', ['id' => 1, 'name' => 'test', 'ids' => 'bad', 'key' => 1]],

            ['test1', []],

            ['test4', []],
            ['test4', ['param1' => 'test']],
            ['test4', ['param2' => 'test']],

            ['test5', ['param' => 50000]],
            ['test7', ['opt' => 50000]],

            ['test7', ['opt' => 50000]],

            ['foo-bar', []],

            ['te', []],

            ['param/opt', []],
            ['param/opt', ['opt' => true]],
            ['param/opt', ['opt' => false]],

            ['static1', ['test' => 40]],

            ['static5', ['opt' => 40]],

        ];
    }

    /**
     * @return void
     */
    public function testBuildWithQueryArray()
    {
        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => ['key' => 'value']
            ], true);
        self::assertEquals(self::BASE_URL . '/user/2/grigor?key=value', $result);

        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => ['key' => 'value']
            ]);
        self::assertEquals('/user/2/grigor?key=value', $result);

        $result = $this->urlBuilder->build('user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => ['key' => 'value']
            ]);
        self::assertEquals('user/2/grigor?key=value', $result);
    }

    /**
     * @return void
     */
    public function testBuildWithQueryString()
    {
        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => 'key=value'
            ], true);
        self::assertEquals(self::BASE_URL . '/user/2/grigor?key=value', $result);

        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => 'key=value'
            ]);
        self::assertEquals('/user/2/grigor?key=value', $result);

        $result = $this->urlBuilder->build('user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => 'key=value'
            ]);
        self::assertEquals('user/2/grigor?key=value', $result);
    }

    /**
     * @return void
     */
    public function testBuildWithFragment()
    {
        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '#' => 'value'
            ], true);
        self::assertEquals(self::BASE_URL . '/user/2/grigor#value', $result);

        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '#' => 'value'
            ]);
        self::assertEquals('/user/2/grigor#value', $result);

        $result = $this->urlBuilder->build('user1',
            [
                'id' => 2, 'name' => 'grigor',
                '#' => 'value'
            ]);
        self::assertEquals('user/2/grigor#value', $result);

        $result = $this->urlBuilder->build('user1',
            [
                'id' => 2, 'name' => 'grigor',
            ]);
        self::assertEquals('user/2/grigor', $result);
    }

    /**
     * @return void
     */
    public function testBuildAllParams()
    {
        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => 'key=value',
                '#' => 'value'
            ], true);
        self::assertEquals(self::BASE_URL . '/user/2/grigor?key=value#value', $result);

        $result = $this->urlBuilder->build('/user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => 'key=value',
                '#' => 'value'
            ]);
        self::assertEquals('/user/2/grigor?key=value#value', $result);

        $result = $this->urlBuilder->build('user1',
            [
                'id' => 2, 'name' => 'grigor',
                '?' => 'key=value',
                '#' => 'value'
            ]);
        self::assertEquals('user/2/grigor?key=value#value', $result);

        $result = $this->urlBuilder->build('user1',
            [
                'id' => 2, 'name' => 'grigor',
            ]);
        self::assertEquals('user/2/grigor', $result);
    }

    /**
     * @return void
     */
    public function testBuildWithIdnConverted()
    {
        $urlBuilder = new UrlBuilder(
            'https://россия.рф',
            new ArrayRulesProvider([
                'user1' => '/user/{id:\d+}[/{name}]',
            ])
        );
        $result = $urlBuilder->build('user1', ['id' => 2, 'name' => 'grigor', ':idn' => true], true);
        self::assertEquals('https://xn--h1alffa9f.xn--p1ai/user/2/grigor', $result);
    }

    /**
     * @return void
     */
    public function testBuildWithStrategy()
    {
        $urlBuilder = new UrlBuilder(
            'https://россия.рф',
            new ArrayRulesProvider([
                'user1' => '/user/{id:\d+}[/{name}]',
            ]),
            ['referral' => new ReferralUrlStrategy()]
        );

        $result = $urlBuilder->build('/user1', ['id' => 2, 'name' => 'grigor', ':strategy' => 'referral'], true);
        self::assertEquals('https://россия.рф/user/2/grigor?refid=222', $result);

        $result = $urlBuilder->build('user1', ['id' => 2, 'name' => 'grigor', ':strategy' => 'referral'], true);
        self::assertEquals('https://россия.рф/user/2/grigor?refid=222', $result);

        $result = $urlBuilder->build('/user1', ['id' => 2, 'name' => 'grigor', ':strategy' => 'referral']);
        self::assertEquals('/user/2/grigor?refid=222', $result);

        $result = $urlBuilder->build('user1', ['id' => 2, 'name' => 'grigor', ':strategy' => 'referral']);
        self::assertEquals('user/2/grigor?refid=222', $result);
    }

    /**
     * @return void
     */
    public function testBuildWithStrategyException()
    {
        $this->expectException(BadParameterException::class);
        $urlBuilder = new UrlBuilder(
            'https://россия.рф',
            new ArrayRulesProvider([
                'user1' => '/user/{id:\d+}[/{name}]',
            ]),
            ['referral' => new ReferralUrlStrategy()]
        );

        $urlBuilder->build('/user1', ['id' => 2, 'name' => 'grigor', ':strategy' => 'non-existent'], true);
    }

    /**
     * @return void
     */
    public function testConstructEmptyBaseUrl()
    {
        $this->expectException(InvalidUrlException::class);
        new UrlBuilder(
            '',
            new ArrayRulesProvider([
                'user1' => '[/{name}]',
            ])
        );
    }

    /**
     * @return void
     */
    public function testConstructEmptyBaseUrl2()
    {
        $this->expectException(InvalidUrlException::class);
        new UrlBuilder(
            '     ',
            new ArrayRulesProvider([
                'user1' => '[/{name}]',
            ])
        );
    }

}