<?php

use W4OS\Views\Contract;

describe('a form of settings of a grid', function () {
    test('is its x-form: its title, its button, its fields with their values and conditions, who reads and who changes it', function () {
        $form = (new Contract(contract()))->settingsOf('models');
        $fields = array_column($form['fields'], null, 'name');

        expect($form)->toMatchArray(['group' => 'models', 'title' => 'settings_models', 'scoped' => true])
            ->and(array_keys($fields))->toBe(['match', 'pattern', 'list'])
            ->and($fields['match']['values'][0])->toBe(['value' => 'first', 'label' => 'models_match_first'])
            ->and($fields['pattern']['condition'])->toBe(['match' => ['first', 'last', 'any']])
            ->and($fields['list'])->toMatchArray(['type' => 'avatar', 'multiple' => true])
            ->and($form['read']['operation'])->toBe('getModelsSettings')
            ->and($form['change']['operation'])->toBe('updateModelsSettings')
            ->and($fields['match']['access']['level'])->toBe($form['change']['level']);
    });

    test('is none for a group the engine does not describe', function () {
        expect((new Contract(contract()))->settingsOf('nothing'))->toBeNull();
    });
});
