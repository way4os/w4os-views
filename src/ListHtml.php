<?php

namespace W4OS\Views;

/**
 * The HTML of a list of the engine (ListView), of an item (ItemView) and of a list with the detail of the item it selects
 * (ListDetailView), the same for every site that shows them: semantic, classes only, no colours (its style: ListDetailView::ASSETS,
 * the site's theme for the rest). An admin keeps its own (the plugin's Tabler templates).
 *
 * Its texts are the keys of the engine's catalog, said by the bridge (`t`). Options:
 * - `t` (required): Closure(string $key, list<scalar> $vars = []): string, the text of a key of the catalog;
 * - `language`: the language of the page, for its dates and numbers (`en`);
 * - `empty`: the key of the text of a list without rows; null shows nothing (a search not asked yet);
 * - `previous`, `next`: the keys of the links to the pages before and after (web_search_previous, web_search_next);
 * - `level`: the level of the title of a detail (`h2`);
 * - `picture`: Closure(?string $picture, ?string $default, string $name): string, the HTML of a picture at the site's addresses;
 * - `actions`: Closure(ItemView $item): string, the HTML of the actions of a detail;
 * - `client`, `pattern`, `texts`: the teleport link of an item that has links (`tpLinks`), as TeleportLink::html() takes them;
 *   `go`: the key of its text (its name without it).
 */
final class ListHtml
{
    /** The width of a list from which a column of each priority shows (1: at every width): the classes list.css hides. */
    private const TIERS = [2 => 'md', 3 => 'xl'];

    /**
     * A list: its table, then its links to the page before and after. Its sorts and its pages keep the whole query of the page (the
     * criteria of a search too). With a ListDetailView, the link of a row selects it.
     *
     * @param array<string,mixed> $options
     */
    public static function table(ListView $view, array $options, ?ListDetailView $detail = null): string
    {
        $t = self::texts($options);
        if ($view->rows === []) {
            $empty = $options['empty'] ?? null;

            return $empty === null || $empty === '' ? '' : '<p class="w4os-list-empty">'.self::e($t((string) $empty)).'</p>';
        }
        $head = '';
        foreach ($view->columns as $column) {
            $label = self::e($t($column->label));
            $sort = $column->sorted ? ' aria-sort="'.($column->descending ? 'descending' : 'ascending').'"' : '';
            $content = $column->sort === null ? $label
                : '<a href="'.self::e(self::href(['sort' => $column->next] + array_diff_key($view->query, ['sort' => 1, 'page' => 1]))).'">'.$label
                    .($column->sorted ? '<span class="w4os-sort-mark" aria-hidden="true">'.($column->descending ? '▼' : '▲').'</span>' : '').'</a>';
            $head .= '<th scope="col" class="'.self::columnClass($column).'"'.$sort.'>'.$content.'</th>';
        }
        $body = '';
        foreach ($view->rows as $row) {
            $selected = $detail !== null && $detail->selected === $row->id;
            $cells = '';
            foreach ($view->columns as $i => $column) {
                $value = self::cell($column->definition, $row->item, $options);
                if ($i === 0) {
                    $link = $detail !== null ? self::href($detail->select[$row->id] ?? []).'#'.$detail->region : $row->link;
                    $more = $detail === null ? '' : ' data-w4os-select="'.self::e($row->id).'" aria-controls="'.self::e($detail->region).'"'.($selected ? ' aria-current="true"' : '');
                    $value = $link === null ? $value : '<a class="w4os-row-link" href="'.self::e($link).'"'.$more.'>'.$value.'</a>';
                    $row->subtitle === null || $value .= '<div class="w4os-subtitle">'.self::e($row->subtitle).'</div>';
                    $value .= self::folded($view, $row->item, $options);
                }
                $cells .= ($i === 0 ? '<th scope="row"' : '<td').' class="'.self::columnClass($column).'">'.$value.($i === 0 ? '</th>' : '</td>');
            }
            $classes = trim(($selected ? 'is-selected' : '').($row->status === null ? '' : ' w4os-'.$row->status));
            $body .= '<tr'.($classes === '' ? '' : ' class="'.self::e($classes).'"').'>'.$cells.'</tr>';
        }
        $type = (string) ($view->list['type'] ?? '');

        return '<div class="w4os-list-wrap"><table class="w4os-list'.($type === '' ? '' : ' w4os-list-'.self::e($type)).'"><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table></div>'
            .self::pages($view, $options);
    }

