<?php

namespace W4OS\Views;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * A date in the language of the page: relative while it is recent, as Carbon tells it in each language (`il y a 15 heures`,
 * `15 hours ago`, `vor 3 Tagen`), else by PHP's Intl formatter (ISO without it).
 */
final class Dates
{
    /** How long a time reads relatively in a list ("15 hours ago"), in seconds; an older one is a short date with its time. */
    public const RECENT = 7 * 86400;

    /**
     * A date in a style: `date` the medium date (`16 nov. 2026`), `long` with its time (`16 nov. 2026, 10:00`), `full` the long
     * date with its time (the tip of a date), `list` relative while it is recent, else the short date with its time
     * (`16/11/2026 10:00`).
     *
     * @param int|string|null $value a timestamp or a date PHP reads
     * @param string $language the language of the page (`fr`, `pt-BR`)
     * @param ?int $now the time it is (a timestamp)
     */
    public static function format(int|string|null $value, string $language, string $style = 'date', ?int $now = null): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }
        try {
            $date = is_numeric($value) ? (new \DateTimeImmutable)->setTimestamp((int) $value) : new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            return (string) $value;
        }
        $locale = str_replace('-', '_', $language);
        $now ??= time();
        if ($style === 'list' && abs($now - $date->getTimestamp()) < self::RECENT) {
            return Carbon::createFromTimestamp($date->getTimestamp())->locale($locale)
                ->diffForHumans(Carbon::createFromTimestamp($now), CarbonInterface::DIFF_RELATIVE_TO_NOW);
        }
        $date = $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        if (! class_exists(\IntlDateFormatter::class)) {
            return $date->format($style === 'date' ? 'Y-m-d' : 'Y-m-d H:i');
        }
        [$day, $time] = match ($style) {
            'list' => [\IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT],
            'long' => [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT],
            'full' => [\IntlDateFormatter::LONG, \IntlDateFormatter::SHORT],
            default => [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE],
        };

        return (string) (new \IntlDateFormatter($locale, $day, $time))->format($date);
    }
}
