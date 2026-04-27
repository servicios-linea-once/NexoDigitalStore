<?php

namespace App\Http\Controllers\Admin\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Store\Keys\ImportKeysRequest;
use App\Models\DigitalKey;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KeyController extends Controller
{
    // ── Index: Ver claves ─────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $query = DigitalKey::with('product:id,name,slug,ulid')
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->status,     fn ($q) => $q->where('status', $request->status))
            ->latest();

        $keys = $query->paginate(50)->withQueryString();

        $stats = DigitalKey::selectRaw("
                COUNT(*) as total,
                SUM(status = 'available') as available,
                SUM(status = 'sold') as sold,
                SUM(status = 'reserved') as reserved,
                SUM(status = 'expired') as expired
            ")
            ->first();

        $storeProducts = Product::whereIn('status', ['active', 'draft', 'paused'])
            ->get(['id', 'ulid', 'name', 'stock_count']);

        return Inertia::render('Admin/Store/Keys/Index', [
            'keys' => $keys->through(fn ($k) => [
                'id'           => $k->id,
                'ulid'         => $k->ulid,
                'key_value'    => $k->key_value,
                'product'      => $k->product ? ['name' => $k->product->name, 'ulid' => $k->product->ulid] : null,
                'status'       => $k->status,
                'license_type' => $k->license_type,
                'max_activations' => $k->max_activations,
                'created_at'   => $k->created_at->toIso8601String(),
                'sold_at'      => $k->sold_at?->toIso8601String(),
            ]),
            'stats'    => $stats,
            'products' => $storeProducts,
            'filters'  => $request->only(['product_id', 'status']),
        ]);
    }

    // ── Import: modo individual o masivo ──────────────────────────────────
    public function import(ImportKeysRequest $request): RedirectResponse
    {
        $product   = Product::findOrFail($request->product_id);
        $rawKeys   = $this->parseKeys($request);
        $imported  = 0;
        $duplicates = 0;

        if (empty($rawKeys)) {
            return back()->withErrors(['keys_text' => 'No se encontraron claves válidas.']);
        }

        DB::transaction(function () use ($rawKeys, $product, $request, &$imported, &$duplicates) {
            foreach ($rawKeys as $rawKey) {
                $rawKey = trim($rawKey);
                if ($rawKey === '') {
                    continue;
                }

                $hash = hash('sha256', $rawKey.$product->id);

                if (DigitalKey::where('product_id', $product->id)->where('key_hash', $hash)->exists()) {
                    $duplicates++;
                    continue;
                }

                DigitalKey::create([
                    'product_id'      => $product->id,
                    'seller_id'       => $request->user()->id,
                    'key_value'       => $rawKey,
                    'key_hash'        => $hash,
                    'status'          => 'available',
                    'license_type'    => $request->license_type,
                    'max_activations' => $request->max_activations,
                    'delivery_method' => $product->delivery_type,
                ]);

                $imported++;
            }

            if ($imported > 0) {
                $product->increment('stock_count', $imported);
            }
        });

        $message = "{$imported} clave(s) importadas correctamente.";
        if ($duplicates > 0) {
            $message .= " {$duplicates} duplicada(s) omitidas.";
        }

        return back()->with('success', $message);
    }

    // ── Delete ────────────────────────────────────────────────────────────
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $key = DigitalKey::where('id', $id)
            ->where('status', 'available')
            ->firstOrFail();

        DB::transaction(function () use ($key) {
            $key->product->decrement('stock_count');
            $key->delete();
        });

        return back()->with('success', 'Clave eliminada.');
    }

    // ── Parse Keys (single/bulk/file) ─────────────────────────────────────
    private function parseKeys(ImportKeysRequest $request): array
    {
        // Modo individual: una sola clave
        if ($request->filled('key_value')) {
            return [trim($request->string('key_value'))];
        }

        $raw = '';

        if ($request->hasFile('keys_file')) {
            $raw = (string) file_get_contents($request->file('keys_file')->getRealPath());
        } elseif ($request->filled('keys_text')) {
            $raw = (string) $request->keys_text;
        }

        $keys = preg_split('/[\n\r,]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $keys)));
    }
}
