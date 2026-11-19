<?php

use W4OS\Views\Contract;
use W4OS\Views\FormView;

describe('the form that makes an item', function () {
    $contract = new Contract(contract());

    test('is offered above its list by the button the engine gives (create), with its label and its operation', function () use ($contract) {
        expect($contract->listOf('avatars')['create'])->toMatchArray(['label' => 'action_new_avatar', 'method' => 'POST', 'path' => '/grids/{grid}/avatars', 'operation' => 'createAvatar'])
            ->and($contract->listOf('regions')['create'] ?? null)->toBeNull();
    });

    test('is the x-form of that operation: its title, its help, its button, its fields in their order, who may make one', function () use ($contract) {
        $form = $contract->creationOf('avatars');
        $fields = array_column($form['fields'], null, 'name');

        expect($form)->toMatchArray(['type' => 'avatars', 'title' => 'action_new_avatar', 'submit' => 'action_create_avatar', 'description' => ['register_text'], 'method' => 'POST', 'path' => '/grids/{grid}/avatars', 'scoped' => true])
            ->and(array_keys($fields))->toBe(['firstName', 'lastName', 'email', 'password', 'model'])
            ->and($fields['firstName'])->toMatchArray(['required' => true, 'pattern' => '[A-Za-z][A-Za-z0-9]*', 'maxLength' => 32])
            ->and($fields['model']['source'])->toMatchArray(['path' => '/grids/{grid}/avatar-models', 'value' => 'uuid'])
            ->and($form['make']['level'])->toBe(100)
            ->and($fields['firstName']['access']['level'])->toBe(100)
            ->and($contract->creationOf('regions'))->toBeNull();
    });

    test('is changed by a manager from the level of its operation', function () use ($contract) {
        $form = $contract->creationOf('avatars');

        expect(FormView::of($form, [], null, true, 100)->changeable)->toBeTrue()
            ->and(FormView::of($form, [], null, true, 50)->changeable)->toBeFalse();
    });
});
