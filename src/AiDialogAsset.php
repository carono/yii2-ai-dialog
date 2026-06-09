<?php

declare(strict_types=1);

namespace Carono\AiDialog;

use yii\web\AssetBundle;

/**
 * Asset bundle for the embeddable ai-dialog widget (`widget.js`).
 *
 * The `widget.js` file is pulled in as the npm-asset `carono-ai-dialog-widget` (a dependency in
 * composer.json) and lives in `vendor/npm-asset/carono-ai-dialog-widget`. It is published
 * by the standard AssetManager.
 *
 * Usually registered automatically by the {@see Module}. You only need to register it manually
 * if you use the widget without the module — in that case the data attributes
 * (`data-project`, `data-gateway`, `data-token`) are set directly in `$js`.
 */
class AiDialogAsset extends AssetBundle
{
    public $sourcePath = '@npm/carono-ai-dialog-widget';

    public $js = [
        'widget.js',
    ];

    /**
     * The widget is a self-contained IIFE bundle (Preact inside), no external dependencies.
     */
    public $depends = [];
}
