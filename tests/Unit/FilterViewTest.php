<?php

use W4OS\Views\Contract;
use W4OS\Views\FilterView;
use W4OS\Views\ListView;

describe('a filter to show', function () {
    test('a choice among values: its field, its values with their label key, the one the page asks chosen', function () {
        $status = array_column((new Contract(contract()))->listOf('avatars')['filters'], null, 'name')['status'];
        $view = FilterView::of($status, ['status' => 'disabled']);

        expect($view->name)->toBe('status')
            ->and($view->label)->toBe('status')
            ->and($view->field)->toBe('select')
            ->and($view->chosen)->toBe(['disabled'])
            ->and($view->active)->toBeTrue()
            ->and($view->values[1])->toMatchArray(['value' => 'disabled', 'label' => 'status_disabled', 'text' => null, 'chosen' => true])
            ->and($view->values[0]['chosen'])->toBeFalse()
            ->and(FilterView::of($status, [])->active)->toBeFalse();
    });

    test('several choices: a field of several values, each one asked chosen, its help as its description', function () {
        $flags = array_column((new Contract(contract()))->listOf('regions')['filters'], null, 'name')['flags'];
        $view = FilterView::of($flags, ['flags' => ['DefaultRegion', 'FallbackRegion']]);

        expect($view->field)->toBe('multiple')
            ->and($view->chosen)->toBe(['DefaultRegion', 'FallbackRegion'])
            ->and($view->values[0])->toMatchArray(['value' => 'DefaultRegion', 'description' => 'region_flag_default_region_help', 'chosen' => true]);
    });

    test('a value written in its format (a UUID, a server): a text field, what the page asks its value', function () {
        $owner = array_column((new Contract(contract()))->listOf('regions')['filters'], null, 'name')['owner'];

        expect(FilterView::of($owner, ['owner' => 'a1b2'])->field)->toBe('text')
            ->and(FilterView::of($owner, ['owner' => 'a1b2'])->chosen)->toBe(['a1b2'])
            ->and(FilterView::of($owner, [])->chosen)->toBe([]);
    });

    test('values a bridge offers from the answer of the engine keep their text, shown as it is', function () {
        $filter = ['name' => 'simulator', 'label' => 'filter_simulator', 'format' => 'simulator', 'multiple' => true, 'values' => [['value' => 'h:9020', 'text' => 'Test Sim']]];

        expect(FilterView::of($filter, [])->field)->toBe('multiple')
            ->and(FilterView::of($filter, [])->values[0])->toMatchArray(['value' => 'h:9020', 'label' => null, 'text' => 'Test Sim', 'chosen' => false]);
    });

    test('a list gives the views of its filters, and counts the ones the page asks', function () {
        $view = ListView::of((new Contract(contract()))->listOf('avatars'), [], [], ['status' => 'active', 'role' => '']);

        expect(array_map(fn (FilterView $f) => $f->name, $view->filters))->toContain('status', 'role', 'type')
            ->and(array_column(array_map(fn (FilterView $f) => (array) $f, $view->filters), 'active', 'name')['status'])->toBeTrue()
            ->and($view->activeFilters)->toBe(1);
    });
});
