<?php

namespace App\Console\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

trait FetchesIspringToken
{
    protected function fetchToken(): ?string
    {
        return Cache::remember('ispring_token', 55, function () {
            $resp = Http::asForm()->post(config('services.cfip.token_url'), [
                'grant_type'    => 'client_credentials',
                'client_id'     => config('services.cfip.client_id'),
                'client_secret' => config('services.cfip.client_secret'),
            ]);
            return $resp->json('access_token');
        });
    }
}
