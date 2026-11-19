<?php

namespace W4OS\Views;

/**
 * A list of the engine to show: its columns, the ones a narrow screen shows under the name (`secondary`), its rows, its filters
 * (FilterView), its count and its pages, the filters chosen and the query that removes them, its views (the values of the filters
 * the contract marks as views, with their counts).
 */
final readonly class ListView
{
    /** The display types whose value reads without the label of its column (until the contract says it, `bare` of a column). */
    public const BARE = ['email', 'role', 'status', 'size', 'avatar', 'map', 'picture', 'boolean', 'hop'];

    /**
     * @param array<string,mixed> $list the list (Contract::listOf(), with the filters the page shows)
     * @param list<ColumnView> $columns
     * @param list<ColumnView> $secondary
     * @param list<RowView> $rows
     * @param list<FilterView> $filters the filters the page shows, their values and what it asks of them
     * @param array<string,mixed> $query the query of the page
     * @param array<string,mixed> $clearQuery the query without the filters chosen nor its page
     * @param list<array{filter: ?string, value: ?string, label: ?string, text: ?string, count: ?int, query: array<string,mixed>, current: bool}> $views
     *     the views of the list (views())
     */
    public function __construct(
        public array $list,
        public array $columns,
        public array $secondary,
        public array $rows,
        public array $filters,
        public int $total,
        public int $page,
        public int $pages,
        public int $activeFilters,
        public array $clearQuery,
        public array $query,
        public array $views = [],
    ) {}

    /**
     * The view of a page of a list.
     *
     * @param array<string,mixed> $list the list (Contract::listOf(), with the filters the page shows)
     * @param list<array<string,mixed>> $items the rows as the engine gives them
     * @param array<string,mixed> $meta the meta of the engine's answer (total, page, perPage, counts)
     * @param array<string,mixed> $query the query of the page (search, filters, sort, page)
     * @param list<array{key: string, url?: string, attribute?: string}> $links the links of a row: an address with `{uuid}`, or
     *     the attribute that gives one; the first a row has is the link of its name
     * @param ?string $self the UUID of the avatar of whoever looks
     * @param ?int $level their user level; null: not checked
     * @param array<string,mixed> $fixed the filters the bridge always asks one value of (`grid`: the grid of the site): their
     *     column, the one that repeats it (`mirrors`) and the filter are left out, every row has that value
     */
    public static function of(array $list, array $items, array $meta, array $query, array $links = [], ?string $self = null, ?int $level = null, array $fixed = []): self
    {
        $list += ['columns' => [], 'filters' => [], 'actions' => [], 'sort' => []];
        // The column of a fixed filter, and the one that repeats it (mirrors: the region of the parcels of a region)
        $list['columns'] = array_values(array_filter($list['columns'], fn (array $column) => ! array_key_exists((string) ($column['name'] ?? ''), $fixed)
            && ! array_key_exists((string) ($column['mirrors'] ?? ''), $fixed)));
        $list['filters'] = array_values(array_filter($list['filters'], fn (array $filter) => ! array_key_exists((string) ($filter['name'] ?? ''), $fixed)));
        $columns = array_map(fn (array $column, int $i) => self::column($column, $i === 0, (array) $list['sort'] + ['default' => '', 'descending' => '-'], $query), $list['columns'], array_keys($list['columns']));
        $names = array_column($list['filters'], 'name');
        $filters = array_map(fn (array $filter) => FilterView::of($filter, $query, isset($meta['counts'][$filter['name'] ?? '']) ? (array) $meta['counts'][$filter['name']] : null), $list['filters']);
        $total = (int) ($meta['total'] ?? count($items));

        return new self(
            $list,
            $columns,
            array_values(array_filter($columns, fn (ColumnView $column) => ! $column->primary)),
            array_map(fn (array $item) => self::row($list, $item, $links, $self, $level), $items),
            $filters,
            $total,
            (int) ($meta['page'] ?? 1),
            (int) ceil($total / max(1, (int) ($meta['perPage'] ?? 0) ?: 50)),
            count(array_filter($filters, fn (FilterView $filter) => $filter->active)),
            array_diff_key($query, array_flip([...$names, 'page'])),
            $query,
            self::views($filters, (array) ($list['views'] ?? []), $query, $total),
        );
    }

    /**
     * The views of a list, as w4os-wp shows them above its table: All, then each value of the filters the contract shows as views
     * (`views`, in their order) that gives rows or that the page asks, with the number of rows it gives; each the query of the page
     * with that value alone of these filters (the search and the other filters kept), the one the page asks current. All counts the
     * rows of every value of the filter asked (the engine counts a filter without its own value), the total when none is.
     *
     * @param list<FilterView> $filters
     * @param list<string> $names the filters shown as views, in their order
     * @param array<string,mixed> $query
     * @return list<array{filter: ?string, value: ?string, label: ?string, text: ?string, count: ?int, query: array<string,mixed>, current: bool}>
     */
    private static function views(array $filters, array $names, array $query, int $total): array
    {
        $byName = [];
        foreach ($filters as $filter) {
            $byName[$filter->name] = $filter;
        }
        $marked = array_values(array_filter(array_map(fn ($name) => $byName[$name] ?? null, $names)));
        if ($marked === []) {
            return [];
        }
        $base = array_diff_key($query, array_flip([...array_map(fn (FilterView $filter) => $filter->name, $marked), 'page']));
        $asked = array_values(array_filter($marked, fn (FilterView $filter) => $filter->active));
        $counts = $asked === [] ? [] : array_column($asked[0]->values, 'count');
        $all = $asked === [] ? $total : (in_array(null, $counts, true) ? null : array_sum($counts));
        $views = [['filter' => null, 'value' => null, 'label' => null, 'text' => null, 'count' => $all, 'query' => $base, 'current' => $asked === []]];
        foreach ($marked as $filter) {
            foreach ($filter->values as $value) {
                if ($value['count'] === 0 && ! $value['chosen']) {
                    continue;
                }
                $views[] = ['filter' => $filter->name, 'value' => $value['value'], 'label' => $value['label'], 'text' => $value['text'], 'count' => $value['count'],
                    'query' => $base + [$filter->name => $value['value']], 'current' => $value['chosen'] && count($asked) === 1];
            }
        }

        return $views;
    }

    /**
     * A column: at every width when it is the first or an indicators column (until the contract says it, `priority`), its sort
     * state from the query of the page (its `sort`, the default of the list without it).
     *
     * @param array<string,mixed> $column
     * @param array{default: string, descending: string} $sort
     * @param array<string,mixed> $query
     */
    private static function column(array $column, bool $first, array $sort, array $query): ColumnView
    {
        $type = (string) ($column['type'] ?? 'text');
        $descending = $sort['descending'] ?: '-';
        $current = (string) ($query['sort'] ?? $sort['default']);
        $down = str_starts_with($current, $descending);
        $key = isset($column['sort']) ? (string) $column['sort'] : null;
        $sorted = $key !== null && ($down ? substr($current, strlen($descending)) : $current) === $key;

        return new ColumnView(
            (string) $column['name'],
            (string) ($column['label'] ?? 'column_'.$column['name']),
            $type,
            isset($column['priority']) ? $column['priority'] === 'primary' : $first || $type === 'indicators',
            (bool) ($column['bare'] ?? in_array($type, self::BARE, true)),
            $key,
            $sorted,
            $sorted && $down,
            $key === null ? '' : ($sorted && ! $down ? $descending.$key : $key),
            $column,
        );
    }

    /**
     * A row: its link, its address, its indicators and the actions they hold, the others in its menu.
     *
     * @param array<string,mixed> $list
     * @param array<string,mixed> $item
     * @param list<array<string,mixed>> $links
     */
    private static function row(array $list, array $item, array $links, ?string $self, ?int $level): RowView
    {
        $id = Rows::id($item);
        $urls = [];
        foreach ($links as $link) {
            $url = isset($link['attribute']) ? (string) ($item[$link['attribute']] ?? '') : str_replace('{uuid}', rawurlencode($id), (string) ($link['url'] ?? ''));
            $url === '' || $urls[] = ['url' => $url, 'key' => (string) $link['key']];
        }
        $first = (array) ($list['columns'][0] ?? []);
        $subtitle = null;
        foreach ((array) ($first['subtitle'] ?? []) as $attribute) {
            $value = $item[$attribute] ?? null;
            if ($subtitle === null && $value !== null && $value !== '') {
                $subtitle = (string) $value;
            }
        }
        $allowed = array_column(Rows::actions($list, $item, $self, $level), null, 'name');
        $indicators = [];
        $iconic = [];
        foreach ($list['columns'] as $column) {
            if (($column['type'] ?? '') !== 'indicators') {
                continue;
            }
            $indicators[(string) $column['name']] = [];
            foreach ((array) ($column['indicators'] ?? []) as $indicator) {
                $iconic = [...$iconic, ...(array) ($indicator['actions'] ?? [])];
                $value = Rows::value($item, (string) $indicator['name']);
                $state = null;
                foreach ((array) ($indicator['states'] ?? []) as $known) {
                    $state ??= ($known['value'] ?? null) === $value ? $known : null;
                }
                if ($state === null) {
                    continue;
                }
                $action = null;
                foreach ((array) ($indicator['actions'] ?? []) as $name) {
                    $action ??= isset($allowed[$name]) ? ActionView::of($allowed[$name]) : null;
                }
                $indicators[(string) $column['name']][] = new IndicatorView((string) $indicator['name'], (string) ($state['icon'] ?? $indicator['icon'] ?? ''), (string) ($state['tone'] ?? 'off'), (string) ($state['label'] ?? ''), $action);
            }
        }
        $menu = array_map(fn (array $action) => ActionView::of($action), array_values(array_diff_key($allowed, array_flip($iconic))));
        usort($menu, fn (ActionView $a, ActionView $b) => $a->danger <=> $b->danger);
        $status = $item['status'] ?? null;

        return new RowView(
            $id,
            $item,
            $urls[0]['url'] ?? null,
            $urls,
            $subtitle,
            $subtitle === null || ! isset($first['subtitleLink']) ? null : (($item[$first['subtitleLink']] ?? null) ?: null),
            is_scalar($status) ? (string) $status : null,
            $indicators,
            $menu,
        );
    }
}
