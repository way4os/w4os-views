<?php

use W4OS\Views\Access;
use W4OS\Views\Contract;

describe('the settings of a site', function () {
    test('are changed by whom the contract says (x-site), the engine\'s rule without it, nobody when neither says', function () {
        $api = new Contract(contract());

        expect($api->siteAccess('settings'))->toBe(['level' => 200, 'self' => false])
            ->and(Access::allows($api->siteAccess('settings'), 200))->toBeTrue()
            ->and(Access::allows($api->siteAccess('settings'), 100))->toBeFalse()
            ->and((new Contract([]))->siteAccess('settings'))->toBe(['level' => 200, 'self' => false])
            ->and((new Contract([]))->siteAccess('nothing'))->toBe(['level' => PHP_INT_MAX, 'self' => false]);
    });
});
