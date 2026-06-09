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

## Architecture — what you are installing

This Composer package is **only the client**. It injects a small `widget.js` that, by itself,
does nothing useful — it needs a separate shared service, the **gateway**:

```
Widget on the page ──wss──► Gateway (one shared service) ──► AI engine (with access to your repo)
```

The gateway accepts widget connections and, by the `project` + `token` pair, routes the request
to the configured AI engine (e.g. Claude Code with read access to your repository). One gateway
serves all projects. So a working setup always has **two sides**:

1. **Client (this package)** — `bootstrap` + module config in your Yii2 app (below).
2. **Gateway** — your project registered in its `projects.json`, gateway running and reachable
   over `wss`. See [The gateway](#the-gateway).

You don't have to memorize this: **the widget guides you**. Once it appears, open it — on any
connection problem it shows exactly what is wrong (gateway down, project not registered, wrong
token) and copy-paste-ready steps to fix it.

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

### How it works

The module implements `BootstrapInterface`. On every request it checks the client IP against
`allowedIPs` (the same logic as `yii\debug\Module`) and, if access is allowed and the response
is a regular HTML page, appends a `<script src=".../widget.js" data-project data-gateway
data-token>` tag at the end of `<body>`. JSON/AJAX responses are left untouched.

> Widget access is restricted by `allowedIPs` only; that is enough for local development.
> Protection of the gateway itself (the project token) lives on its side.

## The gateway

The gateway is the shared service the widget talks to (see [Architecture](#architecture--what-you-are-installing)).
You do **not** set up a server, nginx, or certificates per project — that part is shared and
already running. To connect a new project you only:

1. **Register the project** in the gateway's `projects.json`:

   ```json
   "myapp": {
     "endpoint": "claude-code",
     "repoPath": "/absolute/path/to/repo",
     "token": "project-secret",
     "allowWrite": false
   }
   ```
   - `endpoint` — `claude-code` (agent with read access to the repo), `dashboard` (answers from
     page content only), or `opencode` (not implemented yet).
   - `repoPath` — absolute path to the repo root (required for `claude-code`).
   - `token` — must equal the module's `token`.
   - `allowWrite` — keep `false`: the agent can only read code, not change files.

2. **Restart the gateway** (it reads the registry on start) and confirm your project is listed:

   ```bash
   curl -s http://127.0.0.1:8787/health   # {"ok":true,"projects":["myapp", ...]}
   ```

If you don't run the gateway yourself, ask its owner to add your project and give you the token.

The full, step-by-step runbook — also written so an **AI agent** can deploy the gateway and wire
a project end-to-end — is [`docs/INTEGRATION.md`](https://github.com/carono/ai-dialog/blob/master/docs/INTEGRATION.md)
in the [ai-dialog](https://github.com/carono/ai-dialog) repository.

## Troubleshooting

Open the widget first — its panel names the exact problem and shows copy-paste steps. Reference:

| Symptom | Cause | Fix |
|---|---|---|
| No 💬 button at all | `aiDialog` not in `$config['bootstrap']` | Add `$config['bootstrap'][] = 'aiDialog';` |
| No 💬 button | `project` not set | Set the `project` option (the module skips injection without it; see the warning in the log) |
| No 💬 button | request IP not in `allowedIPs` | Add your IP to `allowedIPs` (default is localhost only) |
| No 💬 button | page is JSON/AJAX or has no layout | The widget injects only into regular HTML pages (`endBody`) |
| Button shows, banner **"Шлюз недоступен"** | gateway not running / not proxied | Start the gateway; check `curl …/health`; ensure `wss` proxy + DNS |
| Banner **"Проект не заведён"** | `project` missing in `projects.json` | Add the project entry and restart the gateway |
| Banner **"Неверный токен"** | `token` ≠ gateway's `token` | Align the module `token` with `projects.json`, then reload the page |

After changing the config, clear published assets and hard-reload (Ctrl+F5):

```bash
rm -rf web/assets/*
```

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
