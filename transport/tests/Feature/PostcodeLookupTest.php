<?php

namespace Tests\Feature;

use App\Services\PostcodeLookup;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostcodeLookupTest extends TestCase
{
    public function test_it_returns_normalised_postcode_data(): void
    {
        Http::fake(['api.postcodes.io/postcodes/*' => Http::response(['status' => 200, 'result' => ['postcode' => 'PE28 5YQ', 'admin_district' => 'Huntingdonshire']], 200)]);

        $result = app(PostcodeLookup::class)->lookup('pe285yq');

        $this->assertSame('PE28 5YQ', $result['postcode']);
        $this->assertSame('Huntingdonshire', $result['admin_district']);
    }

    public function test_it_returns_null_for_an_unknown_postcode(): void
    {
        Http::fake(['api.postcodes.io/postcodes/*' => Http::response(['status' => 404], 404)]);
        $this->assertNull(app(PostcodeLookup::class)->lookup('invalid'));
    }
}
