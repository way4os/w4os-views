<?php

use W4OS\Views\Contract;

describe('a form of settings of a grid', function () {
    test('is its x-form: its title, its button, its help, its preview, its fields with their values and conditions, who reads and who changes it', function () {
        $form = (new Contract(contract()))->settingsOf('models');
        $fields = array_column($form['fields'], null, 'name');

        expect($form)->toMatchArray(['group' => 'models', 'title' => 'settings_models', 'scoped' => true])
            ->and(array_keys($fields))->toBe(['match', 'pattern', 'list'])
            ->and($fields['match']['values'][0])->toBe(['value' => 'first', 'label' => 'models_match_first'])
            ->and($fields['pattern']['condition'])->toBe(['match' => ['first', 'any', 'last']])
            ->and($fields['match']['widget'])->toBe('radio')
            ->and($form['submit'])->toBe('action_save_changes')
            ->and($form['menu'])->toBe('settings_models')
            ->and($form['saved'])->toBe('settings_saved')
            ->and((new Contract(contract()))->settingsOf('economy')['menu'])->toBe('settings_menu_economy')
            ->and($form['description'])->toContain('models_help_choice')
            ->and($form['preview'])->toMatchArray(['label' => 'models_preview', 'empty' => 'models_none', 'path' => '/grids/{grid}/avatar-models', 'filters' => ['match', 'pattern', 'list']])
            ->and($fields['list'])->toMatchArray(['type' => 'avatar', 'multiple' => true])
            ->and($form['read']['operation'])->toBe('getModelsSettings')
            ->and($form['change']['operation'])->toBe('updateModelsSettings')
            ->and($fields['match']['access']['level'])->toBe($form['change']['level']);
    });

    test('has its groups in their order, each field its group; none for a form of one group', function () {
        $document = ['paths' => ['/grids/{grid}/settings/grid' => ['get' => ['x-form' => ['title' => 'settings_grid',
            'groups' => [['name' => 'grid', 'label' => 'group_grid_info'], ['name' => 'robust', 'label' => 'group_robust'], ['name' => 'broken']],
            'fields' => [['name' => 'gridName', 'type' => 'string', 'group' => 'grid'], ['name' => 'consolePort', 'type' => 'integer', 'group' => 'robust']]]]]]];
        $form = (new Contract($document))->settingsOf('grid');

        expect($form['groups'])->toBe([['name' => 'grid', 'label' => 'group_grid_info'], ['name' => 'robust', 'label' => 'group_robust']])
            ->and(array_column($form['fields'], 'group', 'name'))->toBe(['gridName' => 'grid', 'consolePort' => 'robust'])
            ->and((new Contract(contract()))->settingsOf('models')['groups'])->toBe([]);
    });

    test('is none for a group the engine does not describe', function () {
        expect((new Contract(contract()))->settingsOf('nothing'))->toBeNull();
    });
});

/** The settings of the offline messages, their module set by the options of the grid (shown, never changed here). */
function readonly_module(array $form): array
{
    $form['fields'] = array_map(fn (array $field) => $field['name'] === 'offlineModule' ? ['readOnly' => true] + $field : $field, $form['fields']);

    return $form;
}

describe('a form of the settings of a service', function () {
    test('shows what the options of the grid set, never changes it', function () {
        $form = readonly_module((new Contract(contract()))->settingsOf('offline'));
        $view = W4OS\Views\FormView::of($form, ['provide' => true, 'offlineModule' => 'OfflineMessageModule', 'path' => '/helpers/offline', 'mailSender' => 'a@example.org'], null, true, 250);

        expect(array_column($view->shown, 'name'))->toBe(['offlineModule'])
            ->and(array_column($view->fields, 'name'))->toContain('provide', 'mailSender')->not->toContain('offlineModule')
            ->and($view->changes(['provide' => '1', 'path' => '/helpers/offline', 'offlineModule' => 'Offline Message Module V2', 'mailSender' => 'b@example.org', 'offlineDatabase' => ['useDefault' => '1']]))->toBe(['mailSender' => 'b@example.org']);
    });

    test('a database as its parts, its password never shown, kept unless one is typed', function () {
        $form = (new Contract(contract()))->settingsOf('search');
        $item = ['provide' => true, 'searchDatabase' => ['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os', 'password' => '********', 'options' => []]];
        $view = W4OS\Views\FormView::of($form, $item, null, true, 250);
        $parts = ['host' => 'db.example.org', 'port' => '3306', 'database' => 'search', 'user' => 'os', 'password' => ''];
        $sent = ['provide' => '1'];

        expect(array_column(array_column($form['fields'], null, 'name')['searchDatabase']['fields'], 'name'))->toBe(['host', 'port', 'database', 'user', 'password'])
            ->and($view->values['searchDatabase'])->toBe(['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os', 'useDefault' => false])
            ->and($view->changes($sent + ['searchDatabase' => $parts]))->toBe([])
            ->and($view->changes($sent + ['searchDatabase' => ['host' => 'db2.example.org'] + $parts]))->toBe(['searchDatabase' => ['host' => 'db2.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os']])
            ->and($view->changes($sent + ['searchDatabase' => ['password' => 'new'] + $parts]))->toBe(['searchDatabase' => ['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os', 'password' => 'new']]);
    });

    test('a database the grid has a default for: its default kept, chosen, or left for parts of its own', function () {
        $form = (new Contract(contract()))->settingsOf('search');
        $default = W4OS\Views\FormView::of($form, ['provide' => true, 'searchDatabase' => null], null, true, 250);
        $own = W4OS\Views\FormView::of($form, ['provide' => true, 'searchDatabase' => ['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os', 'password' => '********']], null, true, 250);
        $parts = ['host' => 'db.example.org', 'port' => '3306', 'database' => 'search', 'user' => 'os', 'password' => ''];

        expect($default->values['searchDatabase'])->toMatchArray(['useDefault' => true, 'host' => ''])
            ->and($own->values['searchDatabase'])->toMatchArray(['useDefault' => false, 'host' => 'db.example.org'])
            ->and($default->changes(['provide' => '1', 'searchDatabase' => ['useDefault' => '1'] + $parts]))->toBe([])
            ->and($default->changes(['provide' => '1', 'searchDatabase' => $parts]))->toBe(['searchDatabase' => ['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os']])
            ->and($own->changes(['provide' => '1', 'searchDatabase' => ['useDefault' => '1'] + $parts]))->toBe(['searchDatabase' => ['useDefault' => true]])
            ->and($own->changes(['provide' => '1', 'searchDatabase' => $parts]))->toBe([]);
    });

    test('what the options set is in the form whatever Provide is now, so that checking it shows it', function () {
        $form = readonly_module((new Contract(contract()))->settingsOf('offline'));
        $off = W4OS\Views\FormView::of($form, ['provide' => false, 'offlineModule' => 'OfflineMessageModule'], null, true, 250);

        expect(array_column($off->shown, 'name'))->toBe(['offlineModule']);
    });

    test('a field shown by Provide and by what the options set is in the form whatever Provide is now', function () {
        $form = (new Contract(contract()))->settingsOf('economy');
        $off = W4OS\Views\FormView::of($form, ['provide' => false, 'economyModule' => 'DTLNSLMoneyModule'], null, true, 250);

        expect(array_column($off->fields, 'name'))->toContain('moneyDatabase', 'moneyScriptKey', 'economyUrl')
            ->and(W4OS\Views\FormView::met(array_column($form['fields'], null, 'name')['moneyDatabase'], $off->item))->toBeFalse();
    });
});
