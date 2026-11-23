<?php

use W4OS\Views\Dates;

describe('a date in the language of the page', function () {
    $time = 1794787200;

    test('relative while it is recent, in the right order for each language', function () use ($time) {
        $later = $time + 15 * 3600;

        expect(Dates::format($time, 'fr', 'list', $later))->toBe('il y a 15 heures')
            ->and(Dates::format($time, 'en', 'list', $later))->toBe('15 hours ago')
            ->and(Dates::format($time, 'pt-BR', 'list', $later))->toBe('há 15 horas')
            ->and(Dates::format($time, 'de', 'list', $time + 3 * 86400))->toBe('vor 3 Tagen');
    });

    test('a short date with its time once older than a week', function () use ($time) {
        expect(Dates::format($time, 'en', 'list', $time + 8 * 86400))->toMatch('~^11/16/26, \d{1,2}:\d{2}\s?[AP]M$~u')
            ->and(Dates::RECENT)->toBe(7 * 86400);
    });

    test('a date, never relative, where the host carries translation contracts Carbon cannot load beside (Grav 1.7)', function () use ($time) {
        // The interface of symfony/contracts 1, declared first as Grav 1.7's autoloader gives it, in a PHP of its own
        $script = 'namespace Symfony\\Contracts\\Translation; interface TranslatorInterface { public function trans($id, array $parameters = [], $domain = null, $locale = null); }'
            .' require '.var_export(dirname(__DIR__, 2).'/vendor/autoload.php', true).';'
            .' echo \\W4OS\\Views\\Dates::canTellRelative() ? "relative" : "date", "|", \\W4OS\\Views\\Dates::format('.$time.', "fr", "list", '.($time + 15 * 3600).');';
        exec(escapeshellarg(PHP_BINARY).' -r '.escapeshellarg($script).' 2>&1', $output);

        expect(Dates::canTellRelative())->toBeTrue()
            ->and(implode("\n", $output))->toStartWith('date|')->not->toContain('il y a')->toMatch('~^date\|\d{2}/\d{2}/\d{4} \d{2}:\d{2}$~');
    });

    test('a medium date, with its time or its long form', function () use ($time) {
        expect(Dates::format($time, 'en'))->toBe('Nov 16, 2026')
            ->and(Dates::format($time, 'fr', 'long'))->toMatch('~^16 nov\. 2026, \d{2}:\d{2}$~')
            ->and(Dates::format($time, 'fr', 'full'))->toMatch('~^16 novembre 2026 (à )?\d{2}:\d{2}$~u');
    });

    test('a month and its year, in the form of each language', function () use ($time) {
        expect(Dates::format($time, 'en', 'month'))->toBe('November 2026')
            ->and(Dates::format($time, 'fr', 'month'))->toBe('novembre 2026')
            ->and(Dates::format($time, 'de', 'month'))->toBe('November 2026')
            ->and(Dates::format($time, 'pt-BR', 'month'))->toBe('novembro de 2026')
            ->and(Dates::format($time, 'cy', 'month'))->toBe('Tachwedd 2026');
    });

    test('a date PHP reads, nothing for none, the value itself when it is no date', function () {
        expect(Dates::format('2026-11-16T10:00:00Z', 'en'))->toBe('Nov 16, 2026')
            ->and(Dates::format(null, 'fr'))->toBe('')
            ->and(Dates::format(0, 'fr'))->toBe('')
            ->and(Dates::format('soon', 'fr'))->toBe('soon');
    });
});
