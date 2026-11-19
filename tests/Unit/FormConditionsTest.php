<?php

use W4OS\Views\Contract;
use W4OS\Views\FormView;

describe('the conditions of a form', function () {
    $models = (new Contract(contract()))->settingsOf('models');
    $any = ['match' => 'any', 'pattern' => 'Default', 'list' => []];

    test('are met by the values of the other fields', function () use ($models) {
        $fields = array_column($models['fields'], null, 'name');

        expect(FormView::met($fields['list'], ['match' => 'uuid']))->toBeTrue()
            ->and(FormView::met($fields['list'], ['match' => 'any']))->toBeFalse()
            ->and(FormView::met($fields['list'], []))->toBeFalse()
            ->and(FormView::met($fields['match'], []))->toBeTrue()
            ->and(FormView::met($fields['list'], ['match' => ['any', 'uuid']]))->toBeTrue()
            ->and(FormView::met($fields['list'], ['match' => ['any']]))->toBeFalse();
    });

    test('keep in the form a field whose condition is on a field the viewer changes, whatever its value now', function () use ($models, $any) {
        $view = FormView::of($models, $any, null, true, 200);

        expect(array_column($view->fields, 'name'))->toBe(['match', 'pattern', 'list'])
            ->and($view->values)->toMatchArray(['match' => 'any', 'list' => []]);

        return $view;
    });

    test('are met by the values posted: a field shown by what is chosen changes with it, a field hidden does not', function (FormView $view) {
        expect($view->changes(['match' => 'uuid', 'pattern' => 'Other', 'list' => ['g1', 'a1']]))->toBe(['match' => 'uuid', 'list' => ['g1', 'a1']])
            ->and($view->changes(['match' => 'any', 'pattern' => 'Model', 'list' => ['g1']]))->toBe(['pattern' => 'Model'])
            ->and($view->changes(['match' => 'any', 'pattern' => 'Default']))->toBe([]);
    })->depends('keep in the form a field whose condition is on a field the viewer changes, whatever its value now');

    test('leave out a field whose condition is on what the viewer does not change, when the item does not meet it', function () {
        $form = ['fields' => [
            ['name' => 'kind', 'readOnly' => true],
            ['name' => 'note', 'condition' => ['kind' => ['local']], 'access' => ['edit' => ['manager'], 'level' => 200]],
        ]];

        expect(array_column(FormView::of($form, ['kind' => 'far'], null, true, 200)->fields, 'name'))->toBe([])
            ->and(array_column(FormView::of($form, ['kind' => 'local'], null, true, 200)->fields, 'name'))->toBe(['note']);
    });
});

test('a condition on a box is met by the box as a form sends it: checked, or left out', function () {
    $field = ['name' => 'path', 'condition' => ['provide' => [true]]];
    $off = ['name' => 'url', 'condition' => ['provide' => [false]]];

    expect([W4OS\Views\FormView::met($field, ['provide' => '1']), W4OS\Views\FormView::met($field, ['provide' => true]), W4OS\Views\FormView::met($field, ['provide' => '0']), W4OS\Views\FormView::met($field, [])])
        ->toBe([true, true, false, false])
        ->and([W4OS\Views\FormView::met($off, ['provide' => '0']), W4OS\Views\FormView::met($off, []), W4OS\Views\FormView::met($off, ['provide' => 'on'])])
        ->toBe([true, true, false]);
});
