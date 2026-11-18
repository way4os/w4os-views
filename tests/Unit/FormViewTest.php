<?php

use W4OS\Views\Contract;
use W4OS\Views\FormView;

describe('the form of an item', function () {
    $api = new Contract(contract());

    test('knows who the viewer is to the item: manager, self (its avatar), owner', function () {
        expect(FormView::roles(['uuid' => 'u1', 'owner' => 'u2'], 'u2', false))->toBe(['owner'])
            ->and(FormView::roles(['uuid' => 'u1'], 'u1', true))->toBe(['manager', 'self'])
            ->and(FormView::roles(['uuid' => 'u1'], null, false))->toBe([]);
    });

    test('shows a field by its condition and its access, lets change it by its access and the level of the viewer', function () use ($api) {
        $fields = array_column($api->itemOf('regions')['fields'], null, 'name');
        $avatar = array_column($api->itemOf('avatars')['fields'], null, 'name');

        expect(FormView::visible($fields['enabled'], ['hyperlink' => false, 'local' => true], ['manager']))->toBeTrue()
            ->and(FormView::visible($fields['enabled'], ['hyperlink' => false, 'local' => false], ['manager']))->toBeFalse()
            ->and(FormView::editable($fields['enabled'], ['manager'], 200))->toBeTrue()
            ->and(FormView::editable($fields['enabled'], ['owner']))->toBeFalse()
            ->and(FormView::editable($fields['name'], ['manager'], 200))->toBeFalse()
            ->and(FormView::editable($avatar['about'], ['self']))->toBeTrue()
            ->and(FormView::editable($avatar['role'], ['self']))->toBeFalse();
    });

    test('its groups with the fields the viewer sees, the fields it changes with their value, the ones it may not', function () use ($api) {
        $form = $api->itemOf('regions');
        $local = ['uuid' => 'r1', 'name' => 'Welcome', 'hyperlink' => false, 'local' => true, 'enabled' => true];
        $far = ['uuid' => 'r2', 'name' => 'Far', 'hyperlink' => true, 'local' => false];
        $names = fn (array $groups) => array_merge(...array_map(fn ($group) => array_column($group['fields'], 'name'), $groups));
        $view = FormView::of($form, $local, null, true, 200);

        expect($view->roles)->toBe(['manager'])
            ->and($names($view->groups))->toContain('name', 'enabled')
            ->and($names(FormView::of($form, $far, null, true, 200)->groups))->not->toContain('enabled')
            ->and($view->changeable)->toBeTrue()
            ->and(FormView::of($form, $far, null, true, 200)->changeable)->toBeFalse()
            ->and(FormView::of($form, $local, 'someone', false)->changeable)->toBeFalse()
            ->and(array_column($view->fields, 'name'))->toContain('enabled', 'owner')->not->toContain('name')
            ->and($view->locked)->toContain('name')->not->toContain('enabled')
            ->and(FormView::of($form, ['uuid' => 'r1', 'enabled' => true], null, true, 200)->values)->toBe(['id' => 'r1', 'owner' => '', 'roles' => [], 'enabled' => true]);
    });

    test('a posted form changes what differs only: a box left unchecked, choices in any order, a password only when one is typed', function () use ($api) {
        $region = $api->itemOf('regions');
        $avatar = $api->itemOf('avatars');
        $local = ['uuid' => 'r1', 'hyperlink' => false, 'local' => true, 'enabled' => true];
        $ada = ['uuid' => 'u1', 'about' => 'Hi', 'wantsList' => ['meet', 'build'], 'role' => 'resident'];

        expect(FormView::of($region, $local, null, true, 200)->changes([]))->toBe(['enabled' => false])
            ->and(FormView::of($region, $local, null, true, 200)->changes(['enabled' => '1']))->toBe([])
            ->and(FormView::of($region, ['hyperlink' => false, 'local' => false, 'enabled' => null], null, true, 200)->changes([]))->toBe([])
            ->and(FormView::of($region, $local + ['roles' => ['Persistent']], null, true, 200)->changes(['roles' => ['Persistent', 'FallbackRegion'], 'enabled' => '1']))->toBe(['roles' => ['FallbackRegion', 'Persistent']])
            ->and(FormView::of($avatar, $ada, 'u1', false)->changes(['about' => 'Hi', 'wantsList' => ['build', 'meet'], 'role' => 'god', 'password' => '']))->toBe([])
            ->and(FormView::of($avatar, $ada, null, true, 200)->changes(['about' => 'Hello', 'wantsList' => ['build', 'meet'], 'role' => 'god', 'password' => 'secret1']))
            ->toMatchArray(['about' => 'Hello', 'role' => 'god', 'password' => 'secret1']);
    });

    test('the names of the choices a form posts: unique, sorted, none empty', function () {
        expect(FormView::checked(['b', 'a', '', 'b']))->toBe(['a', 'b'])->and(FormView::checked(null))->toBe([]);
    });
});
