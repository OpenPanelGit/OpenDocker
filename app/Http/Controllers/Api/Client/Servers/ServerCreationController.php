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

        // Fix: Use slots as a Limit, not a consumable.
        // If the user has more or equal servers than bought slots, valid deny.
        // We use the same calculation as User::availableResources to be consistent.
        $usedSlots = $user->servers()->count();
        $totalSlots = $user->bought_slots ?? 0;
        
        if ($usedSlots >= $totalSlots) {
             return new JsonResponse(['error' => "Vous avez atteint votre limite de serveurs (Slots: {$totalSlots})."], 400);
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
            // Note: availableResources() calculation includes the current request's potential usage 
            // only if we correctly subtract used. User::available logic already does (bought - allocated).
            // So plain comparison is correct.
            if ($value > $available[$key]) {
                return new JsonResponse(['error' => "Vous n'avez pas assez de ressources ({$key}) disponibles. Demandé: {$value}, Disponible: {$available[$key]}"], 400);
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
            \Log::error("Store: Server creation failed. No public node found.");
            return new JsonResponse(['error' => 'Aucun nœud public disponible pour le déploiement. Veuillez contacter l\'administrateur.'], 400);
        }

        // Find an available allocation on that node
        $allocation = Allocation::where('node_id', $node->id)->whereNull('server_id')->first();
        if (!$allocation) {
            \Log::error("Store: Server creation failed. No allocation found on Node ID {$node->id}.");
            return new JsonResponse(['error' => 'Aucune allocation (IP/Port) disponible sur le nœud. Veuillez contacter l\'administrateur.'], 400);
        }

        // Fetch Egg to get startup details
        $egg = \Pterodactyl\Models\Egg::find($request->input('egg_id'));
        if (!$egg || $egg->nest_id != $request->input('nest_id')) {
            return new JsonResponse(['error' => 'L\'Egg sélectionné est invalide ou n\'appartient pas à la catégorie choisie.'], 400);
        }
        
        // Get the first docker image from the list or fallback
        $dockerImage = 'ghcr.io/pterodactyl/yolks:debian';
        if (!empty($egg->docker_images)) {
             $dockerImage = array_values($egg->docker_images)[0];
        }

        // Prepare environment variables from Egg defaults
        $environment = [];
        foreach ($egg->variables as $variable) {
            $environment[$variable->env_variable] = $variable->default_value;
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
                'image' => $dockerImage,
                'startup' => $egg->startup,
                'environment' => $environment,
                'start_on_completion' => true,
            ]);

            // We do NOT decrement bought_slots anymore, as it acts as a limit.
            // $user->decrement('bought_slots'); 

            return new JsonResponse([
                'success' => true,
                'server' => $server,
            ]);
        } catch (\Exception $e) {
            \Log::error($e);
            return new JsonResponse(['error' => 'Échec de la création du serveur: ' . $e->getMessage()], 500);
        }
    }
}
