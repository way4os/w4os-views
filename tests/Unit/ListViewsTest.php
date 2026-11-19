<?php

use W4OS\Views\Contract;
use W4OS\Views\ListView;

describe('the views of a list', function () {
    // The avatars, their type and presence marked as views (the contract's `view` of a filter)
    $list = (new Contract(contract()))->listOf('avatars');
    $list['filters'] = array_map(fn (array $filter) => in_array($filter['name'], ['type', 'online'], true) ? $filter + ['view' => true] : $filter, $list['filters']);
    $meta = ['total' => 7, 'counts' => ['type' => ['user' => 5, 'model' => 2, 'service' => 0], 'online' => ['online' => 1, 'offline' => 6]]];

    test('are All, then each value of the filters marked as views that gives rows, in their order, with their counts, as w4os-wp shows them', function () use ($list, $meta) {
        $views = ListView::of($list, [], $meta, ['search' => 'ad'])->views;

        expect(array_map(fn (array $view) => [$view['value'], $view['count'], $view['current']], $views))->toBe([
            [null, 7, true], ['online', 1, false], ['offline', 6, false], ['user', 5, false], ['model', 2, false],
        ])
            ->and($views[0]['query'])->toBe(['search' => 'ad'])
            ->and($views[4])->toMatchArray(['filter' => 'type', 'label' => 'avatar_type_model', 'query' => ['search' => 'ad', 'type' => 'model']]);
    });

    test('mark the one the page asks, All counting the rows of every value of its filter', function () use ($list, $meta) {
        $views = ListView::of($list, [], ['total' => 2] + $meta, ['type' => 'model', 'status' => 'active', 'page' => 2])->views;

        expect(array_column($views, 'current', 'value'))->toMatchArray(['' => false, 'model' => true, 'user' => false])
            ->and($views[0]['count'])->toBe(7)
            ->and($views[0]['query'])->toBe(['status' => 'active'])
            ->and($views[3]['query'])->toBe(['status' => 'active', 'type' => 'user']);
    });

    test('are none for a list without a filter marked as views', function () {
        expect(ListView::of((new Contract(contract()))->listOf('avatars'), [], ['total' => 3], [])->views)->toBe([]);
    });
});
