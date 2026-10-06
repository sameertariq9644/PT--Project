<?php

namespace App\Support;

class VideoEmbed
{
    /**
     * Turn a YouTube or Vimeo page URL into its embeddable player URL.
     * Returns null for anything else, so no arbitrary site is ever framed.
     */
    public static function url(?string $url): ?string
    {
        $url = trim((string) $url);

        if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~', $url, $match)) {
            return 'https://www.youtube-nocookie.com/embed/'.$match[1].'?autoplay=1&rel=0';
        }

        if (preg_match('~^https?://(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d+)~', $url, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1].'?autoplay=1';
        }

        return null;
    }
}
