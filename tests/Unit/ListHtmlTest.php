<?php

use W4OS\Views\ListDetailView;
use W4OS\Views\ListHtml;
use W4OS\Views\ListView;

/** A list of land for sale as a search shows it: its name, its type, its price, its area and its price per m². */
function land_list(): array
{
    return [
        'type' => 'land',
        'columns' => [
            ['name' => 'name', 'label' => 'column_name', 'type' => 'text', 'sort' => 'name'],
            ['name' => 'kind', 'label' => 'column_type', 'type' => 'text', 'priority' => 3, 'values' => [['value' => 'mainland', 'label' => 'land_mainland']]],
            ['name' => 'price', 'label' => 'column_price', 'type' => 'number', 'sort' => 'price'],
            ['name' => 'area', 'label' => 'column_area', 'type' => 'number'],
            ['name' => 'perMeter', 'label' => 'column_price_m2', 'type' => 'number', 'decimals' => 2],
        ],
        'sort' => ['default' => 'name', 'descending' => '-'],
    ];
}

/** The texts of a page: each key in brackets, its values after it. */
function bracketed(): Closure
{
    return fn (string $key, array $vars = []) => "[$key]".($vars === [] ? '' : ' '.implode(' ', $vars));
}

describe('the HTML of a list', function () {
    $lots = [
        ['uuid' => 'p1', 'name' => 'Sea view', 'kind' => 'mainland', 'price' => 1500, 'area' => 512, 'perMeter' => 2.9296875],
        ['uuid' => 'p2', 'name' => 'Hill <top>', 'kind' => 'island', 'price' => 800, 'area' => 1024, 'perMeter' => 0.78125],
    ];
    $query = ['q' => 'sea', 'category' => 'land', 'rating' => ['general', 'moderate'], 'page' => 2];

    test('is a table of its columns, its numbers with their decimals in the language of the page, its texts escaped', function () use ($lots, $query) {
        $html = ListHtml::table(ListView::of(land_list(), $lots, ['total' => 2], $query), ['t' => bracketed(), 'language' => 'fr']);

        expect($html)->toContain('<table class="w4os-list w4os-list-land">', '<th scope="col" class="w4os-col-number w4os-tier-md">[column_area]</th>', '<th scope="row" class="w4os-col-text">Sea view<div class="w4os-subtitle w4os-folded">')
            ->toContain('<td class="w4os-col-text w4os-tier-xl">[land_mainland]</td>', '<td class="w4os-col-text w4os-tier-xl">island</td>')
            ->toContain(">1\u{202F}500<", '>2,93<', '>0,78<')
            ->toContain('Hill &lt;top&gt;')->not->toContain('<top>')
            // A narrow list: the values of its hidden columns under the name, each until its column shows, after its label
            ->toContain('<div class="w4os-subtitle w4os-folded"><span class="w4os-fold-md">[column_price]: 1'."\u{202F}".'500</span><span class="w4os-fold-md">[column_area]: 512</span><span class="w4os-fold-md">[column_price_m2]: 2,93</span><span class="w4os-fold-xl">[column_type]: [land_mainland]</span></div>')
            ->and(ListHtml::table(ListView::of(land_list(), $lots, [], []), ['t' => bracketed(), 'language' => 'en']))->toContain('>1,500<', '>2.93<');
    });

    test('says a number with its unit when its column or its field has one: an event of 60 minutes, an area in m²', function () {
        $html = ListHtml::cell(['name' => 'duration', 'type' => 'number', 'unit' => 'min'], ['duration' => 60], ['language' => 'fr']);

        expect($html)->toBe("60\u{00A0}min")
            ->and(ListHtml::cell(['name' => 'area', 'type' => 'number', 'unit' => 'm²'], ['area' => 1024], ['language' => 'en']))->toBe("1,024\u{00A0}m²")
            ->and(ListHtml::cell(['name' => 'area', 'type' => 'number'], ['area' => 1024], ['language' => 'en']))->toBe('1,024');
    });

    test('says a duration in minutes under an hour, in hours from 60, with the units of the catalog', function () {
        $duration = static fn(int $minutes): string => ListHtml::cell(['name' => 'duration', 'type' => 'duration'], ['duration' => $minutes], ['t' => bracketed(), 'language' => 'fr']);

        expect($duration(45))->toBe('[unit_minutes] 45')
            ->and($duration(60))->toBe('[unit_hours] 1')
            ->and($duration(90))->toBe('[unit_hours_minutes] 1 30')
            ->and($duration(125))->toBe('[unit_hours_minutes] 2 05')
            ->and($duration(120))->toBe('[unit_hours] 2');
    });

    test('keeps the line breaks of a text, escaped: a description of several lines', function () {
        $html = ListHtml::cell(['name' => 'description', 'type' => 'text'], ['description' => "hop://grid:8002/Region\nsecondlife://grid:8002/Region\nCome <now>"], []);

        expect($html)->toBe("hop://grid:8002/Region<br />\nsecondlife://grid:8002/Region<br />\nCome &lt;now&gt;");
    });

    test('keeps the whole query of the page in its sorts and its pages (the criteria of a search), back to the first page on a new sort', function () use ($lots, $query) {
        $html = ListHtml::table(ListView::of(land_list(), $lots, ['total' => 120, 'page' => 2, 'perPage' => 50], $query), ['t' => bracketed()]);
        $criteria = 'q=sea&category=land&rating%5B0%5D=general&rating%5B1%5D=moderate';

        expect($html)->toContain('<th scope="col" class="w4os-col-text" aria-sort="ascending"><a href="?sort=-name&amp;'.str_replace('&', '&amp;', $criteria).'">[column_name]<span class="w4os-sort-mark" aria-hidden="true">▲</span></a></th>')
            ->toContain('<a href="?sort=price&amp;'.str_replace('&', '&amp;', $criteria).'">[column_price]</a>')
            ->toContain('<nav class="w4os-pages"><a class="w4os-page-prev" rel="prev" href="?page=1&amp;'.str_replace('&', '&amp;', $criteria).'">[web_search_previous]</a><a class="w4os-page-next" rel="next" href="?page=3&amp;')
            ->and(ListHtml::table(ListView::of(land_list(), $lots, ['total' => 2], []), ['t' => bracketed()]))->not->toContain('w4os-pages');
    });

    test('says there are no rows only when the page asks it (a search done), nothing otherwise', function () {
        $none = ListView::of(land_list(), [], ['total' => 0], []);

        expect(ListHtml::table($none, ['t' => bracketed()]))->toBe('')
            ->and(ListHtml::table($none, ['t' => bracketed(), 'empty' => 'web_search_none']))->toBe('<p class="w4os-list-empty">[web_search_none]</p>');
    });
});

