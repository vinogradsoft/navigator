# Navigator

> Генератор url. Работает с внутренней навигацией приложения и способен генерировать URL-адреса на внешние ресурсы.
> Внутренняя навигация осуществляется на основе именованных определений маршрутов.
> Именованные маршруты дают возможность удобно создавать URL-адреса, не привязываясь к домену или к определению
> маршрута.

## Общая информация

Библиотека предназначена для приложений использующих в своем коде маршрутизатор
[FastRoute](https://github.com/nikic/FastRoute#fastroute---fast-request-router-for-php). FastRoute использует
определения маршрутов на основе регулярных выражений, а Navigator создает URL-адреса соответствующие этим определениям
маршрутов.

Для постоянства кода определениям маршрутов назначаются имена.

```
   имя           определение 
 маршрута          маршрута
 /‾‾‾‾‾‾‾\      /‾‾‾‾‾‾‾‾‾‾‾\
'user/view' => '/user/{id:\d+}',
```

Это позволяет манипулировать URL-адресами без внесения изменений в существующий код. Например, для создания URL-адреса
именованного определения `'user' => '/user/{id:\d+}'`, можно использовать следующий код:

```php 
/** relative url */
echo $urlBuilder->build('/user', ['id' => 100]); # /user/100

/** absolute url */
echo $urlBuilder->build('/user', ['id' => 100], true); # http://mydomain.ru/user/100
```

Если по какой-либо причине потребуется изменить адрес с `/user/100` на `/employee/100`, вам нужно будет просто изменить
настройку маршрута с `'/user/{id:\d+}'` на `'/employee/{id:\d+}'`. После этого код выше будет создавать относительный
URL-адрес `/employee/100` и абсолютный `http://mydomain.ru/employee/100`.

В объекте класса `Navigator\UrlBuilder` два метода генерации URL: `build` и `buildExternal`. Метод `build` используется
для навигации внутри приложения, а `buildExternal` создает URL для внешних ресурсов и может генерировать только
абсолютные URL-адреса.

## Установка

Предпочтительный способ установки - через [composer](http://getcomposer.org/download/).

Запустите команду

```
php composer require vinogradsoft/navigator "^1.0.0"
```

Требуется PHP 8.0 или новее.

## Быстрый старт

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

## Конструктор

Конструктор имеет шесть параметров, пожалуй самые главные первые два.
Первым параметром идет `$baseUrl` - в данном примере его значение равно `'https://vinograd.soft'` - это базовый URL,
который используется для генерации абсолютных URL-адресов внутри приложения.

Второй параметр `$rulesProvider` принимает экземпляр реализации интерфейса `Navigator\RulesProvider`.
В примере используется реализация `Navigator\ArrayRulesProvider`, которая работает с обычным массивом
зарегистрированных маршрутов. По сути это источник именованных определений маршрутов.

## Параметры метода `build`

### Параметр `$name`

Параметром `$name` вы передаете имя маршрута. На основе значения этого параметра система понимает с каким определением
работает и преобразует его в URL-адрес. В примере используется два случая `'user'` и `'/user'`.
Стоит отметить, что символ “/” в начале передаваемого имени никак не влияет на поиск маршрута, он указывает системе, что
данный символ должен быть включен в начало генерируемого URL-адреса. Система распознает этот символ затем
осуществляет поиск маршрута по имени, исключая из него данный символ. После этого “/” используется системой как
индикатор того, что его необходимо включить в начало формируемого относительного URL-адреса.

### Параметр `$placeholders`

Может быть `массивом настроек заполнителей` или `null`.

#### Массив настроек заполнителей

Из примера, `$urlBuilder` работает с определением маршрута `'/user[/{var_name}]'`, где `var_name` это динамическая
часть,
другими словами переменная.

В метод `build` мы передали такой массив `['var_name' => 'var_value']`, где `'var_name'` переменная из определения
маршрута, а `'var_value'` ее значение, т.е. то чем `'var_name'` в результате будет заменена, в нашем случае результат
для абсолютного URL-адреса такой `https://vinograd.soft/user/var_value`.

#### NULL

NULL используется в случаях генерации статических URL, которым не нужны настройки в директориях пути, такому как это
определение `'user' => '/user'`.

Пример:

```php
$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user/profile',
    ])
);

echo $urlBuilder->build('/user', null, true); # https://vinograd.soft/user/profile
```

### Параметр `$absolute`

Этот булевый параметр указывает системе, какой URL-адрес создавать: абсолютный или относительный. Передача `true`
приведет к созданию абсолютного адреса, в противном случае будет создан относительный адрес. Значение по умолчанию
равно `false`.

## Конфигурация маршрутов

Имена определений не имеют жесткого формата, поэтому какие давать имена зависит от вас.
Вы можете использовать формат `<контроллер>/<действие>` пример: `post/view`.
Или добавлять в начало название модуля `blog/post/view`.

Важно запомнить одно, нельзя чтобы первым символом был `/`. Такое имя не правильное `/blog/post/view` в моменте
генерации URL методом `build`, маршрут с таким именем не будет найден, поскольку система будет его искать без
символа `/`
в начале, затем будет выброшено исключение `Navigator\RoutConfigurationException`.

Пример правильного имени маршрута:

```php
new ArrayRulesProvider([
     'blog/post/view' => '/post/{id:\d+}',
]);
```

Пример **НЕДОПУСТИМОГО** имени маршрута:

```php
new ArrayRulesProvider([
      // Not working warrant /blog/post/view
     '/blog/post/view' => '/post/{id:\d+}',
]);
```

> В именах совсем не обязателен символ `/`, имя может быть и таким `blog.post.view` или каким-то другим не имеющим
> разделителей вовсе.

Как формировать маршруты вы можете прочитать в документации к библиотеке
[FastRoute](https://github.com/nikic/FastRoute#defining-routes).

## Заполнители

### Схема показывает какой заполнитель, за какой участок URL-адреса отвечает

```
  |------------------------------:src--------------------------------------------------|
  |                                                 :path               ?         #    |
  |                                          /‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾‾\ /‾‾‾‾‾‾‾‾‾\ /‾‾‾‾‾‾\|
  |http://grigor:password@vinograd.soft:8080/path/to/resource.json?query=value#fragment|
   \__/   \___/  \_____/  \___________/ \__/                  \__/
 :scheme  :user :password    :host      :port                :suffix
```

Не все заполнители доступны для обоих методов генерации. Таблица ниже показывает доступность каждого заполнителя и тип
данных значения.

| Заполнитель                          | Доступность для метода `build` | Доступность для метода `buildExternal` | Тип                                                                   |
|--------------------------------------|:------------------------------:|:--------------------------------------:|:----------------------------------------------------------------------|
| `название переменной из определения` |             **ДА**             |                  НЕТ                   | `string/int`<br> тип `bool` - для переменных без фигурных скобок`{}`. |
| `:src`                               |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `:scheme`                            |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `:user`                              |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `:password`                          |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `:host`                              |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `:port`                              |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `:path`                              |              НЕТ               |                 **ДА**                 | `string/array`                                                        |
| `:suffix`                            |              НЕТ               |                 **ДА**                 | `string`                                                              |
| `?`                                  |             **ДА**             |                 **ДА**                 | `string/array`                                                        |
| `#`                                  |             **ДА**             |                 **ДА**                 | `string`                                                              |
| `:strategy`                          |             **ДА**             |                 **ДА**                 | `string`                                                              |
| `:idn`                               |             **ДА**             |                 **ДА**                 | `bool`                                                                |      

## Примеры использования заполнителей

Для начала сконфигурируем `$urlBuilder` таким образом:

```php
$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user[/{var_name}]',
        'about' => '/about[.html]',
    ])
);
```

### **Название переменной из определения**

> Не обязательные параметры в определениях не обрамленные в фигурные скобки имеют тип заполнителя `bool`.

#### **Пример 1.**

Страница "about" имеет в определении суффикс - не обязательный параметр `.html` без фигурных скобок. Требуется
сгенерировать URL с этим суффиксом.

```php
echo $urlBuilder->build('about', ['.html' => true], true); # https://vinograd.soft/about.html
```

#### **Пример 2.**

Сгенерируйте абсолютный URL-адрес используя необязательный заполнитель для определения `'user' => '/user[/{var_name}]'`.

```php
echo $urlBuilder->build('/user', ['var_name' => 'my_unique_value'], true); 
# https://vinograd.soft/user/my_unique_value
```

#### **Пример 3.**

Требуется сгенерировать относительный URL-адрес с символом `/` в начале для определения
`'user' => '/user[/{var_name}]'`.

```php
echo $urlBuilder->build('/user', ['var_name' => 'my_unique_value']); # /user/my_unique_value
```

#### **Пример 4.**

Создайте относительный URL-адрес без символа `/` в начале для определения `'user' => '/user[/{var_name}]'`.

```php
echo $urlBuilder->build('user', ['var_name' => 'my_unique_value']); # user/my_unique_value
```

#### **Пример 5.**

Определение `'user' => '/user[/{var_name}]'` имеет не обязательный параметр `var_name`. Нужно сгенерировать абсолютный
URL-адрес без этого параметра.

```php
echo $urlBuilder->build('user', null, true); # https://vinograd.soft/user
```

---

### **:src**

#### **Пример 1.**

В приложении есть переменная в которую записан URL-адрес внешнего ресурса. Нужно внести в URL-адрес несколько
изменений:

+ изменить его схему с `http` на `ftp`
+ добавить имя пользователя `grigor` и пароль `password123`
+ изменить порт на `21`.

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

#### **Пример 2.**

Добавьте путь `blog/post/41` к URL-адресу главной странице другого сайта `http://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'http://another.site',
    ':path' => ['blog', 'post', 41],
]);
# http://another.site/blog/post/41
```

---

### **:scheme**

#### **Пример 1.**

Измените схему URL-адреса `http://another.site` с `http` на `https`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'http://another.site',
    ':scheme' => 'https',
]);
# https://another.site
```

#### **Пример 2.**

Создайте URL-адрес `https://another.site/path/to/resource` из имеющихся в приложении частей со схемой `https`.

```php
echo $urlBuilder->buildExternal([
    ':scheme' => 'https',
    ':host' => 'another.site',
    ':path' => ['path', 'to', 'resource'],
]);
# https://another.site/path/to/resource
```

---

### **:user**

#### **Пример.**

Добавьте имя пользователя `user` для URL-адреса `http://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'http://another.site',
    ':user' => 'user'
]);
# http://user@another.site
```

---

### **:password**

> Этот заполнитель работает только в паре с заполнителем `:user`. Часть `:user` либо должна присутствовать в исходном
> URL-адресе передаваемом в заполнителе `:src`, либо должен передаваться в паре с заполнителем `:password`, если `:user`
> в URL его не окажется, будет выброшено исключение `Navigator\BadParameterException`.

#### **Пример.**

Добавьте имя пользователя `grigor` и пароль `password123` для URL-адреса `ftp://another.site:21`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'ftp://another.site:21',
    ':user' => 'grigor',
    ':password' => 'password123'
]);
# ftp://grigor:password123@another.site:21
```

---

### **:host**

> Заполнитель `:host` используется в паре с заполнителем `:scheme`. В случае когда вы не используете
> заполнитель `:src`, хотите создать URL-адрес из частей, то `:host` и `':scheme'` будут обязательными, при отсутствии
> любого из них будет выброшено исключение `Navigator\BadParameterException`. `:host` единственный заполнитель
> который не переопределяет свой участок в URL-адресе переданным заполнителем `:src`.

#### **Пример.**

Требуется создать URL-адрес `http://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':scheme' => 'http',
    ':host' => 'another.site'
]);
# http://another.site
```

---

### **:port**

#### **Пример.**

Создайте URL-адрес `http://another.site` с портом `5000`.

