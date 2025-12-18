<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function data(Request $request)
    {
        $page = $request->get('page', 1);
        $rows = $request->get('rows', 20);
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');
        $search = $request->get('search', '');

        $query = User::with(['roles', 'tenant'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $order);

        $total = $query->count();
        $users = $query->skip(($page - 1) * $rows)->take($rows)->get();

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'tenant' => $user->tenant?->name ?? 'Sin empresa',
                'roles' => $user->roles->pluck('name')->implode(', '),
                'is_active' => $user->is_active,
                'created_at' => $user->created_at->format('d/m/Y H:i'),
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'roles' => 'array',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente',
            'data' => $user,
        ]);
    }

    public function show(User $user)
    {
        $user->load('roles');
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'roles' => 'array',
            'is_active' => 'boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $validated['is_active'] ?? true;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente',
            'data' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puede eliminar su propio usuario',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado exitosamente',
        ]);
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puede desactivar su propio usuario',
            ], 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
        ]);
    }

    /**
     * Actualizar configuración POS del usuario
     */
    public function updatePosConfig(Request $request, User $user)
    {
        $request->validate([
            'pos_enabled' => 'boolean',
            'pos_require_rfid' => 'boolean',
            'rfid_code' => 'nullable|string|max:100|unique:users,rfid_code,' . $user->id,
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $user->update([
            'pos_enabled' => $request->has('pos_enabled'),
            'pos_require_rfid' => $request->has('pos_require_rfid'),
            'rfid_code' => $request->rfid_code,
            'commission_percentage' => $request->commission_percentage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración POS actualizada correctamente',
        ]);
    }

    /**
     * Establecer/Actualizar PIN del POS
     */
    public function setPosPin(Request $request, User $user)
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:6|regex:/^[0-9]+$/',
            'pin_confirmation' => 'required|same:pin',
        ], [
            'pin.regex' => 'El PIN debe contener solo números',
            'pin.min' => 'El PIN debe tener al menos 4 dígitos',
            'pin.max' => 'El PIN no puede tener más de 6 dígitos',
            'pin_confirmation.same' => 'Los PINs no coinciden',
        ]);

        $user->setPosPin($request->pin);

        return response()->json([
            'success' => true,
            'message' => 'PIN establecido correctamente',
        ]);
    }

    /**
     * Eliminar PIN del POS
     */
    public function removePosPin(User $user)
    {
        $user->update(['pos_pin' => null]);

        return response()->json([
            'success' => true,
            'message' => 'PIN eliminado correctamente',
        ]);
    }
}
