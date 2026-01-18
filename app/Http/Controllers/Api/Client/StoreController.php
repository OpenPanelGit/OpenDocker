<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\StoreProduct;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Exceptions\Model\DataValidationException;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class StoreController extends ClientApiController
{
    /**
     * Return all available store products.
     */
    public function index(ClientApiRequest $request): array
    {
        $settings = app(SettingsRepositoryInterface::class);
        $user = $request->user();
        
        // Ensure user has default values
        if (is_null($user->bought_cpu)) {
            $user->bought_cpu = 0;
            $user->bought_memory = 0;
            $user->bought_disk = 0;
            $user->bought_slots = 0;
            $user->bought_databases = 0;
            $user->bought_backups = 0;
            $user->save();
        }
        
        try {
            $available = $user->availableResources();
        } catch (\Exception $e) {
            \Log::error('Error getting available resources: ' . $e->getMessage());
            $available = [
                'memory' => 0,
                'cpu' => 0,
                'disk' => 0,
                'databases' => 0,
                'backups' => 0,
                'slots' => 0,
            ];
        }
        
        return [
            'success' => true,
            'products' => StoreProduct::where('enabled', true)->get(),
            'balance' => (float) ($user->coins ?? 0),
            'rate' => (float) ($settings->get('store:afk_rate') ?? 0.1),
            'limit_cpu' => (int) ($settings->get('store:limit_cpu') ?? 100),
            'limit_memory' => (int) ($settings->get('store:limit_memory') ?? 4096),
            'limit_disk' => (int) ($settings->get('store:limit_disk') ?? 10240),
            'limit_databases' => (int) ($settings->get('store:limit_databases') ?? 5),
            'limit_backups' => (int) ($settings->get('store:limit_backups') ?? 5),
            'available' => $available,
        ];
    }

    /**
     * Handle the purchase of a resource product.
     */
    public function purchase(ClientApiRequest $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $product = StoreProduct::where('id', $productId)->where('enabled', true)->firstOrFail();

        $user = $request->user();

        if ($user->coins < $product->price) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Vous n\'avez pas assez de coins.',
            ], 400);
        }

        // Deduct coins
        $user->decrement('coins', $product->price);

        // Increment user resources based on product type
        switch ($product->type) {
            case 'cpu':
                $user->increment('bought_cpu', $product->amount);
                break;
            case 'memory':
                $user->increment('bought_memory', $product->amount);
                break;
            case 'disk':
                $user->increment('bought_disk', $product->amount);
                break;
            case 'slots':
                $user->increment('bought_slots', $product->amount);
                break;
            case 'databases':
                $user->increment('bought_databases', $product->amount);
                break;
            case 'backups':
                $user->increment('bought_backups', $product->amount);
                break;
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Achat réussi !',
            'balance' => (float) $user->coins,
        ]);
    }

    /**
     * Periodically called by the frontend to earn AFK coins.
     */
    public function afk(ClientApiRequest $request): JsonResponse
    {
        $user = $request->user();
        $gain = (float) $request->input('gain', 0);
        
        // Relaxed sanity check: allow up to 100 coins per sync (10s) to allow for high rates/testing
        if ($gain <= 0 || $gain > 100) { 
            return new JsonResponse(['success' => false, 'balance' => (float) ($user->coins ?? 0)]);
        }

        // Ensure coins is not null before addition
        if (is_null($user->coins)) {
            $user->coins = 0;
            $user->save();
        }

        // Atomic update
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update([
                'coins' => \Illuminate\Support\Facades\DB::raw('COALESCE(coins, 0) + ' . (float) $gain),
                'last_afk_gain' => now(),
            ]);
        
        $freshUser = $user->fresh();
        \Illuminate\Support\Facades\Log::info("Store: User {$user->username} gained {$gain} coins. New balance: {$freshUser->coins}");

        return new JsonResponse([
            'success' => true,
            'balance' => (float) $freshUser->coins,
        ]);
    }
}
