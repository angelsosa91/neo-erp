<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index()
    {
        return view('services.index');
    }

    /**
     * Get data for DataGrid
     */
    public function data(Request $request)
    {
        $query = Service::with('category')
            ->where('tenant_id', Auth::user()->tenant_id);

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->search($search);
        }

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        // Sorting
        $sortField = $request->get('sort', 'sort_order');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $page = $request->get('page', 1);
        $pageSize = $request->get('rows', 20);
        $offset = ($page - 1) * $pageSize;

        $total = $query->count();
        $services = $query->offset($offset)->limit($pageSize)->get();

        // Format data
        $rows = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'code' => $service->code,
                'name' => $service->name,
                'category_name' => $service->category ? $service->category->name : '-',
                'price' => number_format($service->price, 0, ',', '.'),
                'price_raw' => $service->price,
                'duration' => $service->formatted_duration ?? '-',
                'tax_rate' => $service->tax_rate . '%',
                'commission' => $service->commission_percentage ? $service->commission_percentage . '%' : '-',
                'is_active' => $service->is_active,
                'created_at' => $service->created_at->format('d/m/Y'),
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create()
    {
        $categories = Category::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get();

        $nextCode = Service::generateCode(Auth::user()->tenant_id);

        return view('services.create', compact('categories', 'nextCode'));
    }

    /**
     * Store a newly created service in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:services,code,NULL,id,tenant_id,' . Auth::user()->tenant_id,
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'required|in:0,5,10',
            'duration_minutes' => 'nullable|integer|min:1',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $service = Service::create([
            'tenant_id' => Auth::user()->tenant_id,
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'tax_rate' => $request->tax_rate,
            'duration_minutes' => $request->duration_minutes,
            'commission_percentage' => $request->commission_percentage,
            'color' => $request->color,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio creado exitosamente',
            'service' => $service,
        ]);
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service)
    {
        // Verificar tenant
        if ($service->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $service->load('category');

        return response()->json($service);
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Service $service)
    {
        // Verificar tenant
        if ($service->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $categories = Category::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get();

        return view('services.edit', compact('service', 'categories'));
    }

    /**
     * Update the specified service in storage.
     */
    public function update(Request $request, Service $service)
    {
        // Verificar tenant
        if ($service->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'code' => 'required|string|max:50|unique:services,code,' . $service->id . ',id,tenant_id,' . Auth::user()->tenant_id,
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'tax_rate' => 'required|in:0,5,10',
            'duration_minutes' => 'nullable|integer|min:1',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $service->update([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'price' => $request->price,
            'tax_rate' => $request->tax_rate,
            'duration_minutes' => $request->duration_minutes,
            'commission_percentage' => $request->commission_percentage,
            'color' => $request->color,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado exitosamente',
            'service' => $service,
        ]);
    }

    /**
     * Remove the specified service from storage.
     */
    public function destroy(Service $service)
    {
        // Verificar tenant
        if ($service->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Verificar si tiene ventas asociadas
        if ($service->saleServiceItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el servicio porque tiene ventas asociadas. Puede desactivarlo en su lugar.',
            ], 400);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado exitosamente',
        ]);
    }

    /**
     * Get list of services for combo/select
     */
    public function list(Request $request)
    {
        $query = Service::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true);

        // Search
        if ($request->has('q') && !empty($request->q)) {
            $search = $request->q;
            $query->search($search);
        }

        $services = $query->orderBy('name')
            ->limit(50)
            ->get(['id', 'code', 'name', 'price', 'tax_rate']);

        return response()->json($services->map(function ($service) {
            return [
                'id' => $service->id,
                'text' => $service->name,
                'name' => $service->name,
                'code' => $service->code,
                'price' => $service->price,
                'tax_rate' => $service->tax_rate,
            ];
        }));
    }

    /**
     * Get popular services for POS
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 12);

        $services = Service::where('tenant_id', Auth::user()->tenant_id)
            ->popular($limit)
            ->get();

        return response()->json($services);
    }
}
