<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    public function fetch(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response('No URL specified.', 400);
        }

        // Fetch remote URL using Laravel HTTP client
        $response = Http::withOptions(['allow_redirects' => true])
                        ->get($url);

        // Check content type
        $validContentTypes = [
            'application/octet-stream', 
            'application/pdf', 
            'image/jpeg', 
            'image/png', 
            'application/zip', 
            'text/plain'
        ];

        $contentType = $response->header('Content-Type');

        if (in_array($contentType, $validContentTypes)) {
            return response($response->body(), 200)
                        ->header('Content-Type', $contentType);
        }

        return response('', 204);
    }
}
