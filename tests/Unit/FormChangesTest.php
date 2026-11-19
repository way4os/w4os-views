<?php

use W4OS\Views\FormView;

describe('the changes of a posted form', function () {
    $form = ['fields' => [['name' => 'list', 'label' => 'models_list', 'type' => 'avatar', 'multiple' => true, 'access' => ['edit' => ['manager'], 'level' => 200]]]];

    test('take a field of several values as a list: changed, unchanged in another order, emptied', function () use ($form) {
        $view = FormView::of($form, ['list' => ['a1', 'b2']], null, true, 200);

        expect($view->changes(['list' => ['b2', 'c3']]))->toBe(['list' => ['b2', 'c3']])
            ->and($view->changes(['list' => ['b2', 'a1']]))->toBe([])
            ->and($view->changes(['list' => []]))->toBe(['list' => []])
            ->and($view->changes([]))->toBe(['list' => []])
            ->and($view->changes(['list' => ['a1', '', 'b2']]))->toBe([]);
    });

    test('take a field the browser posts as several values even without its multiple (an array posted)', function () {
        $view = FormView::of(['fields' => [['name' => 'tags', 'type' => 'text', 'access' => ['edit' => ['manager'], 'level' => 200]]]], ['tags' => 'x'], null, true, 200);

        expect($view->changes(['tags' => ['x', 'y']]))->toBe(['tags' => ['x', 'y']]);
    });
});
