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
