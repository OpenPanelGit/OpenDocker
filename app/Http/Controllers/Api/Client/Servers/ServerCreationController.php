<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Illuminate\Http\Request;
use Pterodactyl\Services\Servers\ServerCreationService;

class ServerCreationController extends ClientApiController
{
    private ServerCreationService $creationService;

    public function __construct(ServerCreationService $creationService)
    {
        parent::__construct();
        $this->creationService = $creationService;
    }

    /**
     * Create a new server for the authenticated user using their bought resources.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'nest_id' => 'required|integer|exists:nests,id',
            'egg_id' => 'required|integer|exists:eggs,id',
            'memory' => 'required|integer|min:128',
            'cpu' => 'required|integer|min:10',
            'disk' => 'required|integer|min:128',
            'databases' => 'required|integer|min:0',
            'backups' => 'required|integer|min:0',
        ]);

        $user = $request->user();
        $settings = app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class);

        if ($user->bought_slots <= 0) {
            return new JsonResponse(['error' => 'Vous n\'avez plus de slots de serveur disponibles.'], 400);
        }

        // 1. Check against User's available total resources
        $available = $user->availableResources();
        $requested = [
            'memory' => (int) $request->input('memory'),
            'cpu' => (int) $request->input('cpu'),
            'disk' => (int) $request->input('disk'),
            'databases' => (int) $request->input('databases'),
            'backups' => (int) $request->input('backups'),
        ];

        foreach ($requested as $key => $value) {
            if ($value > $available[$key]) {
                return new JsonResponse(['error' => "Vous n'avez pas assez de ressources ({$key}) disponibles. Disponible: {$available[$key]}"], 400);
            }
        }

        // 2. Check against Admin's global server limits
        $limits = [
            'memory' => (int) $settings->get('store:limit_memory', 4096),
            'cpu' => (int) $settings->get('store:limit_cpu', 100),
            'disk' => (int) $settings->get('store:limit_disk', 10240),
            'databases' => (int) $settings->get('store:limit_databases', 5),
            'backups' => (int) $settings->get('store:limit_backups', 5),
        ];

        foreach ($requested as $key => $value) {
            if ($value > $limits[$key]) {
                return new JsonResponse(['error' => "Cette configuration dépasse la limite autorisée par l'administration pour un serveur ({$key}: {$limits[$key]})"], 400);
            }
        }

        // Find a suitable node automatically (simplified: just pick first active node)
        $node = Node::where('public', true)->first();
        if (!$node) {
            return new JsonResponse(['error' => 'Aucun nœud disponible pour le déploiement.'], 400);
        }

        // Find an available allocation on that node
        $allocation = Allocation::where('node_id', $node->id)->whereNull('server_id')->first();
        if (!$allocation) {
            return new JsonResponse(['error' => 'Aucune allocation (IP/Port) disponible sur le nœud.'], 400);
        }

        try {
            $server = $this->creationService->handle([
                'name' => $request->input('name'),
                'owner_id' => $user->id,
                'egg_id' => $request->input('egg_id'),
                'nest_id' => $request->input('nest_id'),
                'allocation_id' => $allocation->id,
                'memory' => $requested['memory'],
                'cpu' => $requested['cpu'],
                'disk' => $requested['disk'],
                'database_limit' => $requested['databases'],
                'backup_limit' => $requested['backups'],
                'swap' => 0,
                'io' => 500,
                'image' => 'ghcr.io/pterodactyl/yolks:debian', // Default image or from egg
                'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}}', // Default startup
                'environment' => [],
                'start_on_completion' => true,
            ]);

            // Decrement slots
            $user->decrement('bought_slots');

            return new JsonResponse([
                'success' => true,
                'server' => $server,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Échec de la création du serveur: ' . $e->getMessage()], 500);
        }
    }
}
