<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Http;

class AddressSearch
{
    /** @return array<int, array{id:string,label:string}> */
    public function suggestions(string $query, GeneralSettings $settings): array
    {
        if ($settings->postcode_validation_provider !== 'google' || blank($settings->google_maps_api_key)) {
            return [];
        }
        $response = Http::acceptJson()->withHeaders(['X-Goog-Api-Key' => $settings->google_maps_api_key, 'X-Goog-FieldMask' => 'suggestions.placePrediction.placeId,suggestions.placePrediction.text.text'])
            ->post('https://places.googleapis.com/v1/places:autocomplete', ['input' => $query, 'includedRegionCodes' => [strtolower($settings->google_address_country)]]);
        if (! $response->successful()) {
            return [];
        }

        $suggestions = $response->json('suggestions');
        if (! is_array($suggestions)) {
            return [];
        }

        $results = [];
        foreach ($suggestions as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }
            $id = data_get($suggestion, 'placePrediction.placeId');
            $label = data_get($suggestion, 'placePrediction.text.text');
            if (is_string($id) && $id !== '' && is_string($label) && $label !== '') {
                $results[] = ['id' => $id, 'label' => $label];
            }
        }

        return $results;
    }

    /** @return array<string, string|null>|null */
    public function details(string $placeId, GeneralSettings $settings): ?array
    {
        if (blank($settings->google_maps_api_key)) {
            return null;
        }
        $response = Http::acceptJson()->withHeaders(['X-Goog-Api-Key' => $settings->google_maps_api_key, 'X-Goog-FieldMask' => 'id,displayName,formattedAddress,addressComponents'])
            ->get('https://places.googleapis.com/v1/places/'.rawurlencode($placeId));
        if (! $response->successful()) {
            return null;
        }
        $parts = [];
        foreach ($response->json('addressComponents', []) as $part) {
            foreach ($part['types'] ?? [] as $type) {
                $parts[$type] = $part['longText'] ?? null;
            }
        }
        $line = trim(($parts['street_number'] ?? '').' '.($parts['route'] ?? ''));

        return ['name' => $response->json('displayName.text') ?: $line, 'address_line_1' => $line, 'address_line_2' => null, 'town' => $parts['postal_town'] ?? $parts['locality'] ?? null, 'county' => $parts['administrative_area_level_2'] ?? null, 'postcode' => $parts['postal_code'] ?? '', 'google_place_id' => $placeId];
    }
}
