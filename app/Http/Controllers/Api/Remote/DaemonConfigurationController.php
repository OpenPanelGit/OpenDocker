<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Pterodactyl\Models\Node;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;

class DaemonConfigurationController extends Controller
{
    /**
     * Returns the configuration for a node in YAML format.
     */
    public function __invoke(Request $request, string $uuid)
    {
        $node = Node::where('uuid', $uuid)->firstOrFail();

        return response($node->getYamlConfiguration(), 200)
            ->header('Content-Type', 'application/yaml');
    }
}
