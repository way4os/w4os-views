<?php

use W4OS\Views\Contract;
use W4OS\Views\Rows;

describe('the actions of a row', function () {
    test('the ones whose conditions it meets: when, requires, notSelf', function () {
        $contract = new Contract(contract());
        $avatars = $contract->listOf('avatars');
        $regions = $contract->listOf('regions');
        $names = fn (array $list, array $item, ?string $self = null, ?int $level = null) => array_column(Rows::actions($list, $item, $self, $level), 'name');

        expect($names($avatars, ['uuid' => 'u1', 'status' => 'active', 'email' => 'ada@example.org']))->toBe(['disable', 'reset', 'ban'])
            ->and($names($avatars, ['uuid' => 'u1', 'status' => 'active', 'email' => '']))->toBe(['disable', 'ban'])
            ->and($names($avatars, ['uuid' => 'u1', 'status' => 'active', 'email' => 'ada@example.org'], 'u1'))->toBe(['reset'])
            ->and($names($avatars, ['uuid' => 'u1', 'status' => 'pending', 'email' => 'ada@example.org']))->toBe(['enable', 'ban'])
            ->and($names($regions, ['uuid' => 'r1', 'local' => true, 'enabled' => false]))->toBe(['enable'])
            ->and($names($regions, ['uuid' => 'r1', 'enabled' => null]))->toBe([]);
    });

    test('from the level of their operation, and the level an action asks more (a ban: a god)', function () {
        $avatars = (new Contract(contract()))->listOf('avatars');
        $names = fn (array $item, int $level) => array_column(Rows::actions($avatars, $item, 'me', $level), 'name');
        $active = ['uuid' => 'a1', 'status' => 'active', 'estateBanned' => false];

        expect($names($active, 0))->toBe([])
            ->and($names($active, 150))->toContain('disable')->not->toContain('ban', 'estateBan')
            ->and($names($active, 200))->toContain('ban', 'estateBan')
            ->and($names(['uuid' => 'a1', 'status' => 'banned', 'estateBanned' => true], 200))->toContain('unban', 'estateUnban')->not->toContain('ban');
    });
});

describe('the request of an action', function () {
    test('its method, its path under the grid, its JSON:API document with the row\'s attributes', function () {
        $actions = array_column((new Contract(contract()))->listOf('avatars')['actions'], null, 'name');
        $ada = ['uuid' => 'u1', 'name' => 'Ada Love', 'status' => 'active'];

        expect(Rows::request($actions['disable'], $ada))->toBe(['PATCH', '/avatars/u1', ['type' => 'avatars', 'id' => 'u1', 'attributes' => ['status' => 'disabled']], true])
            ->and(Rows::request($actions['reset'], $ada, ['link' => 'https://site.example.org/reset?token={token}']))
            ->toBe(['POST', '/password-resets', ['type' => 'password-resets', 'attributes' => ['name' => 'Ada Love', 'link' => 'https://site.example.org/reset?token={token}']], true]);
    });

    test('an action of the machine asked outside the grid, its path filled from the row', function () {
        $action = ['name' => 'start', 'method' => 'POST', 'path' => '/instances/{slug}/start'];

        expect(Rows::request($action, ['id' => 'h:9040', 'slug' => 'demo.solo']))->toBe(['POST', '/instances/demo.solo/start', ['type' => 'start', 'attributes' => []], false])
            ->and(Rows::request(['method' => 'PATCH', 'path' => '/grids/{grid}/regions/{id}'], ['uuid' => 'r 1'])[1])->toBe('/regions/r%201')
            ->and(Rows::request(['method' => 'PATCH', 'path' => '/grids/{grid}/regions/{id}'], ['uuid' => 'r1'])[3])->toBeTrue();
    });

    test('a condition on a dotted path (connections.console)', function () {
        $row = ['id' => 'h:9040', 'connections' => ['console' => 'ok', 'robust' => 'none']];
        $list = ['actions' => [['name' => 'console', 'method' => 'POST', 'path' => '/x', 'when' => ['connections.console' => ['ok']]], ['name' => 'robust', 'method' => 'POST', 'path' => '/y', 'when' => ['connections.robust' => ['ok']]]]];

        expect(array_column(Rows::actions($list, $row), 'name'))->toBe(['console']);
    });

    test('a value by its dotted path, the id of a row', function () {
        expect(Rows::value(['connections' => ['console' => 'ok']], 'connections.console'))->toBe('ok')
            ->and(Rows::value(['connections' => null], 'connections.console'))->toBeNull()
            ->and(Rows::id(['uuid' => 'u1', 'id' => 'x']))->toBe('u1')
            ->and(Rows::id(['id' => 'h:9040']))->toBe('h:9040');
    });
});
