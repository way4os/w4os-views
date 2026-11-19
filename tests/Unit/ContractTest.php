<?php

use W4OS\Views\Contract;

describe('a list of the contract', function () {
    test('read from its list operation and its x-list', function () {
        $list = (new Contract(contract()))->listOf('avatars');

        expect(array_column($list['columns'], 'name'))->toBe(['name', 'email', 'shownRole', 'type', 'created', 'lastSeen', 'estates', 'regions', 'parcels', 'status'])
            ->and($list['columns'][0])->toMatchArray(['label' => 'avatar_name', 'type' => 'text', 'sort' => 'name', 'picture' => 'profileImage'])
            ->and($list['sort'])->toMatchArray(['default' => '-created', 'descending' => '-'])
            ->and($list['sort']['values'])->toContain('level')
            ->and($list['search'])->toBe(['name', 'email'])
            ->and(array_column($list['filters'], 'name'))->toContain('status', 'role', 'technical')
            ->and(array_column($list['actions'], 'name'))->toContain('enable', 'disable', 'ban')
            ->and($list['read'])->toBe(['level' => 100, 'self' => false, 'operation' => 'listAvatars'])
            ->and($list['scoped'])->toBeTrue()
            ->and((new Contract(contract()))->listOf('simulators')['scoped'])->toBeFalse()
            ->and((new Contract(contract()))->listOf('nothing'))->toBeNull();
    });

    test('its texts as the engine names them, the ones it gives', function () {
        $contract = new Contract(contract());

        expect($contract->listOf('posts')['texts'])->toBe(['title' => 'flux_title', 'menu' => 'flux_menu', 'placeholder' => 'flux_search', 'empty' => 'flux_empty', 'count' => 'posts_count'])
            ->and($contract->listOf('simulators')['texts'])->toBe([]);
    });

    test('a filter keeps its format, its several values, its dynamic values and their descriptions', function () {
        $filters = array_column((new Contract(contract()))->listOf('regions')['filters'], null, 'name');

        expect($filters['owner'])->toMatchArray(['format' => 'uuid', 'values' => []])
            ->and($filters['flags'])->toMatchArray(['multiple' => true, 'dynamic' => true])
            ->and($filters['flags']['values'][0])->toBe(['value' => 'DefaultRegion', 'label' => 'region_flag_default_region', 'description' => 'region_flag_default_region_help']);
    });

    test('each action with who may do it', function () {
        $actions = array_column((new Contract(contract()))->listOf('avatars')['actions'], null, 'name');

        expect($actions['disable']['access'])->toBe(['level' => 100, 'self' => true, 'operation' => 'updateAvatar'])
            ->and($actions['ban'])->toMatchArray(['level' => 200, 'notSelf' => true, 'ask' => 'ask_ban']);
    });
});

describe('an item of the contract', function () {
    test('read from the x-form of its operation, a field of choices with its values', function () {
        $contract = new Contract(contract());
        $region = $contract->itemOf('regions');

        expect(array_column($region['groups'], 'name'))->toBe(['region'])
            ->and(array_column($region['fields'], 'name'))->toContain('name', 'uri', 'enabled')
            ->and(array_column($region['actions'], 'name'))->toBe(['enable', 'disable'])
            ->and(array_column(array_column($region['fields'], null, 'name')['roles']['values'], 'label', 'value'))->toMatchArray(['FallbackRegion' => 'region_flag_fallback_region'])
            ->and($region['change'])->toBe(['level' => 200, 'self' => false, 'operation' => 'updateRegion'])
            ->and($contract->itemOf('avatars')['groups'][0])->toBe(['name' => 'inworld', 'label' => 'profile_tab_inworld'])
            ->and($contract->itemOf('nothing'))->toBeNull();
    });

    test('a field without its own level takes the one of the change', function () {
        $fields = array_column((new Contract(contract()))->itemOf('regions')['fields'], null, 'name');

        expect($fields['roles']['access']['level'])->toBe(200)
            ->and($fields['name'])->not->toHaveKey('access.level');
    });
});

describe('the access of an operation', function () {
    test('as its x-access says, its operationId with it', function () {
        expect((new Contract(contract()))->access('DELETE', '/grids/{grid}/avatars/{id}'))->toBe(['level' => 200, 'self' => false, 'operation' => 'deleteAvatar'])
            ->and((new Contract(contract()))->access('GET', '/grids/{grid}/info'))->toBe(['level' => null, 'self' => false, 'operation' => 'getInfo']);
    });

    test('none for an operation the contract does not have', function () {
        expect((new Contract([]))->access('GET', '/nothing'))->toMatchArray(['self' => false, 'operation' => '']);
    });
});
