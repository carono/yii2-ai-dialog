<?php

declare(strict_types=1);

namespace Carono\AiDialog;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\Module as BaseModule;
use yii\web\Application;
use yii\web\View;

/**
 * Модуль подключения dev-виджета ai-dialog к Yii2-приложению.
 *
 * Работает по тому же принципу, что и панель отладки (`yii\debug\Module`):
 * регистрируется в `bootstrap`, на каждый HTTP-запрос проверяет доступ по IP и,
 * если он разрешён, дописывает в конец `<body>` тег `<script>` виджета.
 * Вся настройка — в конфиге, в dev-секции приложения. В код проекта (layout,
 * assets) ничего добавлять не нужно.
 *
 * Пример (`config/web.php`):
 *
 * ```php
 * if (YII_ENV_DEV) {
 *     $config['bootstrap'][] = 'aiDialog';
 *     $config['modules']['aiDialog'] = [
 *         'class'   => \Carono\AiDialog\Module::class,
 *         'project' => 'myapp',                 // = ключ в projects.json шлюза
 *         'token'   => 'секрет-проекта',        // = token этого проекта
 *         // 'gateway'    => 'wss://wss.carono.site', // адрес шлюза (по умолчанию)
 *         // 'allowedIPs' => ['127.0.0.1', '::1'],    // как у debug-панели
 *     ];
 * }
 * ```
 */
class Module extends BaseModule implements BootstrapInterface
{
    /**
     * @var string[] список IP-адресов, которым разрешён виджет. Поддерживает
     * wildcard в конце (`192.168.*`) и `*` — «всем». По умолчанию — только
     * локальные адреса, как у панели отладки.
     */
    public array $allowedIPs = ['127.0.0.1', '::1'];

    /**
     * @var string|null идентификатор проекта (`data-project`). Должен совпадать
     * с ключом в `projects.json` шлюза. Без него виджет не подключается.
     */
    public ?string $project = null;

    /**
     * @var string адрес WebSocket-шлюза (`data-gateway`).
     */
    public string $gateway = 'wss://wss.carono.site';

    /**
     * @var string|null секрет проекта (`data-token`). Должен совпадать с `token`
     * проекта в `projects.json`. Если на шлюзе токен задан — обязателен.
     */
    public ?string $token = null;

    /**
     * @var bool общий выключатель. Удобно гасить виджет, не убирая конфиг.
     */
    public bool $enabled = true;

    public function bootstrap($app): void
    {
        if (!$this->enabled || !$app instanceof Application) {
            return;
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app): void {
            if ($this->project === null || $this->project === '') {
                Yii::warning('AI Dialog: не задан "project", виджет отключён.', __METHOD__);
                return;
            }
            if (!$this->checkAccess()) {
                return;
            }
            $app->getView()->on(View::EVENT_END_BODY, [$this, 'registerWidget']);
        });
    }

    /**
     * Регистрирует ассет виджета в конце `<body>`. Вызывается по событию
     * View::EVENT_END_BODY, поэтому срабатывает только для обычных страниц с
     * layout'ом, но не для JSON/AJAX-ответов.
     */
    public function registerWidget(Event $event): void
    {
        if (Yii::$app->getRequest()->getIsAjax()) {
            return;
        }

        /** @var View $view */
        $view = $event->sender;

        // Первый элемент — имя файла, остальные ключи становятся HTML-атрибутами
        // тега <script>. Виджет читает их через document.currentScript.dataset.
        $js = [
            'widget.js',
            'data-project' => $this->project,
            'data-gateway' => $this->gateway,
        ];
        if ($this->token !== null && $this->token !== '') {
            $js['data-token'] = $this->token;
        }

        // Переопределяем только список js (с data-атрибутами); sourcePath
        // (@npm/carono-ai-dialog-widget) берётся из самого AiDialogAsset.
        Yii::$app->getAssetManager()->bundles[AiDialogAsset::class] = [
            'js' => [$js],
        ];

        AiDialogAsset::register($view);
    }

    /**
     * Проверка доступа по IP — логика идентична `yii\debug\Module::checkAccess()`.
     */
    protected function checkAccess(): bool
    {
        $ip = Yii::$app->getRequest()->getUserIP();
        foreach ($this->allowedIPs as $filter) {
            if (
                $filter === '*'
                || $filter === $ip
                || (($pos = strpos($filter, '*')) !== false && strncmp($ip, $filter, $pos) === 0)
            ) {
                return true;
            }
        }
        Yii::warning('Доступ к виджету AI Dialog запрещён для IP ' . $ip . '.', __METHOD__);
        return false;
    }
}
