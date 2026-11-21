# w4os views

The view models of the w4os bridges. The engine of an OpenSimulator grid describes what a site shows of its lists, items and forms in its contract (`x-list`, `x-form`, `x-access` of its OpenAPI document) and answers with the rows and items themselves. This library reads both and gives each bridge what to show: the columns of a list, its filters and their state, its rows with their cells, indicators and actions, the fields of an item a visitor may change, the cards of a dashboard, the dates in the language of the page. Each bridge renders them its own way: Twig and Tabler in the Grav plugin, `WP_List_Table` and the Settings API in the WordPress plugin, Filament in the manager.

It has no framework of its own and works from the contract alone, so a bridge that talks to a remote engine shows the same thing as one that bundles it. When the engine's library is there, its own rules answer (who may do an operation, the message once an action is done); without it, the same rules are read from the contract.

It gives keys of the engine's catalog, never translated text, and never markup: the bridge translates with its own language files and draws with its own templates. Dates are the exception: they come in the language of the page, relative while recent.

## Install

```bash
composer require w4os/w4os-views
```

PHP 8.3 with `intl`.

## Use

```php
use W4OS\Views\Contract;
use W4OS\Views\Access;

$contract = new Contract($document);        // the engine's OpenAPI document
$list = $contract->listOf('avatars');       // its columns, sorts, search, filters and actions
Access::allows($list['read'], $level);      // whether a manager of this level reads it
```

## License

AGPL-3.0-or-later, see [LICENSE](LICENSE).
