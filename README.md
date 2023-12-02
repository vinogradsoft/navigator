<div align="center">
<img alt="Navigator logo" src="banner.svg">
<br/><br/>
</div>
<br/><br/>
<div align="center">
<strong>Create reusable applications.</strong>
<br>
Open source library.
<br /><br />
</div>

<div align="center">

[![codecov](https://codecov.io/gh/vinogradsoft/navigator/graph/badge.svg?token=73PNNFWLG1)](https://codecov.io/gh/vinogradsoft/navigator)
<img src="https://badgen.net/static/license/MIT/green">

</div>

## What is Navigator?

> 👉 Navigator is a url generator. Works with internal application navigation and is able to generate URLs to external
> resources. Internal navigation is based on named route definitions. Named routes allow you to conveniently create URLs
> without being tied to a domain or route definition.

## General Information

The library is intended for applications that use
the [FastRoute](https://github.com/nikic/FastRoute#fastroute---fast-request-router-for-php) router in their code.
FastRoute uses regular expression route definitions, and Navigator creates URLs that match those route definitions.

For code consistency, route definitions are assigned names.

```
   route            route 
    name        determination
 /‾‾‾‾‾‾‾\      /‾‾‾‾‾‾‾‾‾‾‾\
'user/view' => '/user/{id:\d+}',
```

This allows URLs to be manipulated without making changes to existing code. For example, to create a URL for a named
definition `'user' => '/user/{id:\d+}'`, you could use the following code:

```php 
/** relative url */
echo $urlBuilder->build('/user', ['id' => 100]); # /user/100

/** absolute url */
echo $urlBuilder->build('/user', ['id' => 100], true); # http://mydomain.ru/user/100
```

If for any reason you need to change the address from `/user/100` to `/employee/100`, you will simply need to change the
route setting from `'/user/{id:\d+}'` to `'/employee/{id:\d+}'`. The code above will then create the relative URL
`/employee/100` and the absolute URL `http://mydomain.ru/employee/100`.

The `Navigator\UrlBuilder` class object has two methods for generating URLs: `build` and `buildExternal`. The `build`
method is used for navigation within the application, and `buildExternal` creates URLs for external resources and can
only generate absolute URLs.

## Install

To install with composer:

```
php composer require vinogradsoft/navigator "^1.0"
```

Requires PHP 8.0 or newer.

## 🚀 Quick Start

```php
<?php

use Navigator\ArrayRulesProvider;
use Navigator\UrlBuilder;

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';

$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user[/{var_name}]',
        // many other route definitions in key-value format.
    ])
);

echo $urlBuilder->build('/user', null, true), '<br>'; # https://vinograd.soft/user
echo $urlBuilder->build('/user', ['var_name' => 'var_value'], true), '<br>'; # https://vinograd.soft/user/var_value
echo $urlBuilder->build('user', ['var_name' => 'var_value'], true), '<br>';  # https://vinograd.soft/user/var_value
echo $urlBuilder->build('/user', ['var_name' => 'var_value']), '<br>';  # /user/var_value
echo $urlBuilder->build('user', ['var_name' => 'var_value']), '<br>';  # user/var_value
```

## Constructor

The constructor has six parameters, perhaps the most important are the first two. The first parameter is `$baseUrl` - in
this example its value is `'https://vinograd.soft'` - this is the base URL, which is used to generate absolute URLs
within the application.

The second parameter `$rulesProvider` accepts an implementation instance of the `Navigator\RulesProvider` interface. The
example uses the `Navigator\ArrayRulesProvider` implementation, which operates on a regular array of registered routes.
It is essentially a source of named route definitions.

## Parameters Of The `build(...)` Method

### Parameter `$name`

With the `$name` parameter you pass the name of the route. Based on the value of this parameter, the system understands
which definition it is working with and converts it into a URL. The example uses two cases `'user'` and `'/user'`. It is
worth noting that the “/” character at the beginning of the transmitted name does not affect the route search in any
way; it indicates to the system that this character should be included at the beginning of the generated URL. The system
recognizes this symbol and then searches for the route by name, excluding this symbol from it. The “/” is then used by
the system as an indicator that it should be included at the beginning of the relative URL being generated.

### Parameter `$placeholders`

Can be `array of placeholder settings` or `null`.

#### Array Of Placeholder Settings

From the example, $urlBuilder works with the route definition `'/user[/{var_name}]'`, where `var_name` is the dynamic
part, in other words a variable.

We passed the following array to the build method `['var_name' => 'var_value']`, where `'var_name'` is a variable from
the route definition, and 'var_value' is its value, i.e. then what 'var_name' will be replaced with as a result, in our
case the result for the absolute URL is `https://vinograd.soft/user/var_value`.

#### NULL

`NULL` is used in cases of generating static URLs that do not need settings in the path directories, such as this
definition `'user' => '/user'`.

Example:

```php
$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user/profile',
    ])
);

echo $urlBuilder->build('/user', null, true); # https://vinograd.soft/user/profile
```

### `$absolute` parameter

This Boolean parameter tells the system whether to create an absolute or relative URL. Passing `true` will create an
absolute address, otherwise a relative address will be created. The default value is `false`.

## Route Configuration

Definition names do not have a rigid format, so what names you give is up to you. You can use the
format `<controller>/<action>` example: `post/view`. Or add the name of the module `blog/post/view` to the beginning.

It is important to remember one thing: the first character cannot be `/`. This name is not correct `/blog/post/view`
when the URL is generated by the `build` method, a route with this name will not be found, since the system will look
for it without the `/` character at the beginning, then a `Navigator\RoutConfigurationException` will be thrown.

Example of a correct route name:

```php
new ArrayRulesProvider([
     'blog/post/view' => '/post/{id:\d+}',
]);
```

Example of an **ILLEGAL** route name:

```php
new ArrayRulesProvider([
      // Not working warrant /blog/post/view
     '/blog/post/view' => '/post/{id:\d+}',
]);
```

> The `/` symbol is not required in names; the name can be `blog.post.view` or something else that has no delimiters at
> all.

You can read how to create routes in the documentation for
the [FastRoute](https://github.com/nikic/FastRoute#defining-routes) library.

## Placeholders

### The scheme shows which placeholder is responsible for which part of the URL

```
  |------------------------------:src--------------------------------------------------|
  |                                                 :path               ?         #    |
  |                                          /‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾\ /‾‾‾‾‾‾‾‾‾\ /‾‾‾‾‾‾\|
  |http://grigor:password@vinograd.soft:8080/path/to/resource.json?query=value#fragment|
   \__/   \___/  \_____/  \___________/ \__/                  \__/
 :scheme  :user :password    :host      :port                :suffix
```

Not all placeholders are available for both generation methods. The table below shows the availability of each
placeholder and the data type of the value.

| Placeholder                     | Availability for `build` method | Availability for `buildExternal` method | Type                                                                    |
|---------------------------------|:-------------------------------:|:---------------------------------------:|:------------------------------------------------------------------------|
| `variable name from definition` |             **YES**             |                   NO                    | `string/int`<br> type `bool` - for variables without curly braces `{}`. |
| `:src`                          |               NO                |                 **YES**                 | `string`                                                                |
| `:scheme`                       |               NO                |                 **YES**                 | `string`                                                                |
| `:user`                         |               NO                |                 **YES**                 | `string`                                                                |
| `:password`                     |               NO                |                 **YES**                 | `string`                                                                |
| `:host`                         |               NO                |                 **YES**                 | `string`                                                                |
| `:port`                         |               NO                |                 **YES**                 | `string`                                                                |
| `:path`                         |               NO                |                 **YES**                 | `string/array`                                                          |
| `:suffix`                       |               NO                |                 **YES**                 | `string`                                                                |
| `?`                             |             **YES**             |                 **YES**                 | `string/array`                                                          |
| `#`                             |             **YES**             |                 **YES**                 | `string`                                                                |
| `:strategy`                     |             **YES**             |                 **YES**                 | `string`                                                                |
| `:idn`                          |             **YES**             |                 **YES**                 | `bool`                                                                  |      

## ⚡ Examples Of Using Placeholders

First, let's configure `$urlBuilder` this way:

```php
$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user[/{var_name}]',
        'about' => '/about[.html]',
    ])
);
```

### **👉 Variable Name From Definition**

> Optional parameters in definitions not enclosed in curly braces have a placeholder type of `bool`.

#### **Example 1.**

The "about" page has a suffix in its definition - an optional parameter `.html` without curly braces. You need to
generate a URL with this suffix.

```php
echo $urlBuilder->build('about', ['.html' => true], true); # https://vinograd.soft/about.html
```

#### **Example 2.**

Generate an absolute URL using the optional placeholder to specify `'user' => '/user[/{var_name}]'`.

```php
echo $urlBuilder->build('/user', ['var_name' => 'my_unique_value'], true); 
# https://vinograd.soft/user/my_unique_value
```

#### **Example 3.**

You need to generate a relative URL with a leading `/` to define `'user' => '/user[/{var_name}]'`.

```php
echo $urlBuilder->build('/user', ['var_name' => 'my_unique_value']); # /user/my_unique_value
```

#### **Example 4.**

Create a relative URL without the leading `/` to define `'user' => '/user[/{var_name}]'`.

```php
echo $urlBuilder->build('user', ['var_name' => 'my_unique_value']); # user/my_unique_value
```

#### **Example 5.**

The definition `'user' => '/user[/{var_name}]'` has an optional parameter `var_name`. You need to generate an absolute
URL without this parameter.

```php
echo $urlBuilder->build('user', null, true); # https://vinograd.soft/user
```

---

### **👉 :src**

#### **Example 1.**

The application has a variable that contains the URL of an external resource. There are a few changes you need to make
to the URL:

+ change its scheme from `http` to `ftp`
+ add username `grigor` and password `password123`
+ change port to `21`.

```php
$externalAddress = 'http://another.site:8080/path/to/resource';
echo $urlBuilder->buildExternal([
    ':src' => $externalAddress,
    ':scheme' => 'ftp',
    ':user' => 'grigor',
    ':password' => 'password123',
    ':port' => '21'
]);
# ftp://grigor:password123@another.site:21/path/to/resource
```

#### **Example 2.**

Add the path `blog/post/41` to the other site's homepage URL `http://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'http://another.site',
    ':path' => ['blog', 'post', 41],
]);
# http://another.site/blog/post/41
```

---

### **👉 :scheme**

#### **Example 1.**

Change the URL scheme of `http://another.site` from `http` to `https`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'http://another.site',
    ':scheme' => 'https',
]);
# https://another.site
```

#### **Example 2.**

Create a URL `https://another.site/path/to/resource` from the parts available in the application with the
scheme `https`.