describe('a list with the detail of the item it selects', function () {
    $lots = [['uuid' => 'p1', 'name' => 'Sea view', 'price' => 1500], ['uuid' => 'p2', 'name' => 'Hill', 'price' => 800]];
    $groups = [['label' => 'group_lot', 'fields' => [
        ['name' => 'price', 'label' => 'field_price', 'type' => 'number'],
        ['name' => 'description', 'label' => 'field_description', 'type' => 'text'],
        ['name' => 'when', 'label' => 'field_when', 'type' => 'datetime'],
    ]], ['label' => 'group_members', 'fields' => [
        ['name' => 'members', 'label' => 'field_members', 'type' => 'table', 'columns' => [
            ['name' => 'name', 'label' => 'column_name', 'type' => 'link', 'link' => 'profile'],
            ['name' => 'title', 'label' => 'column_title', 'type' => 'text'],
        ]],
    ]]];
    $item = ['uuid' => 'p2', 'name' => 'Hill', 'price' => 800, 'description' => 'Quiet', 'when' => '2026-11-21T20:00:00Z',
        'members' => [['name' => 'Ada Love', 'title' => 'Owner', 'profile' => '/profile/Ada.Love'], ['name' => 'Bob', 'title' => '']],
        'tpLinks' => ['browser' => [['action' => 'teleport', 'format' => 8, 'link' => 'hop://h:8002/Hill', 'label' => 'Teleport (hop://)'], ['action' => 'copy', 'format' => 1024, 'link' => 'h:8002:Hill', 'label' => 'Copy the address']]]];

    test('selects an item by a parameter of the page, kept with the rest of its query; each row the query that selects it', function () use ($lots, $item, $groups) {
        $view = ListDetailView::of(ListView::of(land_list(), $lots, [], ['q' => 'sea', 'id' => 'p2', 'page' => 2]), $item, $groups);

        expect($view->selected)->toBe('p2')->and($view->param)->toBe('id')->and($view->region)->toBe('w4os-detail')
            ->and($view->select)->toBe(['p1' => ['q' => 'sea', 'id' => 'p1', 'page' => 2], 'p2' => ['q' => 'sea', 'id' => 'p2', 'page' => 2]])
            ->and($view->detail?->name)->toBe('Hill')
            // An item off the page of the list: its detail still there
            ->and(ListDetailView::of(ListView::of(land_list(), [$lots[0]], [], ['id' => 'p2']), $item, $groups)->detail?->id)->toBe('p2')
            ->and(ListDetailView::of(ListView::of(land_list(), $lots, [], []), null)->selected)->toBeNull();
    });

    test('links each row to its detail, without a script too (the anchor of the detail), the selected one marked', function () use ($lots, $item, $groups) {
        $html = ListHtml::listDetail(ListDetailView::of(ListView::of(land_list(), $lots, [], ['q' => 'sea', 'id' => 'p2']), $item, $groups), ['t' => bracketed()]);

        expect($html)->toStartWith('<div class="w4os-list-detail" data-w4os-list-detail data-region="w4os-detail" data-param="id"><div class="w4os-ld has-detail"><div class="w4os-ld-list">')
            ->toContain('<a class="w4os-row-link" href="?q=sea&amp;id=p1#w4os-detail" data-w4os-select="p1" aria-controls="w4os-detail">Sea view</a>')
            ->toContain('<tr class="is-selected"><th scope="row" class="w4os-col-text"><a class="w4os-row-link" href="?q=sea&amp;id=p2#w4os-detail" data-w4os-select="p2" aria-controls="w4os-detail" aria-current="true">Hill</a>')
            ->toContain('<section id="w4os-detail" class="w4os-detail" aria-labelledby="w4os-detail-title"><header class="w4os-detail-header"><h2 id="w4os-detail-title" class="w4os-detail-title" tabindex="-1">Hill</h2></header>')
            ->and(substr_count($html, 'aria-controls="w4os-detail" aria-current="true"'))->toBe(1)
            // None selected: its element there, hidden, for the script to fill
            ->and(ListHtml::listDetail(ListDetailView::of(ListView::of(land_list(), $lots, [], []), null), ['t' => bracketed()]))
            ->toContain('<div class="w4os-ld"><div class="w4os-ld-list">', '<section id="w4os-detail" class="w4os-detail" hidden></section>')->not->toContain('aria-current');
    });

    test('shows the detail: its teleport link, its groups as terms and values, a small table of records, its actions', function () use ($lots, $item, $groups) {
        $view = ListDetailView::of(ListView::of(land_list(), $lots, [], ['id' => 'p2']), $item, $groups);
        $html = ListHtml::item($view->detail, ['t' => bracketed(), 'language' => 'en', 'texts' => ['more' => 'More'], 'go' => 'hg_visit_go', 'actions' => fn ($item) => '<button>Buy '.$item->name.'</button>']);

        expect($html)->toContain('<p class="w4os-detail-go"><span class="w4os-tp" data-w4os-tp data-client="browser"><a class="w4os-tp-main" href="hop://h:8002/Hill" data-browser="hop://h:8002/Hill">[hg_visit_go]')
            ->toContain('<h3 class="w4os-detail-group">[group_lot]</h3><dl class="w4os-fields"><div class="w4os-field w4os-field-number"><dt>[field_price]</dt><dd>800</dd></div><div class="w4os-field w4os-field-text"><dt>[field_description]</dt><dd>Quiet</dd></div>')
            ->toContain('<dt>[field_when]</dt><dd><time datetime="2026-11-21T20:00:00+00:00"')
            ->toContain('<table class="w4os-records"><thead><tr><th scope="col" class="w4os-col-link">[column_name]</th><th scope="col" class="w4os-col-text">[column_title]</th></tr></thead><tbody><tr><td class="w4os-col-link"><a href="/profile/Ada.Love">Ada Love</a></td><td class="w4os-col-text">Owner</td></tr><tr><td class="w4os-col-link">Bob</td><td class="w4os-col-text"></td></tr></tbody></table>')
            ->toContain('<div class="w4os-detail-actions"><button>Buy Hill</button></div>');
    });
});

