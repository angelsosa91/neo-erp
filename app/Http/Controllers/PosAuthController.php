<?php

namespace App\Http\Controllers;

use App\Models\PosSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleServiceItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosAuthController extends Controller
{
    /**
     * Mostrar pantalla de login POS
     */
    public function showLogin()
    {
        // Si ya tiene sesión POS activa, redirigir al POS
        if ($this->hasActivePosSession()) {
            return redirect()->route('pos.index');
        }

        return view('pos.login');
    }

    /**
     * Autenticar usuario con PIN
     */
    public function login(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:6',
        ]);

        $currentUser = Auth::user();
        $tenantId = $currentUser->tenant_id;

        // Buscar usuario del mismo tenant que tenga ese PIN
        $users = User::where('tenant_id', $tenantId)
            ->where('pos_enabled', true)
            ->where('is_active', true)
            ->whereNotNull('pos_pin')
            ->get();

        $user = null;
        foreach ($users as $potentialUser) {
            if ($potentialUser->verifyPosPin($request->pin)) {
                $user = $potentialUser;
                break;
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'PIN incorrecto o usuario no habilitado para POS',
            ], 401);
        }

        // Verificar permiso pos.use
        if (!$user->hasPermission('pos.use')) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para usar el POS',
            ], 403);
        }

        // Si requiere 2FA (RFID), indicar que debe verificar RFID
        if ($user->posRequires2FA()) {
            // Guardar en sesión temporal que el PIN fue verificado
            session(['pos_pin_verified' => true, 'pos_user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'requires_rfid' => true,
                'message' => 'PIN correcto. Por favor, acerque su tarjeta RFID',
            ]);
        }

        // Cerrar cualquier sesión POS activa anterior
        $this->closeActiveSessions($user->id);

        // Crear nueva sesión POS
        $posSession = PosSession::createSession(
            $user,
            'pin',
            null,
            $request->input('terminal_id')
        );

        // IMPORTANTE: Hacer login del vendedor en Laravel
        // Esto actualiza Auth::user() para que devuelva al vendedor correcto
        Auth::login($user);

        // Guardar token en sesión
        session(['pos_session_token' => $posSession->session_token]);

        return response()->json([
            'success' => true,
            'requires_rfid' => false,
            'message' => 'Autenticación exitosa',
            'redirect' => route('pos.index'),
        ]);
    }

    /**
     * Verificar código RFID (segundo factor de autenticación)
     */
    public function verifyRfid(Request $request)
    {
        $request->validate([
            'rfid_code' => 'required|string',
        ]);

        // Verificar que el PIN fue verificado previamente
        if (!session('pos_pin_verified')) {
            return response()->json([
                'success' => false,
                'message' => 'Debe ingresar su PIN primero',
            ], 403);
        }

        $userId = session('pos_user_id');
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        // Verificar código RFID
        if (!$user->verifyRfidCode($request->rfid_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Código RFID incorrecto',
            ], 401);
        }

        // Limpiar sesión temporal
        session()->forget(['pos_pin_verified', 'pos_user_id']);

        // Cerrar cualquier sesión POS activa anterior
        $this->closeActiveSessions($user->id);

        // Crear nueva sesión POS con 2FA
        $posSession = PosSession::createSession(
            $user,
            'pin+rfid',
            $request->rfid_code,
            $request->input('terminal_id')
        );

        // IMPORTANTE: Hacer login del vendedor en Laravel
        // Esto actualiza Auth::user() para que devuelva al vendedor correcto
        Auth::login($user);

        // Guardar token en sesión
        session(['pos_session_token' => $posSession->session_token]);

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa',
            'redirect' => route('pos.index'),
        ]);
    }

    /**
     * Cerrar sesión POS
     */
    public function logout(Request $request)
    {
        $sessionToken = session('pos_session_token');

        if ($sessionToken) {
            $posSession = PosSession::where('session_token', $sessionToken)->first();

            if ($posSession) {
                $posSession->close();
            }

            session()->forget('pos_session_token');
        }

        // IMPORTANTE: Cerrar también la sesión de Laravel
        // Esto desloguea al vendedor del sistema completo
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
            'redirect' => route('login'), // Redirige al login principal del sistema
        ]);
    }

    /**
     * Verificar estado de la sesión (para polling de timeout)
     */
    public function checkSession(Request $request)
    {
        $sessionToken = session('pos_session_token');

        if (!$sessionToken) {
            return response()->json([
                'active' => false,
                'message' => 'No hay sesión activa',
            ]);
        }

        $posSession = PosSession::where('session_token', $sessionToken)->first();

        if (!$posSession || $posSession->isExpired(10)) {
            if ($posSession) {
                $posSession->markAsExpired();
            }
            session()->forget('pos_session_token');

            return response()->json([
                'active' => false,
                'expired' => true,
                'message' => 'Sesión expirada',
            ]);
        }

        // Actualizar actividad
        $posSession->updateActivity();

        return response()->json([
            'active' => true,
            'user' => $posSession->user->name,
            'opened_at' => $posSession->opened_at->format('H:i'),
            'duration' => $posSession->formatted_duration,
        ]);
    }

    /**
     * Verificar si el usuario tiene una sesión POS activa
     */
    private function hasActivePosSession(): bool
    {
        $sessionToken = session('pos_session_token');

        if (!$sessionToken) {
            return false;
        }

        $posSession = PosSession::where('session_token', $sessionToken)->first();

        return $posSession && !$posSession->isExpired(10);
    }

    /**
     * Cerrar todas las sesiones activas del usuario
     */
    private function closeActiveSessions(int $userId): void
    {
        PosSession::where('user_id', $userId)
            ->where('status', 'active')
            ->each(function ($session) {
                $session->close();
            });
    }

    /**
     * Obtener items (servicios + productos) para el POS
     */
    public function items(Request $request)
    {
        $limit = $request->get('limit', 50);
        $user = $request->user();

        // Obtener servicios activos ordenados por sort_order
        $services = Service::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'type' => 'service',
                    'name' => $service->name,
                    'price' => $service->price,
                    'tax_rate' => $service->tax_rate,
                    'color' => $service->color,
                    'icon' => $service->icon,
                    'formatted_duration' => $service->formatted_duration,
                ];
            });

        // Obtener productos activos con stock
        $products = Product::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'type' => 'product',
                    'name' => $product->name,
                    'price' => $product->sale_price,
                    'tax_rate' => $product->tax_rate,
                    'stock' => $product->stock,
                    'color' => null,
                    'icon' => null,
                    'formatted_duration' => null,
                ];
            });

        // Combinar servicios y productos
        $items = $services->concat($products);

        return response()->json($items->values());
    }

    /**
     * Procesar una venta desde el POS
     */
    public function storeSale(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:service,product',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'required|integer|in:0,5,10',
            'payment_method' => 'required|string|in:efectivo,tarjeta,transferencia',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $sessionToken = session('pos_session_token');
            $posSession = PosSession::where('session_token', $sessionToken)->first();

            // Crear la pre-venta (borrador)
            $sale = Sale::create([
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'pos_session_id' => $posSession?->id,
                'sale_number' => Sale::generateSaleNumber($user->tenant_id),
                'sale_date' => now()->toDateString(),
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'draft', // Pre-venta - debe confirmarse después
            ]);

            // Crear los items de la venta
            foreach ($validated['items'] as $itemData) {
                if ($itemData['type'] === 'service') {
                    // Item de servicio
                    $service = Service::find($itemData['id']);

                    if (!$service || $service->tenant_id !== $user->tenant_id) {
                        throw new \Exception('Servicio no encontrado o no pertenece al tenant');
                    }

                    SaleServiceItem::create([
                        'sale_id' => $sale->id,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'],
                        'commission_percentage' => $service->commission_percentage,
                    ]);
                } else {
                    // Item de producto
                    $product = Product::find($itemData['id']);

                    if (!$product || $product->tenant_id !== $user->tenant_id) {
                        throw new \Exception('Producto no encontrado o no pertenece al tenant');
                    }

                    // Verificar stock disponible (solo advertencia, no se descuenta aún)
                    if ($product->track_stock && $product->stock < $itemData['quantity']) {
                        throw new \Exception("Stock insuficiente para el producto {$product->name}");
                    }

                    $saleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'tax_rate' => $itemData['tax_rate'],
                    ]);

                    // Calcular valores del item
                    $saleItem->calculateValues();
                    $saleItem->save();

                    // NO se descuenta stock en pre-venta
                    // El stock se descontará cuando se confirme la venta
                }
            }

            // Cargar los items y calcular totales
            $sale->load(['items', 'serviceItems']);
            $sale->calculateTotals();
            $sale->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pre-venta creada exitosamente. Debe confirmarse para descontar stock.',
                'sale' => [
                    'id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'status' => $sale->status,
                    'total' => $sale->total,
                    'subtotal_exento' => $sale->subtotal_exento,
                    'subtotal_5' => $sale->subtotal_5,
                    'iva_5' => $sale->iva_5,
                    'subtotal_10' => $sale->subtotal_10,
                    'iva_10' => $sale->iva_10,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: ' . $e->getMessage(),
            ], 500);
        }
    }
}
