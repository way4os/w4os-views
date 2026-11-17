# Developers

## What goes here

What two bridges would otherwise both write: reading the contract of the engine, deciding what a row, an item, a form or a card shows and which actions apply, the dates. What belongs to a bridge stays there: its routes and pages, its forms, its sessions, its language files, its templates and styles, the icon of each semantic icon name. What belongs to the engine goes to the engine: a rule a bridge would have to guess is a field of the contract.

The library takes plain arrays (the contract document, the engine's answers, the viewer: level, own UUID, roles, language) and gives plain values: catalog keys and their arguments, never translated text, never markup.

## Rules of the engine, in one place

When the engine's library is installed (`magicoli/opensim-engine`, a bridge that bundles it), its classes answer: `OpenSim\Api\Access` for who may do an operation, `OpenSim\I18n\Catalog::doneKey()` for the message once an action is done. Without it (a remote engine), the same rule is read from the contract, written once here. The tests check every operation and action of the contract against both, so they never part.

## Tests

[Pest](https://pestphp.com) 4: `vendor/bin/pest`, or `composer test`.

- Tests come first: a test that fails, then the code.
- The engine is a development dependency (a path repository, `../opensim-engine`), for its contract (`OpenSim\Api\Contract::document()`, the `contract()` helper of `tests/Pest.php`) and its rules.
- Use `describe()` and `test()`, never `it()`; titles short, the important words first.

## Build and release

[build-tools](https://github.com/magicoli/build-tools): `dev/build.sh` makes the zip of the last commit in `dist/`, `dev/release.sh` releases. Without `packaging/*.yaml`, the library is released as a zip alone; `.distignore` says what it leaves out.

## Working with a bridge

A bridge requires it as a path repository during development, the way it requires build-tools:

```json
{ "name": "magicoli/w4os-views", "url": "../w4os-views/", "type": "path", "options": { "symlink": true } }
```

## Commits

`type (scope): subject` in the imperative, English; `(untested)` before one not verified yet. Work on `dev`.
