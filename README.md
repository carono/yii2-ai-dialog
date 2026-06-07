<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px" alt="Yii">
    </a>
    <h1 align="center">Yii AI Dialog</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/carono/yii2-ai-dialog/v)](https://packagist.org/packages/carono/yii2-ai-dialog)
[![Total Downloads](https://poser.pugx.org/carono/yii2-ai-dialog/downloads)](https://packagist.org/packages/carono/yii2-ai-dialog)
[![Build status](https://github.com/carono/yii2-ai-dialog/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/carono/yii2-ai-dialog/actions/workflows/build.yml?query=branch%3Amaster)
[![Code Coverage](https://codecov.io/gh/carono/yii2-ai-dialog/branch/master/graph/badge.svg)](https://codecov.io/gh/carono/yii2-ai-dialog)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fcarono%2Fyii2-ai-dialog%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/carono/yii2-ai-dialog/master)
[![Static analysis](https://github.com/carono/yii2-ai-dialog/actions/workflows/static.yml/badge.svg?branch=master)](https://github.com/carono/yii2-ai-dialog/actions/workflows/static.yml?query=branch%3Amaster)
[![type-coverage](https://shepherd.dev/github/carono/yii2-ai-dialog/coverage.svg)](https://shepherd.dev/github/carono/yii2-ai-dialog)
[![psalm-level](https://shepherd.dev/github/carono/yii2-ai-dialog/level.svg)](https://shepherd.dev/github/carono/yii2-ai-dialog)

This package embeds the [ai-dialog](https://github.com/carono/ai-dialog) AI chat widget into a
Yii2 application: a 💬 button in the page corner that sends the current page context to a shared
gateway and streams the answer back. It is wired up **like the debug toolbar** — through
`bootstrap` in the dev section of the config, guarded by IP. Nothing is added to the project's
code (layout, assets): the widget bundle `widget.js` is pulled in as an npm-asset Composer
dependency and wired up automatically.

> 🇷🇺 Документация на русском: [`docs/README.ru.md`](docs/README.ru.md).

## Requirements

- PHP 8.1 or higher.

## Installation

The widget bundle is distributed as an npm-asset (`npm-asset/carono-ai-dialog-widget`) and pulled
in by Composer. Because Composer does not inherit repositories from dependencies, the **consuming
project's `composer.json`** must enable asset-packagist once:

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

Then:

```shell
composer require carono/yii2-ai-dialog
```

> If Composer reports `npm-asset/carono-ai-dialog-widget could not be found`, asset-packagist has
> not indexed the package yet. As a temporary workaround add an inline repository to the project
> and re-run `require`:
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

## Setup

All configuration lives in the config, in the dev section. As with `yii2-debug`, wrap the
registration in `YII_ENV_DEV` so the widget never reaches production.

`config/web.php`:

```php
$config = [ /* ... */ ];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'aiDialog';
    $config['modules']['aiDialog'] = [
        'class'   => \Carono\AiDialog\Module::class,
        'project' => 'myapp',          // = the project key in the gateway's projects.json
        'token'   => 'project-secret', // = this project's token on the gateway
        // optional:
        // 'gateway'    => 'wss://wss.carono.site', // gateway address (this is the default)
        // 'allowedIPs' => ['127.0.0.1', '::1'],    // who sees the widget (same as debug)
        // 'enabled'    => true,                    // master switch
    ];

    // usually already present for debug/gii:
    // $config['bootstrap'][] = 'debug';
    // $config['modules']['debug'] = ['class' => \yii\debug\Module::class];
}
```

Three values must match the gateway side (`projects.json`):

| Module option | What it is | Must match |
|---|---|---|
| `project` | project identifier | the object key in `projects.json` |
| `token`   | project secret     | the `token` field of that project |
| `gateway` | WebSocket gateway address | the shared `wss://wss.carono.site` |

Registering a project on the gateway and the overall architecture are described in
`docs/INTEGRATION.md` of the [ai-dialog](https://github.com/carono/ai-dialog) repository.
In short: add a project entry to `projects.json` and restart the gateway.

### How it works

The module implements `BootstrapInterface`. On every request it checks the client IP against
`allowedIPs` (the same logic as `yii\debug\Module`) and, if access is allowed and the response
is a regular HTML page, appends a `<script src=".../widget.js" data-project data-gateway
data-token>` tag at the end of `<body>`. JSON/AJAX responses are left untouched.

> Widget access is restricted by `allowedIPs` only; that is enough for local development.
> Protection of the gateway itself (the project token) lives on its side.

## Documentation

- [Internals](docs/internals.md)

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place
for that. You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## License

The Yii AI Dialog is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)
