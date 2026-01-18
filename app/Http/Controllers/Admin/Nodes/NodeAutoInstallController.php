<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Models\Location;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Nodes\NodeCreationService;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Repositories\Eloquent\ApiKeyRepository;
use Pterodactyl\Contracts\Repository\LocationRepositoryInterface;

class NodeAutoInstallController extends Controller
{
    public function __construct(
        private AlertsMessageBag $alert,
        private NodeCreationService $creationService,
        private LocationRepositoryInterface $locationRepository,
        private ApiKeyRepository $apiKeyRepository,
        private KeyCreationService $keyCreationService,
        private Encrypter $encrypter
    ) {}

    /**
     * Automatically creates a local node and redirects to the auto-deploy page.
     */
    public function index(Request $request): RedirectResponse
    {
        // 1. Ensure a location exists
        $location = Location::first();
        if (!$location) {
            $location = $this->locationRepository->create([
                'short' => 'Local',
                'long' => 'Default Local Location',
            ]);
        }

        // 2. Identify server IP
        $ip = $request->server('SERVER_ADDR') ?? $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
        if ($ip === '::1' || $ip === '127.0.0.1') {
            // Try to get public IP via external service if local
            try {
                $ip = file_get_contents('https://api.ipify.org') ?: $ip;
            } catch (\Exception $e) {
                // Fallback to 127.0.0.1
            }
        }

        // 3. Create the Node
        try {
            $node = $this->creationService->handle([
                'name' => 'Auto Node ' . time(),
                'location_id' => $location->id,
                'fqdn' => $ip,
                'scheme' => 'http', // Default to http for easier auto-setup
                'behind_proxy' => false,
                'maintenance_mode' => false,
                'memory' => 2048,
                'memory_overallocate' => 0,
                'disk' => 10240,
                'disk_overallocate' => 0,
                'upload_size' => 100,
                'daemonListen' => 8080,
                'daemonSFTP' => 2022,
                'daemonBase' => '/var/lib/pterodactyl/volumes',
            ]);

            $this->alert->success('Node créé automatiquement ! Prochaine étape : installation de Wings.')->flash();

            return redirect()->route('admin.nodes.view.configuration', $node->id);
        } catch (\Exception $e) {
            $this->alert->error('Erreur lors de la création automatique : ' . $e->getMessage())->flash();
            return redirect()->route('admin.nodes');
        }
    }
}
