<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class DebugController extends ClientApiController
{
    /**
     * Debug endpoint to check user resources and database state.
     */
    public function index(ClientApiRequest $request): JsonResponse
    {
        $user = $request->user();
        
        return new JsonResponse([
            'user_id' => $user->id,
            'username' => $user->username,
            'coins' => $user->coins,
            'bought_cpu' => $user->bought_cpu,
            'bought_memory' => $user->bought_memory,
            'bought_disk' => $user->bought_disk,
            'bought_slots' => $user->bought_slots,
            'bought_databases' => $user->bought_databases,
            'bought_backups' => $user->bought_backups,
            'available_resources' => $user->availableResources(),
            'allocated_resources' => $user->allocatedResources(),
            'servers_count' => $user->servers()->count(),
        ]);
    }
}
