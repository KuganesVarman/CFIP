<?php

namespace App\Console\Commands;

use App\Console\Concerns\FetchesIspringToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Group;


class SyncGroups extends Command
{
    use FetchesIspringToken;

    protected $signature = 'group:sync';
    protected $description = 'Sync groups from iSpring API';

    public function handle()
{
    $apiUrl = config('services.cfip.base_url') . '/groups';
    $token = $this->fetchToken();
    if (!$token) {
        $this->error('Failed to get token!');
        return;
    }

    $response = Http::timeout(60)->withToken($token)->get($apiUrl);
    $body = $response->body();
    // Remove XML declaration
    $body = preg_replace('/<\?xml.*?\?>/', '', $body);
    $xml = simplexml_load_string($body);

    if ($xml && isset($xml->groups->group)) {
        foreach ($xml->groups->group as $item) {
            $data = json_decode(json_encode($item), true);
            \App\Models\Group::updateOrCreate(
                ['group_id' => $data['groupId'] ?? null],
                [
                    'name' => $data['name'] ?? '',
                    'is_smart' => ($data['isSmart'] == '1'), // xml gives '0' or '1', so cast to boolean
                ]
            );
        }
        $this->info('Groups synced successfully.');
    } else {
        $this->error('No groups found in API response.');
        dump($body); // For further debugging
    }
}

}

