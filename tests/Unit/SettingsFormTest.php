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

    test('is none for a group the engine does not describe', function () {
        expect((new Contract(contract()))->settingsOf('nothing'))->toBeNull();
    });
});

describe('a form of the settings of a service', function () {
    test('shows what the options of the grid set, never changes it', function () {
        $form = (new Contract(contract()))->settingsOf('offline');
        $view = W4OS\Views\FormView::of($form, ['offlineModule' => 'OfflineMessageModule', 'offlineMessageUrl' => 'https://play.example.org/helpers/offline', 'mailSender' => 'a@example.org'], null, true, 250);

        expect(array_column($view->shown, 'name'))->toBe(['offlineModule', 'offlineMessageUrl'])
            ->and(array_column($view->fields, 'name'))->toBe(['mailSender'])
            ->and($view->changes(['offlineModule' => 'Offline Message Module V2', 'mailSender' => 'b@example.org']))->toBe(['mailSender' => 'b@example.org']);
    });

    test('a database as its parts, its password never shown, kept unless one is typed', function () {
        $form = (new Contract(contract()))->settingsOf('search');
        $item = ['searchDatabase' => ['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os', 'password' => '********', 'options' => []]];
        $view = W4OS\Views\FormView::of($form, $item, null, true, 250);
        $parts = ['host' => 'db.example.org', 'port' => '3306', 'database' => 'search', 'user' => 'os', 'password' => ''];

        expect(array_column(array_column($form['fields'], null, 'name')['searchDatabase']['fields'], 'name'))->toBe(['host', 'port', 'database', 'user', 'password'])
            ->and($view->values['searchDatabase'])->toBe(['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os'])
            ->and($view->changes(['searchDatabase' => $parts]))->toBe([])
            ->and($view->changes(['searchDatabase' => ['host' => 'db2.example.org'] + $parts]))->toBe(['searchDatabase' => ['host' => 'db2.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os']])
            ->and($view->changes(['searchDatabase' => ['password' => 'new'] + $parts]))->toBe(['searchDatabase' => ['host' => 'db.example.org', 'port' => 3306, 'database' => 'search', 'user' => 'os', 'password' => 'new']]);
    });
});