```php
echo $urlBuilder->buildExternal([
    ':scheme' => 'http',
    ':host' => 'another.site',
    ':port' => '5000'
]);
# http://another.site:5000
```

---

### **:path**

#### **Пример 1.**

Требуется создать URL-адрес `https://another.site/path/to` используя значение заполнителя с типом `array`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    ':path' => ['path', 'to']
]);
# https://another.site/path/to
``` 

#### **Пример 2.**

Сгенерируйте URL-адрес `https://another.site/path/to` используя значение заполнителя с типом `string`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    ':path' => 'path/to'
]);
# https://another.site/path/to
``` 

---

### **:suffix**

> С суффиксами есть один нюанс, если вы передаете URL-адрес с суффиксом средствами заполнителя `:src` вы не сможете
> его переопределить заполнителем `:suffix`, вы можете его только добавить, поскольку суффиксом может быть любая стока,
> он не анализируется и будет являться частью path.

#### **Пример 1.**

Добавьте к URL-адресу `https://another.site/path/to` расширение `.html`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    ':path' => 'path/to',
    ':suffix' => '.html'
]);
# https://another.site/path/to.html
``` 

#### **Пример 2.**

Добавьте суффикс `-city` к URL-адресу `https://another.site/path/to?q=value#news`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site/path/to?q=value#news',
    ':suffix' => '-city'
]);
# https://another.site/path/to-city?q=value#news
``` 

---

### **Заполнитель `?`**

#### **Пример 1.**

Добавьте парамер `s` со значением `Hello world` URL-адресу `https://another.site`.

