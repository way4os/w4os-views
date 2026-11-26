<?php

namespace W4OS\Views;

/**
 * A picture of the grid, the one way every page shows it: the picture when there is one; for a person without one, the neutral
 * silhouette the engine gives (WebAssets::PERSON), at half its opacity (`w4os-picture-none`); for a place without one, nothing.
 * A picture that cannot be loaded becomes the silhouette (a person) or goes away (a place). Never a name or its initials.
 *
 *     Picture::tag($src, $silhouette, 'w4os-thumb', 32);   // a person: its picture, else the silhouette
 *     Picture::tag($src, null, 'place-picture');           // a place: its picture, else nothing
 */
final class Picture
{
    /** The class of a silhouette standing for a picture that is not there. */
    public const NONE = 'w4os-picture-none';

    /** The opacity of the silhouette, given on the picture itself: whatever styles the page has, it stays faint. */
    public const NONE_OPACITY = '0.5';

    /**
     * @param ?string $src the address of the picture, null when there is none
     * @param ?string $none the address of the silhouette of a person, null for what is not a person (a place, a group)
     * @param string $class the classes of its box (its size and its shape, the page's)
     * @param ?int $size its width and its height in pixels, none for the ones its class gives
     */
    public static function tag(?string $src, ?string $none = null, string $class = '', ?int $size = null): string
    {
        $src = $src === null || $src === '' ? null : $src;
        $none = $none === null || $none === '' ? null : $none;
        if ($src === null && $none === null) {
            return '';
        }
        $e = fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $faint = $src === null;
        $failed = $none === null
            ? 'this.parentNode.hidden=true'
            : "this.onerror=null;this.src='".$e(addslashes($none))."';this.style.opacity='".self::NONE_OPACITY."';this.parentNode.classList.add('".self::NONE."')";
        $box = $size === null ? '' : ' style="width: '.$size.'px; height: '.$size.'px"';
        $dimensions = $size === null ? '' : ' width="'.$size.'" height="'.$size.'"';

        return '<span class="'.$e(trim('w4os-picture '.$class.($faint ? ' '.self::NONE : ''))).'"'.$box.'>'
            .'<img src="'.$e($src ?? $none).'" alt=""'.$dimensions.' loading="lazy"'.($faint ? ' style="opacity: '.self::NONE_OPACITY.'"' : '')
            .($faint ? '' : ' onerror="'.$failed.'"').'></span>';
    }
}
