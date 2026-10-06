<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks the stateless public pages as cacheable by browsers and by a CDN or
 * nginx fastcgi_cache, so anonymous traffic rarely reaches PHP. These pages
 * must never carry per-visitor output or cookies.
 */
class PublicCacheHeaders
{
    public const HEADER = 'public, max-age=60, s-maxage=600, stale-while-revalidate=86400';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethodCacheable() && $response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', self::HEADER);

            foreach ($response->headers->getCookies() as $cookie) {
                $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }

        return $response;
    }
}
