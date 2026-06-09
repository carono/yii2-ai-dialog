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
 * Module that attaches the ai-dialog dev widget to a Yii2 application.
 *
 * Works the same way as the debug panel (`yii\debug\Module`):
 * it is registered in `bootstrap`, checks IP access on every HTTP request and,
 * if access is allowed, appends the widget's `<script>` tag to the end of `<body>`.
 * All configuration lives in the config, in the app's dev section. Nothing needs to be
 * added to the project code (layout, assets).
 *
 * Example (`config/web.php`):
 *
 * ```php
 * if (YII_ENV_DEV) {
 *     $config['bootstrap'][] = 'aiDialog';
 *     $config['modules']['aiDialog'] = [
 *         'class'   => \Carono\AiDialog\Module::class,
 *         'project' => 'myapp',                 // = key in the gateway's projects.json
 *         'token'   => 'project-secret',        // = token of this project
 *         'gateway' => 'wss://your-gateway.example', // address of your gateway
 *         // 'allowedIPs' => ['127.0.0.1', '::1'],    // same as the debug panel
 *     ];
 * }
 * ```
 */
class Module extends BaseModule implements BootstrapInterface
{
    /**
     * @var string[] list of IP addresses allowed to use the widget. Supports
     * a trailing wildcard (`192.168.*`) and `*` — "everyone". By default — only
     * local addresses, like the debug panel.
     */
    public array $allowedIPs = ['127.0.0.1', '::1'];

    /**
     * @var string|null project identifier (`data-project`). Must match
     * the key in the gateway's `projects.json`. Without it the widget is not attached.
     */
    public ?string $project = null;

    /**
     * @var string WebSocket gateway address (`data-gateway`), e.g. `wss://your-gateway.example`.
     * Empty — the attribute is not set, and the widget uses its own default
     * (`ws://<page-host>:8787`), which is convenient for a local gateway.
     */
    public string $gateway = '';

    /**
     * @var string|null project secret (`data-token`). Must match the project's `token`
     * in `projects.json`. Required if a token is set on the gateway.
     */
    public ?string $token = null;

    /**
     * @var bool global switch. Handy to turn the widget off without removing the config.
     */
    public bool $enabled = true;

    public function bootstrap($app): void
    {
        if (!$this->enabled || !$app instanceof Application) {
            return;
        }

        $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app): void {
            if ($this->project === null || $this->project === '') {
                Yii::warning('AI Dialog: "project" is not set, the widget is disabled.', __METHOD__);
                return;
            }
            if (!$this->checkAccess()) {
                return;
            }
            $app->getView()->on(View::EVENT_END_BODY, [$this, 'registerWidget']);
        });
    }

    /**
     * Registers the widget asset at the end of `<body>`. Called on the
     * View::EVENT_END_BODY event, so it fires only for regular pages with a
     * layout, but not for JSON/AJAX responses.
     */
    public function registerWidget(Event $event): void
    {
        if (Yii::$app->getRequest()->getIsAjax()) {
            return;
        }

        /** @var View $view */
        $view = $event->sender;

        // The first element is the file name, the remaining keys become HTML attributes
        // of the <script> tag. The widget reads them via document.currentScript.dataset.
        $js = [
            'widget.js',
            'data-project' => $this->project,
        ];
        if ($this->gateway !== '') {
            $js['data-gateway'] = $this->gateway;
        }
        if ($this->token !== null && $this->token !== '') {
            $js['data-token'] = $this->token;
        }

        // Override only the js list (with data attributes); sourcePath
        // (@npm/carono-ai-dialog-widget) is taken from AiDialogAsset itself.
        Yii::$app->getAssetManager()->bundles[AiDialogAsset::class] = [
            'js' => [$js],
        ];

        AiDialogAsset::register($view);
    }

    /**
     * IP access check — the logic is identical to `yii\debug\Module::checkAccess()`.
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
        Yii::warning('Access to the AI Dialog widget denied for IP ' . $ip . '.', __METHOD__);
        return false;
    }
}
