<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Pterodactyl\Models\StoreProduct;
use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;

class StoreController extends Controller
{
    /**
     * Render the admin store index page.
     */
    public function index()
    {
        try {
            $settings = app(SettingsRepositoryInterface::class);
            return view('admin.store.index', [
                'products' => StoreProduct::all(),
                'afk_rate' => $settings->get('store:afk_rate', 0.1),
                'limit_cpu' => $settings->get('store:limit_cpu', 100),
                'limit_memory' => $settings->get('store:limit_memory', 4096),
                'limit_disk' => $settings->get('store:limit_disk', 10240),
                'limit_databases' => $settings->get('store:limit_databases', 5),
                'limit_backups' => $settings->get('store:limit_backups', 5),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Update the AFK rate settings.
     */
    public function updateSettings(Request $request)
    {
        $settings = app(SettingsRepositoryInterface::class);
        $settings->set('store:afk_rate', $request->input('afk_rate'));
        $settings->set('store:limit_cpu', $request->input('limit_cpu'));
        $settings->set('store:limit_memory', $request->input('limit_memory'));
        $settings->set('store:limit_disk', $request->input('limit_disk'));
        $settings->set('store:limit_databases', $request->input('limit_databases'));
        $settings->set('store:limit_backups', $request->input('limit_backups'));

        return redirect()->route('admin.store.index')->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Create a new store product.
     */
    public function store(Request $request)
    {
        StoreProduct::create($request->only(['name', 'description', 'type', 'amount', 'price', 'enabled']));

        return redirect()->route('admin.store.index')->with('success', 'Produit ajouté avec succès.');
    }

    /**
     * Delete a store product.
     */
    public function delete(StoreProduct $product)
    {
        $product->delete();

        return redirect()->route('admin.store.index')->with('success', 'Produit supprimé avec succès.');
    }
}
