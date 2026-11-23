# PHP quality checks

Set `PHP_QA_ROOT` to the shared PHP QA runtime, either in the environment or in the ignored `.agents/qa/local.properties` file (`PHP_QA_ROOT=path`). Run from the repository root:

```powershell
python .agents/qa/run.py
```

The checks lint PHP and run PHPCS for the package, module and plugin. PHP CS Fixer runs in dry-run mode on the package installer and module. PHPDoc/@since validation also covers the plugin's information field. Logs are private under `.pf/runtime/joomla-code-style-fixes/`.

Set `FRONTEND_QA_ROOT` to the shared frontend QA runtime and run `python .agents/qa/run.py --js` to add Node.js tests and ESLint for the module's media files. The tests use Node's built-in test runner and require no project npm dependencies.

ESLint also checks the plugin's `media/js/`. Its selector parameters and add-to-cart script are owned by the plugin. Run `php .agents/qa/tests/plugin-assets.php <joomla-core-root>` to check media installation, WebAsset registration, full CSS selector options and translated configuration against a local Joomla core.

The browser regressions in `tests/plugin-selectors.browser.js` and `tests/cart-sync.browser.js` use the isolated fixture recorded in `.pf/runtime/plugin-ajax-settings/`. `tests/cart-live-catalog.browser.js` and `tests/cart-live-variants.browser.js` exercise real shop requests with the temporary template/module setup documented in that session's verification report; run them only on the prepared test site.

For a change limited to formatting and documentation, add `--baseline=path/to/original/source` to compare PHP ASTs. Comments and insignificant layout whitespace are excluded; runtime string literals and program statements are compared. The later additive price-format metadata is an intentional PHP difference and requires the separate scoped compatibility check recorded with that patch.
