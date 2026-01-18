<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Pterodactyl\Models\Node;
use Illuminate\Http\Request;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Contracts\Encryption\Encrypter;
use Pterodactyl\Services\Api\KeyCreationService;
use Pterodactyl\Repositories\Eloquent\ApiKeyRepository;

class NodeInstallScriptController extends Controller
{
    public function __construct(
        private ApiKeyRepository $repository,
        private Encrypter $encrypter,
        private KeyCreationService $keyCreationService
    ) {}

    /**
     * Returns a bash script to install and configure Wings for the given node.
     */
    public function __invoke(Request $request, string $uuid)
    {
        \Illuminate\Support\Facades\Log::info("NodeInstallScript: Request for UUID {$uuid}");
        $node = Node::where('uuid', $uuid)->first();
        if (!$node) {
            return response('Erreur : Node introuvable avec l\'UUID ' . $uuid, 404)
                ->header('Content-Type', 'text/plain');
        }

        $token = $request->query('token');
        $user = $request->user();

        // 1. Authenticate (either session or token)
        if (!$user || !$user->root_admin) {
            if (!$token) {
                return response('Non autorisé.', 401);
            }

            // Verify token
            $key = ApiKey::where('key_type', ApiKey::TYPE_APPLICATION)
                ->get()
                ->filter(function (ApiKey $key) use ($token) {
                    return $key->identifier . $this->encrypter->decrypt($key->token) === $token;
                })
                ->first();

            if (!$key || !isset($key->r_nodes) || $key->r_nodes !== 1) {
                return response('Token invalide ou permissions insuffisantes.', 403);
            }
        }

        // 2. Get or create deployment token for the script itself to use
        if (!isset($key)) {
            $key = $this->repository->getApplicationKeys($request->user())
                ->filter(function (ApiKey $key) {
                    return isset($key->r_nodes) && $key->r_nodes === 1;
                })
                ->first();

            if (!$key) {
                $key = $this->keyCreationService->setKeyType(ApiKey::TYPE_APPLICATION)->handle([
                    'user_id' => ($user ? $user->id : 1), // Fallback to user 1 if via token
                    'memo' => 'Automatically generated node deployment key.',
                    'allowed_ips' => [],
                ], ['r_nodes' => 1]);
            }
        }

        $token = $key->identifier . $this->encrypter->decrypt($key->token);
        $appUrl = $request->getSchemeAndHttpHost();
        
        // Ensure appUrl doesn't have a trailing slash
        $appUrl = rtrim($appUrl, '/');

        $script = <<<EOT
#!/bin/bash

# OpenDocker Wings Auto-Installer
# Target Node: {$node->name} ({$node->uuid})

echo "🚀 Starting Wings installation for node: {$node->name}..."

# 1. Install Docker
if ! [ -x "$(command -v docker)" ]; then
    echo "📦 Installing Docker..."
    curl -sSL https://get.docker.com/ | CHANNEL=stable sh
    systemctl enable --now docker
else
    echo "✅ Docker is already installed."
fi

# 2. Create directory and download Wings
echo "📥 Downloading Wings..."
mkdir -p /etc/pterodactyl
curl -L -o /usr/local/bin/wings https://github.com/pterodactyl/wings/releases/latest/download/wings_linux_amd64
chmod +x /usr/local/bin/wings

# 3. Download Configuration from Panel
echo "⚙️  Fetching configuration from panel..."
curl -sSL -H "Authorization: Bearer {$token}" -H "Accept: application/yaml" "{$appUrl}/api/remote/config/{$node->uuid}" > /etc/pterodactyl/config.yml

if [ ! -s /etc/pterodactyl/config.yml ]; then
    echo "❌ Error: Configuration file is empty or could not be downloaded."
    echo "Check your panel URL ({$appUrl}) and ensure it's accessible from this server."
    exit 1
fi

# 4. Configure Systemd
echo "🔄 Setting up systemd service..."
cat <<EOF > /etc/systemd/system/wings.service
[Unit]
Description=Pterodactyl Wings Daemon
After=docker.service
Requires=docker.service

[Service]
User=root
WorkingDirectory=/etc/pterodactyl
LimitNOFILE=4096
PIDFile=/var/run/wings/daemon.pid
ExecStart=/usr/local/bin/wings
Restart=on-failure
StartLimitInterval=180
StartLimitBurst=30
RestartSec=5s

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable --now wings

echo "✅ Wings has been installed and started successfully!"
echo "Check your panel to see if the node shows as heartbeat (green)!"
EOT;

        return response($script, 200)
            ->header('Content-Type', 'text/plain');
    }
}