```php
echo $urlBuilder->buildExternal([
    ':scheme' => 'https',
    ':host' => 'another.site',
    ':path' => ['path', 'to', 'resource'],
]);
# https://another.site/path/to/resource
```

---

### **👉 :user**

#### **Example.**

Add the username `user` for the URL `http://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'http://another.site',
    ':user' => 'user'
]);
# http://user@another.site
```

---

### **👉 :password**

> This placeholder only works when paired with the `:user` placeholder. The `:user` part either must be present in the
> original URL passed in the `:src` placeholder, or must be passed in pairs with the `:password` placeholder; if `:user`
> is not present in the URL, a `Navigator\BadParameterException` will be thrown.

#### **Example.**

Add the username `grigor` and password `password123` for the URL `ftp://another.site:21`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'ftp://another.site:21',
    ':user' => 'grigor',
    ':password' => 'password123'
]);
# ftp://grigor:password123@another.site:21
```

---

### **👉 :host**

> The `:host` placeholder is used in conjunction with the `:scheme` placeholder. In the case where you do not use
> the `:src` placeholder and want to create a URL from parts, then `:host` and `':scheme'` will be required; if either
> of them is missing, a `Navigator\BadParameterException` will be thrown. `:host` is the only placeholder that does not
> override its portion of the URL with the passed `:src` placeholder.

#### **Example.**

You need to create the URL `http://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':scheme' => 'http',
    ':host' => 'another.site'
]);
# http://another.site
```

---

### **👉 :port**

#### **Example.**

Create a URL `http://another.site` with port `5000`.

