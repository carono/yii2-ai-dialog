<?php

declare(strict_types=1);

namespace Carono\AiDialog;

use yii\web\AssetBundle;

/**
 * Бандл встраиваемого виджета ai-dialog (`widget.js`).
 *
 * Файл `widget.js` тянется как npm-asset `carono-ai-dialog-widget` (зависимость в
 * composer.json) и лежит в `vendor/npm-asset/carono-ai-dialog-widget`. Публикуется
 * штатным AssetManager'ом.
 *
 * Обычно регистрируется автоматически модулем {@see Module}. Подключать вручную
 * нужно только если используете виджет без модуля — тогда data-атрибуты
 * (`data-project`, `data-gateway`, `data-token`) задаются прямо в `$js`.
 */
class AiDialogAsset extends AssetBundle
{
    public $sourcePath = '@npm/carono-ai-dialog-widget';

    public $js = [
        'widget.js',
    ];

    /**
     * Виджет — самодостаточный IIFE-бандл (Preact внутри), внешних зависимостей нет.
     */
    public $depends = [];
}
