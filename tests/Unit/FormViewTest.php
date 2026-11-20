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

    test('a list of addresses changes when its order does, empty lines left out', function () {
        $form = ['fields' => [['name' => 'registers', 'type' => 'url', 'multiple' => true, 'label' => 'registers', 'access' => ['level' => 200]]]];
        $view = FormView::of($form, ['registers' => ['https://a.example.org', 'https://b.example.org']], null, true, 250);

        expect($view->changes(['registers' => ['https://b.example.org', 'https://a.example.org', ' ']]))->toBe(['registers' => ['https://b.example.org', 'https://a.example.org']])
            ->and($view->changes(['registers' => ['https://a.example.org', '', 'https://b.example.org']]))->toBe([]);
    });

    test('an address with a default the grid takes without it: Use default while it has none, its value under it, its default chosen removes it', function () {
        $form = ['fields' => [['name' => 'assetServerUri', 'type' => 'url', 'fallback' => 'db_use_default', 'label' => 'uri', 'access' => ['level' => 200]]]];
        $default = FormView::of($form, ['assetServerUri' => null], null, true, 250);
        $own = FormView::of($form, ['assetServerUri' => 'http://assets.example.org:8003'], null, true, 250);

        expect($default->values['assetServerUri'])->toBe(['useDefault' => true, 'value' => ''])
            ->and($own->values['assetServerUri'])->toBe(['useDefault' => false, 'value' => 'http://assets.example.org:8003'])
            ->and($default->changes(['assetServerUri' => ['useDefault' => '1', 'value' => '']]))->toBe([])
            ->and($default->changes(['assetServerUri' => ['value' => ' http://a.example.org:8003 ']]))->toBe(['assetServerUri' => 'http://a.example.org:8003'])
            ->and($default->changes(['assetServerUri' => ['value' => '']]))->toBe([])
            ->and($own->changes(['assetServerUri' => ['useDefault' => '1', 'value' => 'http://assets.example.org:8003']]))->toBe(['assetServerUri' => ['useDefault' => true]])
            ->and($own->changes(['assetServerUri' => ['value' => 'http://assets.example.org:8003']]))->toBe([]);
    });

    test('the names of the choices a form posts: unique, sorted, none empty', function () {
        expect(FormView::checked(['b', 'a', '', 'b']))->toBe(['a', 'b'])->and(FormView::checked(null))->toBe([]);
    });
});
