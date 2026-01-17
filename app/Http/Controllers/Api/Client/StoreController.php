<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\StoreProduct;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Exceptions\Http\HttpForbiddenException;
use Pterodactyl\Exceptions\Model\DataValidationException;

class StoreController extends ClientApiController
{
    /**
     * Return all available store products.
     */
    public function index(ClientApiRequest $request): array
    {
        return [
            'success' => true,
            'products' => StoreProduct::where('enabled', true)->get(),
            'balance' => $request->user()->coins,
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
            'balance' => $user->coins,
        ]);
    }

    /**
     * Periodically called by the frontend to earn AFK coins.
     */
    public function afk(ClientApiRequest $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        
        // Earning rate (coins per minute) - Default to 0.1 if not set
        $earningRate = (float) ($this->settings->get('store:afk_rate') ?? 0.1);
        
        if (!$user->last_afk_gain) {
            $user->update(['last_afk_gain' => $now]);
            return new JsonResponse(['success' => true, 'balance' => $user->coins]);
        }

        $diffInMinutes = $now->diffInMinutes($user->last_afk_gain);
        
        if ($diffInMinutes >= 1) {
            $gain = $diffInMinutes * $earningRate;
            $user->increment('coins', $gain);
            $user->update(['last_afk_gain' => $now]);
            
            return new JsonResponse([
                'success' => true,
                'gain' => $gain,
                'balance' => $user->coins,
            ]);
        }

        return new JsonResponse(['success' => true, 'balance' => $user->coins]);
    }
}
