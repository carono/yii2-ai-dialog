# Yii AI Dialog

Пакет встраивает в Yii2-приложение dev-виджет диалога с AI ([ai-dialog](https://github.com/carono/ai-dialog)):
кнопка 💬 в углу страницы, которая отправляет контекст текущей страницы на общий шлюз и
стримит ответ. Подключается **как панель отладки** — через `bootstrap` в dev-секции конфига,
с защитой по IP. В код проекта (layout, ассеты) ничего добавлять не нужно: бандл виджета
`widget.js` тянется как npm-asset Composer-зависимость и подключается автоматически.

> 🇬🇧 English documentation: [`../README.md`](../README.md).

## Требования

- PHP 8.1 или выше.

## Установка

Бандл виджета поставляется как npm-asset (`npm-asset/carono-ai-dialog-widget`) и тянется
Composer'ом. Composer **не наследует** `repositories` от зависимостей, поэтому в `composer.json`
**конечного проекта** нужно один раз включить asset-packagist:

```jsonc
{
    "repositories": [
        { "type": "composer", "url": "https://asset-packagist.org" }
    ],
    "config": {
        "allow-plugins": {
            "composer/installers": true,
            "oomphinc/composer-installers-extender": true
        }
    },
    "extra": {
        "installer-types": ["npm-asset"],
        "installer-paths": {
            "vendor/npm-asset/{$name}": ["type:npm-asset"]
        }
    }
}
```

Затем:

```shell
composer require carono/yii2-ai-dialog
```

> Если Composer пишет `npm-asset/carono-ai-dialog-widget could not be found` — asset-packagist
> ещё не проиндексировал пакет (бывает у свежих версий). Временное решение: добавить в
> `repositories` проекта inline-пакет и повторить `require`:
>
> ```json
> {
>     "type": "package",
>     "package": {
>         "name": "npm-asset/carono-ai-dialog-widget",
>         "version": "0.2.0",
>         "type": "npm-asset",
>         "dist": { "type": "tar", "url": "https://registry.npmjs.org/carono-ai-dialog-widget/-/carono-ai-dialog-widget-0.2.0.tgz" }
>     }
> }
> ```

## Подключение

Вся настройка — в конфиге, в dev-секции. По аналогии с `yii2-debug` оберните регистрацию
в `YII_ENV_DEV`, чтобы виджет никогда не попал в прод.

`config/web.php`:

```php
$config = [ /* ... */ ];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'aiDialog';
    $config['modules']['aiDialog'] = [
        'class'   => \Carono\AiDialog\Module::class,
        'project' => 'myapp',           // = ключ проекта в projects.json шлюза
        'token'   => 'секрет-проекта',  // = token этого проекта на шлюзе
        // необязательные:
        // 'gateway'    => 'wss://wss.carono.site', // адрес шлюза (значение по умолчанию)
        // 'allowedIPs' => ['127.0.0.1', '::1'],    // кому показывать (как у debug)
        // 'enabled'    => true,                    // общий выключатель
    ];

    // обычно эта секция уже есть для debug/gii:
    // $config['bootstrap'][] = 'debug';
    // $config['modules']['debug'] = ['class' => \yii\debug\Module::class];
}
```

Три значения должны совпадать со стороной шлюза (`projects.json`):

| Параметр модуля | Что это | Совпадает с |
|---|---|---|
| `project` | идентификатор проекта | ключ объекта в `projects.json` |
| `token`   | секрет проекта        | поле `token` этого проекта |
| `gateway` | адрес WebSocket-шлюза | общий `wss://wss.carono.site` |

Регистрация проекта на шлюзе и устройство системы описаны в `docs/INTEGRATION.md` репозитория
[ai-dialog](https://github.com/carono/ai-dialog). Кратко: добавить запись проекта в
`projects.json` + перезапустить шлюз.

## Как это работает

Модуль реализует `BootstrapInterface`. На каждый запрос он проверяет IP клиента по
`allowedIPs` (та же логика, что в `yii\debug\Module`) и, если доступ разрешён и ответ —
обычная HTML-страница, дописывает в конец `<body>` тег `<script src=".../widget.js"
data-project data-gateway data-token>`. JSON/AJAX-ответы не затрагиваются.

> Доступ к виджету — только по IP из `allowedIPs`; этого достаточно для локальной разработки.
> Защита самого шлюза (токен проекта) — на его стороне.

## Лицензия

Yii AI Dialog — свободное ПО, распространяется по лицензии BSD.
Подробнее см. [`LICENSE`](../LICENSE.md).