```php
echo $urlBuilder->buildExternal([
    ':scheme' => 'http',
    ':host' => 'another.site',
    ':port' => '5000'
]);
# http://another.site:5000
```

---

### **👉 :path**

#### **Example 1.**

You need to create a URL `https://another.site/path/to` using a placeholder value of type `array`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    ':path' => ['path', 'to']
]);
# https://another.site/path/to
``` 

#### **Example 2.**

Generate the URL `https://another.site/path/to` using a placeholder value of type `string`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    ':path' => 'path/to'
]);
# https://another.site/path/to
``` 

---

### **👉 :suffix**

> There is one caveat with suffixes, if you pass a URL with a suffix using the `:src` placeholder, you cannot override
> it with the `:suffix` placeholder, you can only add it, since the suffix can be any string, it is not parsed and will
> be part of the path.

#### **Example 1.**

Add a `.html` extension to the `https://another.site/path/to` URL.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    ':path' => 'path/to',
    ':suffix' => '.html'
]);
# https://another.site/path/to.html
``` 

#### **Example 2.**

Add the suffix `-city` to the URL `https://another.site/path/to?q=value#news`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site/path/to?q=value#news',
    ':suffix' => '-city'
]);
# https://another.site/path/to-city?q=value#news
``` 

---

### **👉 Placeholder `?`**

#### **Example 1.**

Add the `s` parameter with the value `Hello world` to the `https://another.site` URL.

