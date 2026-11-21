<?php

use W4OS\Views\Link;

/** What builds the addresses of a site from a /fr page, as the engine's Url does. */
function link_urls(): object
{
    return new class {
        public function build(string $target, array $args = []): string
        {
            $path = str_starts_with($target, '/fr/') ? $target : '/fr'.$target;

            return $path.($args === [] ? '' : '?'.http_build_query($args));
        }
    };
}

describe('a link of a site', function () {
    test('has its address built for the page shown, the class of its kind, its text and its address escaped', function () {
        expect(Link::to(link_urls(), '/news', 'All news', 'more'))->toBe('<a class="w4os-more" href="/fr/news">All news</a>')
            ->and(Link::to(link_urls(), '/fr/news', 'Toutes les actualités', 'more'))->toBe('<a class="w4os-more" href="/fr/news">Toutes les actualités</a>')
            ->and(Link::to(link_urls(), '/search', 'Search', 'plain', ['q' => 'a&b']))->toBe('<a href="/fr/search?q=a%26b">Search</a>')
            ->and(Link::html('/x?a=1&b=2', '<b>Tom & "Jerry"</b>'))->toBe('<a href="/x?a=1&amp;b=2">&lt;b&gt;Tom &amp; &quot;Jerry&quot;&lt;/b&gt;</a>');
    });

    test('has the class of each kind, then its own; a kind it does not know is a plain link', function () {
        expect(Link::html('/register', 'Create your avatar', 'primary', 'w4os-start-button'))->toBe('<a class="btn btn-primary w4os-start-button" href="/register">Create your avatar</a>')
            ->and(Link::html('/login', 'Log in', 'button'))->toBe('<a class="btn btn-ghost" href="/login">Log in</a>')
            ->and(Link::html('/x', 'X', 'nope'))->toBe('<a href="/x">X</a>');
    });
});
