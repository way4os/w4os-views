<?php

namespace W4OS\Views;

/**
 * A list with the detail of the item it selects beside it (below on a narrow screen): the list as ListView shows it, the item
 * selected by a parameter of the page (`?id=`, kept with the rest of its query) as ItemView shows it, even when it is not on the
 * page of the list shown, and for each row the query that selects it.
 *
 * Without a script, a row's link loads the page with its detail (and a narrow screen jumps to it, the region's anchor); with the
 * script of ASSETS, the detail is fetched and swapped in place. ListHtml::listDetail() writes it.
 */
final readonly class ListDetailView
{
    /** Its style and its script, in assets/ of this package (the teleport links of a detail need TeleportLink::ASSETS too). */
    public const ASSETS = ['css' => ['css/list.css'], 'js' => ['js/list-detail.js']];

    /**
     * @param array<string,array<string,mixed>> $select by row id, the query of the page that selects it
     */
    public function __construct(
        public ListView $list,
        public ?ItemView $detail,
        public ?string $selected,
        public string $param,
        public string $region,
        public array $select,
    ) {}

    /**
     * The view of a list and of the item the page selects.
     *
     * @param ?array<string,mixed> $item the item selected as the engine gives it (asked by its id), null when none is or it is not found
     * @param list<array{label: string, fields: list<array<string,mixed>>}> $groups its groups of fields (ItemView::of())
     * @param list<array<string,mixed>> $actions its actions (ItemView::of())
     * @param string $param the parameter of the page that selects an item
     * @param string $region the id of the element of the detail (the anchor of the rows' links, their aria-controls)
     */
    public static function of(ListView $list, ?array $item, array $groups = [], array $actions = [], string $param = 'id', string $region = 'w4os-detail'): self
    {
        $detail = $item === null || $item === [] ? null : ItemView::of($list->list, $item, $groups, $actions);
        $selected = $detail?->id ?? (isset($list->query[$param]) && is_scalar($list->query[$param]) && $list->query[$param] !== '' ? (string) $list->query[$param] : null);
        $select = [];
        foreach ($list->rows as $row) {
            $select[$row->id] = $list->query;
            $select[$row->id][$param] = $row->id;
        }

        return new self($list, $detail, $selected, $param, $region, $select);
    }
}
