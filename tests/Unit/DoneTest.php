<?php

use W4OS\Views\Contract;
use W4OS\Views\Done;

describe('the message once an action is done', function () {
    test('the engine\'s key, the same by its rule without the engine', function () {
        $contract = new Contract(contract());
        foreach (['avatars', 'regions', 'simulators'] as $type) {
            foreach ($contract->listOf($type)['actions'] as $action) {
                expect(Done::key($action['name']))->toBe(W4OS\Engine\I18n\Catalog::doneKey($action['name']))
                    ->and(Done::byRule($action['name']))->toBe(W4OS\Engine\I18n\Catalog::doneKey($action['name']));
            }
        }
    });

    test('manage_ and the name of the action in snake case', function () {
        expect(Done::byRule('disable'))->toBe('manage_disable')
            ->and(Done::byRule('estateBan'))->toBe('manage_estate_ban');
    });
});
