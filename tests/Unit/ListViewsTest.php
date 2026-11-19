<?php

use W4OS\Views\Contract;
use W4OS\Views\ListView;

describe('the views of a list', function () {
    // The avatars: their views are their type, whether they are active, their presence (the contract's `views`)
    $list = (new Contract(contract()))->listOf('avatars');
    $meta = ['total' => 7, 'counts' => ['type' => ['user' => 5, 'model' => 2, 'service' => 0], 'active' => ['yes' => 4, 'no' => 1], 'online' => ['online' => 1, 'offline' => 4]]];

    test('are the filters the contract shows as views, in their order', function () use ($list) {
        expect($list['views'])->toBe(['type', 'active', 'online'])
            ->and((new Contract(contract()))->listOf('regions')['views'])->toBe(['status', 'size']);
    });

    test('are All, then each value of the filters shown as views that gives rows, in their order, with their counts, as w4os-wp shows them', function () use ($list, $meta) {
        $views = ListView::of($list, [], $meta, ['search' => 'ad'])->views;

        expect(array_map(fn (array $view) => [$view['value'], $view['count'], $view['current']], $views))->toBe([
            [null, 7, true], ['user', 5, false], ['model', 2, false], ['yes', 4, false], ['no', 1, false], ['online', 1, false], ['offline', 4, false],
        ])
            ->and($views[0]['query'])->toBe(['search' => 'ad'])
            ->and($views[2])->toMatchArray(['filter' => 'type', 'label' => 'avatar_type_model', 'query' => ['search' => 'ad', 'type' => 'model']]);
    });

    test('mark the one the page asks, All counting the rows of every value of its filter', function () use ($list, $meta) {
        $views = ListView::of($list, [], ['total' => 2] + $meta, ['type' => 'model', 'status' => 'active', 'page' => 2])->views;

        expect(array_column($views, 'current', 'value'))->toMatchArray(['' => false, 'model' => true, 'user' => false])
            ->and($views[0]['count'])->toBe(7)
            ->and($views[0]['query'])->toBe(['status' => 'active'])
            ->and($views[1]['query'])->toBe(['status' => 'active', 'type' => 'user'])
            ->and($views[3])->toMatchArray(['filter' => 'active', 'label' => 'view_active']);
    });

    test('are none for a list without views', function () {
        expect(ListView::of((new Contract(contract()))->listOf('simulators'), [], ['total' => 3], [])->views)->toBe([]);
    });
});
