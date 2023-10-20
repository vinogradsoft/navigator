<?php
declare(strict_types=1);

namespace Unit\UrlBuilder;

use Navigator\ArrayRulesProvider;
use Navigator\BadParameterException;
use Navigator\UrlBuilder;
use PHPUnit\Framework\TestCase;
use Test\Cases\Dummy\ReferralUrlStrategy;

class BuildExternalTest extends TestCase
{

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
            'https://vinograd.soft',
            new ArrayRulesProvider([])
        );
    }

    /**
     * @dataProvider getBadData()
     */
    public function testBuildExternalException(array $placeholders)
    {
        $this->expectException(BadParameterException::class);
        $this->urlBuilder->buildExternal($placeholders);
    }

    /**
     * @return array[]
     */
    public function getBadData(): array
    {
        return [
            [
                [':scheme' => 'http', ':user' => 'user', ':password' => '123']
            ],
            [
                [':scheme' => 'http']
            ],
            [
                [':host' => 'another.site', ':user' => 'user', ':password' => '123']
            ],
            [
                [':host' => 'another.site']
            ],
            [
                [':scheme' => 'http', ':host' => 'another.site', ':password' => '123']
            ],
            [
                [':src' => 'https://another.site', ':password' => '123']
            ],
            [
                [':src' => 'another.site']
            ],
            [
                [':src' => 'https://']
            ],
            [
                [':src' => 'test']
            ],
        ];
    }

    /**
     * @dataProvider getData()
     */
    public function testBuildExternal(array $placeholders, string $expected)
    {
        $result = $this->urlBuilder->buildExternal($placeholders);
        self::assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    public function testBuildExternalDouble()
    {
        $result = $this->urlBuilder->buildExternal([':src' => 'https://another.site', ':user' => 'user', ':password' => '123']);
        self::assertEquals('https://user:123@another.site', $result);
        $result = $this->urlBuilder->buildExternal([':src' => 'https://another.site', ':scheme' => 'https']);
        self::assertEquals('https://another.site', $result);

        $result = $this->urlBuilder->buildExternal([':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123', ':path' => ['path', 'to'], '?' => 'key=value', '#' => 'fragment']);
        self::assertEquals('https://user:123@another.site/path/to?key=value#fragment', $result);
        $result = $this->urlBuilder->buildExternal([':scheme' => 'https', ':host' => 'another.site']);
        self::assertEquals('https://another.site', $result);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            [
                [':src' => 'https://another.site', ':user' => 'user', ':password' => '123'],
                'https://user:123@another.site'
            ],
            [
                [':src' => 'https://another.site', ':scheme' => 'ftp'],
                'ftp://another.site'
            ],
            [
                [':scheme' => 'ftp', ':host' => 'another.site', ':user' => 'user', ':password' => '123'],
                'ftp://user:123@another.site'
            ],
            [
                [':src' => 'https://another.site', ':port' => '8080'],
                'https://another.site:8080'
            ],
            [
                [':src' => 'https://another.site', ':path' => 'path/to'],
                'https://another.site/path/to'
            ],
            [
                [':src' => 'https://another.site', ':path' => ['path', 'to']],
                'https://another.site/path/to'
            ],
            [
                [':src' => 'https://another.site', ':path' => ['path', 'to'], ':suffix' => '.html'],
                'https://another.site/path/to.html'
            ],
            [
                [':src' => 'https://another.site', ':path' => ['path', 'to'], ':suffix' => '-city'],
                'https://another.site/path/to-city'
            ],
            [
                [':src' => 'https://another.site', ':suffix' => '-city'],
                'https://another.site'
            ],
            [
                [':src' => 'https://another.site', '?' => 'key=value'],
                'https://another.site/?key=value'
            ],
            [
                [':src' => 'https://another.site', '?' => ['key' => 'value']],
                'https://another.site/?key=value'
            ],
            [
                [':src' => 'https://another.site', '#' => 'fragment'],
                'https://another.site/#fragment'
            ],
            [
                [':src' => 'https://another.site', ':user' => 'user', ':password' => '123', ':path' => ['path', 'to'], '?' => ['key' => 'value'], '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':src' => 'https://another.site', ':user' => 'user', ':password' => '123', ':path' => ['path', 'to'], '?' => 'key=value', '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':src' => 'https://another.site', ':user' => 'user', ':password' => '123', ':path' => 'path/to', '?' => 'key=value', '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':src' => 'https://another.site', ':user' => 'user', ':password' => '123', ':path' => 'path/to', '?' => ['key' => 'value'], '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123', ':path' => ['path', 'to'], '?' => ['key' => 'value'], '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123', ':path' => ['path', 'to'], '?' => 'key=value', '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123', ':path' => 'path/to', '?' => 'key=value', '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123', ':path' => 'path/to', '?' => ['key' => 'value'], '#' => 'fragment'],
                'https://user:123@another.site/path/to?key=value#fragment'
            ],

            [
                [':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123'],
                'https://user:123@another.site'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':user' => 'user', ':password' => '123'],
                'https://user:123@another.site'
            ],
            [
                ['https', ':host' => 'another.site', ':scheme' => 'ftp'],
                'ftp://another.site'
            ],
            [
                [':scheme' => 'ftp', ':host' => 'another.site', ':user' => 'user', ':password' => '123'],
                'ftp://user:123@another.site'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':port' => '8080'],
                'https://another.site:8080'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':path' => 'path/to'],
                'https://another.site/path/to'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':path' => ['path', 'to']],
                'https://another.site/path/to'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':path' => ['path', 'to'], ':suffix' => '.html'],
                'https://another.site/path/to.html'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':path' => ['path', 'to'], ':suffix' => '-city'],
                'https://another.site/path/to-city'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', ':suffix' => '-city'],
                'https://another.site'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', '?' => 'key=value'],
                'https://another.site/?key=value'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', '?' => ['key' => 'value']],
                'https://another.site/?key=value'
            ],
            [
                [':scheme' => 'https', ':host' => 'another.site', '#' => 'fragment'],
                'https://another.site/#fragment'
            ],
        ];
    }

    /**
     * @return void
     */
    public function testBuildExternalWithStrategy()
    {
        $urlBuilder = new UrlBuilder(
            'https://another.site',
            new ArrayRulesProvider([]),
            ['referral' => new ReferralUrlStrategy()]
        );
        $result = $urlBuilder->buildExternal([':src' => 'https://another.site', ':strategy' => 'referral']);
        self::assertEquals('https://another.site/?refid=222', $result);
        $result = $urlBuilder->buildExternal([':src' => 'https://another.site/path/to/resource', ':strategy' => 'referral']);
        self::assertEquals('https://another.site/path/to/resource?refid=222', $result);
    }

}