```php
echo $urlBuilder->buildExternal([':src' => 'https://another.site', '?' => ['s' => 'Hello world']]);
# https://another.site/?s=Hello%20world
```  

#### **Пример 2.**

В системе есть подготовленный параметр `s=Hello world` нужно добавить его к URL-адресу `https://another.site`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site',
    '?' => 's=Hello world'
]);
# https://another.site/?s=Hello%20world
```  

#### **Пример 3.**

Требуется создать URL-адрес для ссылки на результаты поиска другого сайта `https://another.site` со следующими
параметрами:

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

Код:

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

### **Заполнитель `#`**

#### **Пример 1.**

Задача сформировать адрес для ссылки на документацию библиотеки `vinogradsoft/compass`, параграф
`быстрый-старт`.

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://github.com/vinogradsoft/compass',
    '#' => 'быстрый-старт'
]);
# https://github.com/vinogradsoft/compass#быстрый-старт
```

---

### **:strategy**

> Для использования стратегий создания URL-адресов нужно либо реализовать интерфейс `Compass\UrlStrategy`,
> либо наследоваться от класса `Compass\DefaultUrlStrategy`. Более подробно о принципах работы стратегий можно прочитать
> в документации к библиотеке
> [Compass](https://github.com/vinogradsoft/compass#%D1%81%D1%82%D1%80%D0%B0%D1%82%D0%B5%D0%B3%D0%B8%D0%B8-%D0%BE%D0%B1%D0%BD%D0%BE%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F).

#### **Пример 1.**

Задача сделать стратегию для генерации URL-адресов реферальных ссылок с параметром `refid` равному `222`.

Код стратегии:

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

Добавим стратегию в конструктор `$urlBuilder`:

```php
$urlBuilder = new UrlBuilder(
    'https://vinograd.soft',
    new ArrayRulesProvider([
        'user' => '/user[/{var_name}]'
    ]),
    ['referral' => new ReferralUrlStrategy()]
);
```

Генерируем URL-адрес:

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site/path/to/resource',
    ':strategy' => 'referral'
]);
# https://another.site/path/to/resource?refid=222
```

#### **Пример 2.**

Возьмем стратегию из Примера 1 этого параграфа, и сделаем ее стратегией по умолчанию для генерации внешних URL-адресов.

Заменим в экземпляре класса `Compass\Url` стратегию и передадим его в конструктор нашего `$urlBuilder` пятым
параметром (`$externalUrl`):

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

Генерируем URL-адрес:

```php
echo $urlBuilder->buildExternal([
    ':src' => 'https://another.site/path/to/resource'
]);
# https://another.site/path/to/resource?refid=222
```

---

### **:idn**

#### **Пример 1.**

Задача конвертировать URL-адрес `https://россия.рф` в panycode.

```php
echo $urlBuilder->buildExternal([':src' => 'https://россия.рф', ':idn' => true]);
# https://xn--h1alffa9f.xn--p1ai
```

---

## Тестировать

``` php composer tests ```

## Содействие

Пожалуйста, смотрите [ВКЛАД](https://github.com/vinogradsoft/navigator/blob/master/CONTRIBUTING.md) для получения
подробной информации.

## Лицензия

Лицензия MIT (MIT). Пожалуйста, смотрите [файл лицензии](https://github.com/vinogradsoft/navigator/blob/master/LICENSE)
для получения дополнительной информации.