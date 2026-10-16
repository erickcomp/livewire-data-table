<?php

namespace Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;

/**
 * Pest's browser server trims the body of streamed responses: a Content-Length header would then promise more than
 * the body has, and the browser would fail the request. The browser tests put this middleware on the export downloads.
 */
class WithoutContentLength
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $response->headers->remove('Content-Length');

        return $response;
    }
}