```php
echo $urlBuilder->buildExternal([':src' => 'https://another.site', '?' => ['s' => 'Hello world']]);
# https://another.site/?s=Hello%20world
```

#### **Example 2.**

The system has a prepared parameter `s=Hello world`, you need to add it to the URL `https://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    '?' => 's=Hello world'
]);
# https://another.site/?s=Hello%20world
```  

#### **Example 3.**

You need to create a URL to link to the search results of another site `https://another.site` with the following
parameters:

```php
    [
        'search' => [
            'blog' => [
                'category' => 'news',
                'author' => 'Grigor'
            ]
        ]
    ]
```

Code:

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    '?' => [
        'search' => [
            'blog' => [
                'category' => 'news',
                'author' => 'Grigor'
            ]
        ]
    ]
]);
# https://another.site/?search%5Bblog%5D%5Bcategory%5D=news&search%5Bblog%5D%5Bauthor%5D=Grigor
```  

---

### **👉 Placeholder `#`**

#### **Example 1.**

The task is to generate an address for a link to the documentation of the `vinogradsoft/compass` library, quick start
paragraph.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://github.com/vinogradsoft/compass',
    '#' => 'quick-start'
]);
# https://github.com/vinogradsoft/compass#quick-start
```

---

### **👉 :strategy**

> To use URL generation strategies, you must either implement the `Compass\UrlStrategy` interface or inherit from the
> `Compass\DefaultUrlStrategy` class. More details about the principles of operation of strategies can be found in the
> documentation for the [Compass](https://github.com/vinogradsoft/compass#upgrade-strategies) library.

#### **Example 1.**

The task is to create a strategy for generating referral link URLs with the `refid` parameter equal to `222`.

Strategy code:

```php
<?php

namespace <your namespace>;

use Compass\DefaultUrlStrategy;
use Compass\Url;

class ReferralUrlStrategy extends DefaultUrlStrategy
{

    /**
     * @inheritDoc
     */
    public function updateQuery(array $items): string
    {
        $items['refid'] = 222;
        return http_build_query($items, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @inheritDoc
     */
    public function forceUnlockMethod(
        bool    &$schemeState,
        int     &$authoritySate,
        int     &$relativeUrlState,
        array   $items,
        array   $pathItems,
        array   $queryItems,
        bool    $updateAbsoluteUrl,
        ?string $suffix = null
    ): void
    {
        $relativeUrlState &= ~Url::QUERY_STATE;
    }

}
```

Let's add a strategy to the `$urlBuilder` constructor:

```php
$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user[/{var_name}]'
    ]),
    ['referral' => new ReferralUrlStrategy()]
);
```

Generating the URL:

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site/path/to/resource',
    ':strategy' => 'referral'
]);
# https://another.site/path/to/resource?refid=222
```

#### **Example 2.**

Let's take the strategy from Example 1 of this paragraph and make it the default strategy for generating external URLs.

Let's replace the strategy in the instance of the `Compass\Url` class and pass it to the constructor of
our `$urlBuilder`
with the fifth parameter (`$externalUrl`):

```php
$externalUrl = Url::createBlank();
$externalUrl->setUpdateStrategy(new ReferralUrlStrategy());

$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user[/{var_name}]'
    ]),
    [],
    null,
    $externalUrl
);
```

Generating the URL:

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site/path/to/resource'
]);
# https://another.site/path/to/resource?refid=222
```

---

### **👉 :idn**

#### **Example 1.**

The task is to convert the URL `https://россия.рф` into panycode.

```php
echo $urlBuilder->buildExternal([':src' => 'https://россия.рф', ':idn' => true]);
# https://xn--h1alffa9f.xn--p1ai
```

---

## Testing

``` php composer tests ```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see License [File](LICENSE) for more information.
