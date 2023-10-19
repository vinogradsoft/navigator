<?php

namespace Unit;

use Navigator\ArrayRulesProvider;
use Navigator\RoutConfigurationException;
use PHPUnit\Framework\TestCase;

class ArrayRulesProviderTest extends TestCase
{

    private $rulesProvider;
    private $var1 = 'user/profile';
    private $var2 = 'user.profile';
    private $var3 = 'user_profile';
    private $var4 = 'user:profile';
    private $var5 = 'test';

    private $value1 = '/user/profile';
    private $value2 = '/user/profile2';
    private $value3 = '/user/profile3';
    private $value4 = '/test';
    private $value5 = '/test/test';

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->rulesProvider = new ArrayRulesProvider(
            [
                $this->var1 => $this->value1,
                $this->var2 => $this->value2,
                $this->var3 => $this->value3,
                $this->var4 => $this->value4,
                $this->var5 => $this->value5,
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetPattern()
    {
        $result1 = $this->rulesProvider->getPattern($this->var1);
        $result2 = $this->rulesProvider->getPattern($this->var2);
        $result3 = $this->rulesProvider->getPattern($this->var3);
        $result4 = $this->rulesProvider->getPattern($this->var4);
        $result5 = $this->rulesProvider->getPattern($this->var5);
        self::assertEquals($this->value1, $result1);
        self::assertEquals($this->value2, $result2);
        self::assertEquals($this->value3, $result3);
        self::assertEquals($this->value4, $result4);
        self::assertEquals($this->value5, $result5);

        $result1 = $this->rulesProvider->getPattern('/' . $this->var1);
        $result2 = $this->rulesProvider->getPattern('/' . $this->var2);
        $result3 = $this->rulesProvider->getPattern('/' . $this->var3);
        $result4 = $this->rulesProvider->getPattern('/' . $this->var4);
        $result5 = $this->rulesProvider->getPattern('/' . $this->var5);
        self::assertEquals($this->value1, $result1);
        self::assertEquals($this->value2, $result2);
        self::assertEquals($this->value3, $result3);
        self::assertEquals($this->value4, $result4);
        self::assertEquals($this->value5, $result5);

        $result1 = $this->rulesProvider->getPattern('//' . $this->var1);
        $result2 = $this->rulesProvider->getPattern('//' . $this->var2);
        $result3 = $this->rulesProvider->getPattern('//' . $this->var3);
        $result4 = $this->rulesProvider->getPattern('//' . $this->var4);
        $result5 = $this->rulesProvider->getPattern('//' . $this->var5);
        self::assertEquals($this->value1, $result1);
        self::assertEquals($this->value2, $result2);
        self::assertEquals($this->value3, $result3);
        self::assertEquals($this->value4, $result4);
        self::assertEquals($this->value5, $result5);
    }

    /**
     * @return void
     */
    public function testGetPatternException()
    {
        $this->expectException(RoutConfigurationException::class);
        $this->rulesProvider->getPattern('no');
    }

    /**
     * @dataProvider getData
     */
    public function testConstructAndGetPatternException($name, $badName)
    {
        $this->expectException(RoutConfigurationException::class);
        $this->rulesProvider = new ArrayRulesProvider(
            [
                $badName => $this->value1
            ]
        );
        $this->rulesProvider->getPattern($name);
    }

    /**
     * @return array[]
     */
    public function getData()
    {
        return [
            ['/user', '/user'],
            [' /user', '/user'],
            [' /user', ' /user'],
            ['user', '/user'],
            ['user', ' /user'],
            [' user', ' /user'],
            ['user', '/'],
            ['/', '/'],
            [' /', '/'],
            ['/ ', '/'],
            ['/', ' /'],
            ['/', '/ '],
            [' /', '/ '],
            [' / ', ' / '],
            ['/ ', '/ '],
            [' /', ' /'],
            ['/', ''],
            ['/ ', ' '],
            [' / ', ' '],
            [' /', ' '],
            ['', '/'],
            ['', ''],
            [' ', ' '],
            [' ', ''],
            ['         ', ''],
            ["/ \t\n\r\0\x0B", ''],
            ['', ' ']
        ];
    }

}