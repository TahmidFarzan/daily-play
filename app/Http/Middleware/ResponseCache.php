<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseCache
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $seconds = null,
        string $visibility = 'public',
        ?string $staleWhileRevalidate = null,
        ?string $etag = null
    ): Response {

        $forceRefresh = $request->cookie('fresh_response') === '1';

        $response = $next($request);

        if ($forceRefresh) {

            $this->disableCache($response);

            return $response->withoutCookie(
                'fresh_response',
                '/',
                null
            );
        }

        if (! $this->shouldCache($request, $response)) {
            return $response;
        }

        $seconds              = $this->seconds($seconds);
        $visibility           = $this->visibility($visibility, $request);
        $staleWhileRevalidate = $this->seconds($staleWhileRevalidate, 300);

        $directives = [
            $visibility,
            "max-age={$seconds}",
            "stale-while-revalidate={$staleWhileRevalidate}",
        ];

        $response->headers->set(
            'Cache-Control',
            implode(', ', $directives)
        );

        if ($etag === 'etag') {
            $content = $response->getContent();

            if (is_string($content) && $content !== '') {
                $response->setEtag(md5($content));
                $response->isNotModified($request);
            }
        }

        return $response;
    }

    private function disableCache(Response $response): void
    {
        $response->headers->set(
            'Cache-Control',
            'no-store, no-cache, must-revalidate, max-age=0'
        );

        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        $response->headers->remove('ETag');
        $response->headers->remove('Last-Modified');
    }

    private function shouldCache(Request $request, Response $response): bool
    {
        if (! $request->isMethodCacheable()) {
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        if ($response->headers->has('Set-Cookie')) {
            return false;
        }

        return true;
    }

    private function seconds(?string $seconds, int $default = 120): int
    {
        if ($seconds === null || $seconds === '') {
            return $default;
        }

        $seconds = (int) $seconds;

        return $seconds > 0 ? $seconds : $default;
    }

    private function visibility(string $visibility, Request $request): string
    {
        if (! in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'public';
        }

        if ($request->user() && $visibility === 'public') {
            return 'private';
        }

        return $visibility;
    }
}
