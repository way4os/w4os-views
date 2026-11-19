<?php

use W4OS\Views\ActionView;
use W4OS\Views\Contract;
use W4OS\Views\IndicatorView;
use W4OS\Views\ListView;

describe('a list to show', function () {
    $solo = ['id' => 'h:9040', 'name' => 'Test Solo', 'slug' => 'demo.solo', 'grid' => 'demo', 'uri' => 'h:9040', 'httpPort' => 9040, 'enabled' => true, 'running' => true, 'state' => 'running',
        'connections' => ['console' => 'ok', 'screen' => 'failed', 'config' => 'configured', 'regions' => 'none']];

    test('its columns: each its priority as the contract gives it, 1 at every width, a higher number hidden first; bare or after their label', function () use ($solo) {
        $view = ListView::of((new Contract(contract()))->listOf('simulators'), [$solo], ['total' => 1], []);
        $columns = array_column(array_map(fn ($c) => (array) $c, $view->columns), null, 'name');

        expect($columns['name'])->toMatchArray(['priority' => 1, 'primary' => true])
            ->and($columns['status'])->toMatchArray(['priority' => 1, 'primary' => true])
            ->and($columns['regions'])->toMatchArray(['priority' => 2, 'primary' => false])
            ->and($columns['grid'])->toMatchArray(['priority' => 3, 'bare' => false])
            // The others in the line under the name, the ones shown first first
            ->and(array_column(array_map(fn ($c) => (array) $c, $view->secondary), 'priority'))->toBe([2, 2, 2, 2, 3, 3, 3, 4, 4])
            ->and(array_column(array_map(fn ($c) => (array) $c, $view->secondary), 'name'))->toBe(['regions', 'avatars', 'cpu', 'memory', 'grid', 'server', 'area', 'httpPort', 'prims']);

        // A column the contract gives no priority: the first and an indicators column at every width, the others next
        $plain = ListView::of(['columns' => [['name' => 'name', 'type' => 'text'], ['name' => 'email', 'type' => 'email'], ['name' => 'status', 'type' => 'indicators', 'indicators' => []]]], [], [], []);
        expect(array_column(array_map(fn ($c) => (array) $c, $plain->columns), 'priority', 'name'))->toBe(['name' => 1, 'email' => 2, 'status' => 1]);

        $avatars = ListView::of((new Contract(contract()))->listOf('avatars'), [], ['total' => 0], []);
        $bare = array_column(array_map(fn ($c) => (array) $c, $avatars->columns), 'bare', 'name');
        expect($bare)->toMatchArray(['email' => true, 'shownRole' => true, 'created' => false, 'lastSeen' => false]);
    });

    test('a column sorts by its sort, the other way once sorted, its state told', function () {
        $list = (new Contract(contract()))->listOf('avatars');
        $columns = fn (array $query) => array_column(array_map(fn ($c) => (array) $c, ListView::of($list, [], [], $query)->columns), null, 'name');

        expect($columns([])['created'])->toMatchArray(['sort' => 'created', 'sorted' => true, 'descending' => true, 'next' => 'created'])
            ->and($columns([])['name'])->toMatchArray(['sorted' => false, 'next' => 'name'])
            ->and($columns(['sort' => 'name'])['name'])->toMatchArray(['sorted' => true, 'descending' => false, 'next' => '-name'])
            ->and($columns([])['status']['sort'])->toBe('active');
    });

    test('a row: its link, its address under its name, its state', function () use ($solo) {
        $links = [['key' => 'action_view_item', 'attribute' => 'profilePage'], ['key' => 'action_edit', 'url' => '/sim?id={uuid}']];
        $row = ListView::of((new Contract(contract()))->listOf('simulators'), [$solo], ['total' => 1], [], $links)->rows[0];

        expect($row->id)->toBe('h:9040')
            ->and($row->link)->toBe('/sim?id=h%3A9040')
            ->and($row->links)->toBe([['url' => '/sim?id=h%3A9040', 'key' => 'action_edit']])
            ->and($row->subtitle)->toBe('h:9040')->and($row->subtitleLink)->toBeNull();

        $region = ListView::of((new Contract(contract()))->listOf('regions'), [['uuid' => 'r1', 'name' => 'Test East', 'uri' => 'h:8002/Test East', 'teleport' => 'hop://h:8002/Test%20East']], [], [])->rows[0];
        expect($region->subtitle)->toBe('h:8002/Test East')->and($region->subtitleLink)->toBe('hop://h:8002/Test%20East');
    });

    test('its indicators: the state each value meets, its icon over the indicator\'s, its tone, the action of its icon', function () use ($solo) {
        $row = ListView::of((new Contract(contract()))->listOf('simulators'), [$solo], [], [])->rows[0];
        $indicators = array_column(array_map(fn (IndicatorView $i) => (array) $i, $row->indicators['status']), null, 'name');

        expect(array_keys($indicators))->toBe(['enabled', 'state', 'connections.console', 'connections.screen', 'connections.config'])
            ->and($indicators['enabled'])->toMatchArray(['icon' => 'power', 'tone' => 'ok', 'label' => 'state_enabled'])
            ->and($indicators['enabled']['action'])->toBeInstanceOf(ActionView::class)
            ->and($indicators['enabled']['action']->name)->toBe('disable')
            ->and($indicators['state'])->toMatchArray(['icon' => 'play', 'tone' => 'ok'])
            ->and($indicators['state']['action']->ask)->toBe('ask_stop_instance')
            ->and($indicators['connections.screen'])->toMatchArray(['tone' => 'failed', 'action' => null]);
    });

    test('its menu: the other actions, the safe ones first, the ones to confirm last and marked', function () use ($solo) {
        $row = ListView::of((new Contract(contract()))->listOf('simulators'), [$solo], [], [])->rows[0];
        $names = array_map(fn (ActionView $a) => $a->name, $row->actions);

        expect($names)->toContain('restart')->not->toContain('stop', 'disable')
            ->and($row->actions[array_key_last($row->actions)]->danger)->toBeTrue()
            ->and($row->actions[array_key_last($row->actions)]->ask)->toBe('ask_restart_instance');
    });

    test('only the actions the viewer may do, never on their own row when the action says so', function () {
        $avatars = (new Contract(contract()))->listOf('avatars');
        $row = fn (?int $level, ?string $self) => ListView::of($avatars, [['uuid' => 'a1', 'status' => 'active', 'accountState' => 'active', 'email' => 'a@example.org', 'estateBanned' => false]], [], [], [], $self, $level)->rows[0];
        $names = fn ($r) => array_merge(array_map(fn ($a) => $a->name, $r->actions), array_filter(array_map(fn ($i) => $i->action?->name, array_merge(...array_values($r->indicators)))));

        expect($names($row(200, 'me')))->toContain('ban', 'estateBan', 'disable')
            ->and($names($row(150, 'me')))->not->toContain('ban')
            ->and($names($row(200, 'a1')))->not->toContain('ban', 'disable');
    });

    test('its filters chosen, their count and the query that removes them, keeping the search and the sort', function () {
        $list = (new Contract(contract()))->listOf('simulators');
        $view = ListView::of($list, [], ['total' => 0], ['search' => 'solo', 'enabled' => 'yes', 'sort' => 'name', 'page' => 2]);

        expect($view->activeFilters)->toBe(1)
            ->and($view->clearQuery)->toBe(['search' => 'solo', 'sort' => 'name'])
            ->and($view->total)->toBe(0);
    });

    test('a list the contract describes only in part: its columns alone', function () {
        $view = ListView::of(['columns' => [['name' => 'name']]], [['uuid' => 'x', 'name' => 'X']], [], []);

        expect($view->rows[0]->id)->toBe('x')->and($view->rows[0]->actions)->toBe([])->and($view->columns[0]->sort)->toBeNull();
    });

    test('a filter the bridge fixes to one value: its column and the filter hidden, every row has that value', function () use ($solo) {
        $list = (new Contract(contract()))->listOf('simulators');
        $view = ListView::of($list, [$solo], [], ['grid' => 'other'], fixed: ['grid' => 'demo']);

        expect(array_map(fn ($c) => $c->name, $view->columns))->toBe(['name', 'server', 'httpPort', 'regions', 'area', 'avatars', 'prims', 'cpu', 'memory', 'status'])
            ->and(array_column($view->list['filters'], 'name'))->not->toContain('grid')
            ->and($view->activeFilters)->toBe(0)
            ->and(array_map(fn ($c) => $c->name, ListView::of($list, [$solo], [], [])->columns))->toContain('grid');
    });

    test('a column that repeats a fixed filter (mirrors) hidden too: the region of the parcels of a region', function () {
        $list = ['columns' => [['name' => 'name'], ['name' => 'regionName', 'mirrors' => 'region'], ['name' => 'ownerName', 'mirrors' => 'owner']], 'filters' => [['name' => 'region'], ['name' => 'owner']]];
        $rows = [['uuid' => 'p', 'name' => 'Beach', 'regionName' => 'Welcome', 'ownerName' => 'Ada Love']];

        expect(array_map(fn ($c) => $c->name, ListView::of($list, $rows, [], [], fixed: ['region' => 'r-uuid'])->columns))->toBe(['name', 'ownerName'])
            ->and(array_map(fn ($c) => $c->name, ListView::of($list, $rows, [], [])->columns))->toBe(['name', 'regionName', 'ownerName']);
    });

    test('its pages from the meta of the engine', function () {
        $view = ListView::of((new Contract(contract()))->listOf('avatars'), [], ['total' => 120, 'page' => 2, 'perPage' => 50], []);

        expect($view->page)->toBe(2)->and($view->pages)->toBe(3)->and($view->total)->toBe(120);
    });
});
