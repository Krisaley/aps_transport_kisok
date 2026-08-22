<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PostcodeLookup
{
    /** @return array<string, mixed>|null */
    public function lookup(string $postcode): ?array
    {
        $response = Http::acceptJson()->timeout(8)->get('https://api.postcodes.io/postcodes/'.rawurlencode($postcode));

        if (! $response->successful()) {
            return null;
        }

        return $response->json('result');
    }
}