    /**
     * The links to the page before and after, the query of the page kept; none on a list of one page.
     *
     * @param array<string,mixed> $options
     */
    public static function pages(ListView $view, array $options): string
    {
        if ($view->pages < 2) {
            return '';
        }
        $t = self::texts($options);
        $link = fn (int $page, string $rel, string $key) => '<a class="w4os-page-'.$rel.'" rel="'.$rel.'" href="'.self::e(self::href(['page' => $page] + $view->query)).'">'.self::e($t($key)).'</a>';

        return '<nav class="w4os-pages">'
            .($view->page > 1 ? $link($view->page - 1, 'prev', (string) ($options['previous'] ?? 'web_search_previous')) : '<span class="w4os-page-prev"></span>')
            .($view->page < $view->pages ? $link($view->page + 1, 'next', (string) ($options['next'] ?? 'web_search_next')) : '<span class="w4os-page-next"></span>')
            .'</nav>';
    }

    /**
     * An item: its picture and its name, its teleport link when it has links (`tpLinks`: on the value of the field that names the
     * place, `teleport`, else a line of its own), its groups of fields, its actions.
     *
     * @param array<string,mixed> $options
     */
    public static function item(ItemView $item, array $options, string $region = 'w4os-detail'): string
    {
        $t = self::texts($options);
        $level = in_array($options['level'] ?? 'h2', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? (string) ($options['level'] ?? 'h2') : 'h2';
        $sub = 'h'.min(6, (int) substr($level, 1) + 1);
        $picture = $item->pictured && isset($options['picture']) && $options['picture'] instanceof \Closure
            ? (string) ($options['picture'])($item->picture, $item->pictureDefault, $item->name) : '';
        // A person in the header beside the name; a place in full under it, as the search of the viewers shows it
        $person = $item->pictureDefault === 'person';
        $html = '<header class="w4os-detail-header">'.($person ? $picture : '').'<'.$level.' id="'.self::e($region).'-title" class="w4os-detail-title" tabindex="-1">'.self::e($item->name).'</'.$level.'></header>'
            .($person || $picture === '' ? '' : '<div class="w4os-detail-picture">'.$picture.'</div>');
        $links = $item->item['tpLinks'] ?? null;
        $links = is_array($links) && $links !== [] ? $links : null;
        $teleport = fn (string $label): string => TeleportLink::html((array) $links, $label, (string) ($options['client'] ?? 'browser'), (array) ($options['texts'] ?? []), (string) ($options['pattern'] ?? ''));
        // Its teleport link on the field that names the place (`teleport`), else a line of its own under its name
        $placed = null;
        foreach ($item->groups as $group) {
            foreach ($group['fields'] as $field) {
                $placed ??= !empty($field['teleport']) && trim((string) Rows::value($item->item, (string) ($field['name'] ?? ''))) !== '' ? (string) $field['name'] : null;
            }
        }
        if ($links !== null && $placed === null) {
            $label = isset($options['go']) && $options['go'] !== '' ? $t((string) $options['go']) : $item->name;
            $html .= '<p class="w4os-detail-go">'.$teleport($label).'</p>';
        }
        foreach ($item->groups as $group) {
            $fields = '';
            // A text that reads without its label (its description): a text of its own after the fields
            $texts = '';
            foreach ($group['fields'] as $field) {
                if (!empty($field['bare']) && ($field['type'] ?? 'text') === 'text') {
                    $text = self::cell($field, $item->item, $options);
                    $text === '' || $texts .= '<p class="w4os-detail-text">'.$text.'</p>';
                    continue;
                }
                $value = $links !== null && ($field['name'] ?? null) === $placed
                    ? $teleport((string) Rows::value($item->item, (string) $field['name']))
                    : self::cell($field, $item->item, $options + ['dates' => 'long']);
                $value === '' || $fields .= '<div class="w4os-field w4os-field-'.self::e((string) ($field['type'] ?? 'text')).'"><dt>'.self::e($t((string) ($field['label'] ?? ''))).'</dt><dd>'.$value.'</dd></div>';
            }
            if ($fields !== '' || $texts !== '') {
                $html .= (count($item->groups) > 1 && $group['label'] !== '' ? '<'.$sub.' class="w4os-detail-group">'.self::e($t($group['label'])).'</'.$sub.'>' : '')
                    .($fields === '' ? '' : '<dl class="w4os-fields">'.$fields.'</dl>').$texts;
            }
        }
        if (isset($options['actions']) && $options['actions'] instanceof \Closure) {
            $actions = (string) ($options['actions'])($item);
            $actions === '' || $html .= '<div class="w4os-detail-actions">'.$actions.'</div>';
        }

        return $html;
    }

    /**
     * A list and the detail of the item it selects, beside it on a wide screen, below it otherwise; the element of the detail there
     * even when none is selected (hidden), for the script to fill.
     *
     * @param array<string,mixed> $options
     */
    public static function listDetail(ListDetailView $view, array $options): string
    {
        $region = self::e($view->region);
        $detail = $view->detail === null
            ? '<section id="'.$region.'" class="w4os-detail" hidden></section>'
            : '<section id="'.$region.'" class="w4os-detail" aria-labelledby="'.$region.'-title">'.self::item($view->detail, $options, $view->region).'</section>';

        return '<div class="w4os-list-detail" data-w4os-list-detail data-region="'.$region.'" data-param="'.self::e($view->param).'">'
            // The rows without their pictures when the page asks it (a search: the name and the time, the picture in the detail)
            .'<div class="w4os-ld'.($view->detail === null ? '' : ' has-detail').'"><div class="w4os-ld-list">'.self::table($view->list, ($options['rowPictures'] ?? true) === false ? array_diff_key($options, ['picture' => 1]) : $options, $view).'</div>'.$detail.'</div></div>';
    }

    /**
     * A value of a row or of an item, as its column or its field says: its labels (`values`), a count, a number (`decimals`, `unit`), a
     * duration (minutes), a text with its line breaks, a date,
     * a link (`link`: the attribute that gives its address), a small table of records (`table`: its `columns`), a mark; else its text.
     *
     * @param array<string,mixed> $column
     * @param array<string,mixed> $item
     * @param array<string,mixed> $options
     */
    public static function cell(array $column, array $item, array $options): string
    {
        $t = self::texts($options);
        $language = (string) ($options['language'] ?? 'en');
        $type = (string) ($column['type'] ?? 'text');
        $value = Rows::value($item, (string) ($column['name'] ?? ''));
        $html = match (true) {
            $value === null || $value === '' || $value === [] => '',
            $type === 'table' => self::records((array) $value, (array) ($column['columns'] ?? []), $options),
            isset($column['count']) && is_numeric($value) => self::e($t((string) $column['count'], [self::number($value, $language, 0)])),
            // With its unit when it has one (60 min, 1,024 m²), not cut from it
            $type === 'number' && is_numeric($value) => self::e(self::number($value, $language, (int) ($column['decimals'] ?? 0)).(isset($column['unit']) && $column['unit'] !== '' ? "\u{00A0}".$column['unit'] : '')),
            $type === 'duration' && is_numeric($value) => self::e(self::duration((int) $value, $t)),
            // A text of several lines keeps them (a description: its addresses, its paragraphs)
            $type === 'text' && is_string($value) && !isset($column['values']) => nl2br(self::e($value)),
            in_array($type, ['date', 'datetime'], true) => '<time datetime="'.self::e(self::iso($value)).'" title="'.self::e(Dates::format(is_scalar($value) ? $value : null, $language, 'full')).'">'
                .self::e(Dates::format(is_scalar($value) ? $value : null, $language, $type === 'date' ? 'date' : (string) ($options['dates'] ?? 'list'))).'</time>',
            $type === 'boolean' => $value ? '✓' : '',
            default => self::labeled($column, $value, $t),
        };
        if ($html !== '' && $type === 'link' && isset($column['link']) && is_string($url = $item[$column['link']] ?? null) && $url !== '') {
            $html = '<a href="'.self::e($url).'">'.$html.'</a>';
        }
        if (isset($column['picture'], $options['picture']) && $options['picture'] instanceof \Closure) {
            $picture = $item[$column['picture']] ?? null;
            $html = '<span class="w4os-who">'.($options['picture'])(is_scalar($picture) && $picture !== '' ? (string) $picture : null, isset($column['default']) ? (string) $column['default'] : null, (string) ($item['name'] ?? '')).'<span>'.$html.'</span></span>';
        }

        return $html;
    }

    /**
     * A small table of records in a field (the members of a group): its columns as a list's, a link of its first column too.
     *
     * @param list<mixed> $records
     * @param list<array<string,mixed>> $columns
     * @param array<string,mixed> $options
     */
    private static function records(array $records, array $columns, array $options): string
    {
        $t = self::texts($options);
        $records = array_values(array_filter($records, 'is_array'));
        if ($records === [] || $columns === []) {
            return '';
        }
        $head = implode('', array_map(fn (array $column) => '<th scope="col" class="w4os-col-'.self::e((string) ($column['type'] ?? 'text')).'">'.self::e($t((string) ($column['label'] ?? ''))).'</th>', $columns));
        $rows = '';
        foreach ($records as $record) {
            $cells = '';
            foreach ($columns as $column) {
                $cells .= '<td class="w4os-col-'.self::e((string) ($column['type'] ?? 'text')).'">'.self::cell($column, $record, $options).'</td>';
            }
            $rows .= '<tr>'.$cells.'</tr>';
        }

        return '<table class="w4os-records"><thead><tr>'.$head.'</tr></thead><tbody>'.$rows.'</tbody></table>';
    }

    /** A value or a list of them, each with its label when the column names it (`values`), separated by commas. */
    private static function labeled(array $column, mixed $value, \Closure $t): string
    {
        $labels = [];
        foreach ((array) ($column['values'] ?? []) as $known) {
            is_array($known) && isset($known['value'], $known['label']) && $labels[(string) $known['value']] = (string) $known['label'];
        }
        $texts = [];
        foreach (is_array($value) ? $value : [$value] as $one) {
            is_scalar($one) && $texts[] = isset($labels[(string) $one]) ? $t($labels[(string) $one]) : (string) $one;
        }

        return self::e(implode(', ', $texts));
    }

    /**
     * The values of the columns a narrow list hides, in a line under the name, each until its column shows (the ones shown first
     * first); a value that does not say what it is after the label of its column.
     *
     * @param array<string,mixed> $item
     * @param array<string,mixed> $options
     */
    private static function folded(ListView $view, array $item, array $options): string
    {
        $t = self::texts($options);
        $parts = '';
        foreach ($view->secondary as $column) {
            $value = strip_tags(self::cell($column->definition, $item, $options));
            if (trim($value) !== '') {
                $parts .= '<span class="w4os-fold-'.(self::TIERS[$column->priority] ?? 'xxl').'">'.($column->bare ? $value : self::e($t($column->label)).': '.$value).'</span>';
            }
        }

        return $parts === '' ? '' : '<div class="w4os-subtitle w4os-folded">'.$parts.'</div>';
    }

    /** The classes of a column: its type, and the width from which it shows. */
    private static function columnClass(ColumnView $column): string
    {
        return 'w4os-col-'.self::e($column->type).($column->primary ? '' : ' w4os-tier-'.(self::TIERS[$column->priority] ?? 'xxl'));
    }

    /** A duration in minutes, in the units of the catalog: minutes under an hour (45 min), hours from 60 (1 h, 1 h 30). */
    private static function duration(int $minutes, \Closure $t): string
    {
        if ($minutes < 60) {
            return $t('unit_minutes', [(string) $minutes]);
        }
        $rest = $minutes % 60;

        return $rest === 0 ? $t('unit_hours', [(string) intdiv($minutes, 60)]) : $t('unit_hours_minutes', [(string) intdiv($minutes, 60), sprintf('%02d', $rest)]);
    }

    /** A number in the language of the page, with its decimals. */
    private static function number(mixed $value, string $language, int $decimals): string
    {
        $format = new \NumberFormatter(str_replace('-', '_', $language), \NumberFormatter::DECIMAL);
        $format->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, max(0, $decimals));
        $format->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, max(0, $decimals));

        return (string) $format->format((float) $value);
    }

    /** A date as a machine reads it (the attribute of a time). */
    private static function iso(mixed $value): string
    {
        try {
            return is_numeric($value) ? gmdate('c', (int) $value) : (new \DateTimeImmutable((string) $value))->format('c');
        } catch (\Exception) {
            return '';
        }
    }

    /** The address of the page with a query. @param array<string,mixed> $query */
    private static function href(array $query): string
    {
        return '?'.http_build_query($query);
    }

    /** @param array<string,mixed> $options */
    private static function texts(array $options): \Closure
    {
        $t = $options['t'] ?? null;

        return $t instanceof \Closure ? $t : fn (string $key, array $vars = []) => $key;
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
