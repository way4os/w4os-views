<?php

use W4OS\Views\ActionView;
use W4OS\Views\Contract;
use W4OS\Views\IndicatorView;
use W4OS\Views\ItemView;
use W4OS\Views\ListView;

describe('an item to show', function () {
    $avatars = (new Contract(contract()))->listOf('avatars');
    $ada = ['uuid' => 'a1', 'name' => 'Ada Love', 'email' => 'ada@example.org', 'profileImage' => 'pppppppp-pppp-4ppp-8ppp-pppppppppppp',
        'status' => 'active', 'online' => false, 'level' => 0, 'accountState' => 'active', 'ban' => null];

    test('its header: its name and its picture as its list shows them (its first column), round for a person', function () use ($avatars, $ada) {
        $view = ItemView::of($avatars, $ada);

        expect($view->id)->toBe('a1')
            ->and($view->name)->toBe('Ada Love')
            ->and($view->pictured)->toBeTrue()
            ->and($view->picture)->toBe('pppppppp-pppp-4ppp-8ppp-pppppppppppp')
            ->and($view->pictureDefault)->toBe('person')
            ->and($view->round)->toBeTrue()
            ->and(ItemView::of($avatars, ['uuid' => 'a2'])->name)->toBe('a2');
    });

    test('its state: the indicators of its row in its list', function () use ($avatars, $ada) {
        $row = ListView::of($avatars, [$ada], [], [])->rows[0];
        $flat = array_merge(...array_values($row->indicators));

        expect(ItemView::of($avatars, $ada)->states)->toEqual($flat)
            ->and(ItemView::of($avatars, $ada)->states)->each->toBeInstanceOf(IndicatorView::class);
    });

    test('its actions: the ones it is given, the safe ones first, the ones the engine asks to confirm last', function () use ($avatars, $ada) {
        $actions = [['name' => 'ban', 'label' => 'action_ban', 'ask' => 'ask_ban'], ['name' => 'enable', 'label' => 'action_enable']];
        $view = ItemView::of($avatars, $ada, actions: $actions);

        expect(array_map(fn (ActionView $a) => $a->name, $view->actions))->toBe(['enable', 'ban'])
            ->and($view->actions[1]->danger)->toBeTrue();
    });

    test('its details: its groups of fields, without what the header shows (its name, its picture, its state); a group left empty goes', function () use ($avatars, $ada) {
        $groups = [
            ['label' => 'profile_tab_inworld', 'fields' => [['name' => 'name'], ['name' => 'profileImage'], ['name' => 'email'], ['name' => 'accountState']]],
            ['label' => 'profile_tab_web', 'fields' => [['name' => 'ban']]],
        ];
        $view = ItemView::of($avatars, $ada, $groups);

        expect($view->groups)->toBe([['label' => 'profile_tab_inworld', 'fields' => [['name' => 'email']]]]);
    });

    test('a list the contract does not describe: its header from the item alone, no state', function () use ($ada) {
        $view = ItemView::of(['columns' => []], $ada);

        expect($view->name)->toBe('Ada Love')->and($view->pictured)->toBeFalse()->and($view->picture)->toBeNull()->and($view->states)->toBe([]);
    });
});