describe('the assets of a list with its detail', function () {
    test('lay it out side by side when wide, below otherwise, without colours of their own', function () {
        $css = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.ListDetailView::ASSETS['css'][0]);

        expect($css)->toContain('@container w4os-list-detail (min-width: 48rem)', '@container w4os-list (min-width: 36rem) { .w4os-fold-md { display: none; } }', '.w4os-ld.has-detail { grid-template-columns:', 'position: sticky;', '.w4os-detail[hidden] { display: none; }', 'font-variant-numeric: tabular-nums;', '.w4os-list tr.is-selected')
            ->and($css)->not->toMatch('/#[0-9a-f]{3,8}\b|rgba?\(|--pico-|--q2-/i');
    });

    test('have the script: the detail fetched and swapped, its teleport links set up, the history kept, a new tab left alone', function () {
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.ListDetailView::ASSETS['js'][0]);
        $tp = (string) file_get_contents(dirname(__DIR__, 2).'/assets/js/tp-link.js');

        expect($js)->toContain('a[data-w4os-select]', 'event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0', 'getElementById(region)', 'replaceWith(next)', 'window.W4OSTeleportLinks?.setup(next)', 'history.pushState', "addEventListener('popstate'", "setAttribute('aria-current', 'true')")
            ->and($tp)->toContain('window.W4OSTeleportLinks = { setup };');
    });

    test('move the selection with the arrows, as a list of tabs: the next or the previous result, its detail shown', function () {
        $js = (string) file_get_contents(dirname(__DIR__, 2).'/assets/'.ListDetailView::ASSETS['js'][0]);

        expect($js)->toContain("event.key === 'ArrowDown'", "event.key === 'ArrowUp'", '.focus()', 'swap(box, next.href, true)');
    });
});
