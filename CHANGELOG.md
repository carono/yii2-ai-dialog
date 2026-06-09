# yii2-ai-dialog Change Log

## 1.1.2

- Code comments and log warnings translated to English (no behavior change).

## 1.1.1

- Removed the hardcoded personal gateway default from `Module`. `gateway` now defaults to empty;
  `data-gateway` is emitted only when set (otherwise the widget falls back to its local default).
- Docs use neutral placeholders for the gateway address.

## 1.1.0

- Widget dependency switched to `@stable` so projects always get the latest stable widget
  (including the self-onboarding diagnostics, widget 0.4.0+).
- Docs: architecture/gateway/troubleshooting sections (EN + RU).

## 1.0.0

- Initial release: IP-guarded bootstrap module that embeds the ai-dialog widget.
