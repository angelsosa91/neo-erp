<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Usuarios
            ['name' => 'Ver Usuarios', 'slug' => 'users.view', 'module' => 'usuarios'],
            ['name' => 'Crear Usuarios', 'slug' => 'users.create', 'module' => 'usuarios'],
            ['name' => 'Editar Usuarios', 'slug' => 'users.edit', 'module' => 'usuarios'],
            ['name' => 'Eliminar Usuarios', 'slug' => 'users.delete', 'module' => 'usuarios'],
            
            // Roles
            ['name' => 'Ver Roles', 'slug' => 'roles.view', 'module' => 'roles'],
            ['name' => 'Crear Roles', 'slug' => 'roles.create', 'module' => 'roles'],
            ['name' => 'Editar Roles', 'slug' => 'roles.edit', 'module' => 'roles'],
            ['name' => 'Eliminar Roles', 'slug' => 'roles.delete', 'module' => 'roles'],
            
            // Clientes
            ['name' => 'Ver Clientes', 'slug' => 'customers.view', 'module' => 'clientes'],
            ['name' => 'Crear Clientes', 'slug' => 'customers.create', 'module' => 'clientes'],
            ['name' => 'Editar Clientes', 'slug' => 'customers.edit', 'module' => 'clientes'],
            ['name' => 'Eliminar Clientes', 'slug' => 'customers.delete', 'module' => 'clientes'],
            
            // Proveedores
            ['name' => 'Ver Proveedores', 'slug' => 'suppliers.view', 'module' => 'proveedores'],
            ['name' => 'Crear Proveedores', 'slug' => 'suppliers.create', 'module' => 'proveedores'],
            ['name' => 'Editar Proveedores', 'slug' => 'suppliers.edit', 'module' => 'proveedores'],
            ['name' => 'Eliminar Proveedores', 'slug' => 'suppliers.delete', 'module' => 'proveedores'],
            
            // Productos
            ['name' => 'Ver Productos', 'slug' => 'products.view', 'module' => 'productos'],
            ['name' => 'Crear Productos', 'slug' => 'products.create', 'module' => 'productos'],
            ['name' => 'Editar Productos', 'slug' => 'products.edit', 'module' => 'productos'],
            ['name' => 'Eliminar Productos', 'slug' => 'products.delete', 'module' => 'productos'],
            
            // Ventas
            ['name' => 'Ver Ventas', 'slug' => 'sales.view', 'module' => 'ventas'],
            ['name' => 'Crear Ventas', 'slug' => 'sales.create', 'module' => 'ventas'],
            ['name' => 'Anular Ventas', 'slug' => 'sales.cancel', 'module' => 'ventas'],

            // Notas de Crédito
            ['name' => 'Ver Notas de Crédito', 'slug' => 'credit-notes.view', 'module' => 'notas_credito'],
            ['name' => 'Crear Notas de Crédito', 'slug' => 'credit-notes.create', 'module' => 'notas_credito'],
            ['name' => 'Confirmar Notas de Crédito', 'slug' => 'credit-notes.confirm', 'module' => 'notas_credito'],
            ['name' => 'Anular Notas de Crédito', 'slug' => 'credit-notes.cancel', 'module' => 'notas_credito'],
            
            // Compras
            ['name' => 'Ver Compras', 'slug' => 'purchases.view', 'module' => 'compras'],
            ['name' => 'Crear Compras', 'slug' => 'purchases.create', 'module' => 'compras'],
            ['name' => 'Editar Compras', 'slug' => 'purchases.edit', 'module' => 'compras'],
            ['name' => 'Anular Compras', 'slug' => 'purchases.cancel', 'module' => 'compras'],
            
            // Gastos
            ['name' => 'Ver Gastos', 'slug' => 'expenses.view', 'module' => 'gastos'],
            ['name' => 'Crear Gastos', 'slug' => 'expenses.create', 'module' => 'gastos'],
            ['name' => 'Editar Gastos', 'slug' => 'expenses.edit', 'module' => 'gastos'],
            ['name' => 'Eliminar Gastos', 'slug' => 'expenses.delete', 'module' => 'gastos'],
            
            // Reportes
            ['name' => 'Ver Reportes', 'slug' => 'reports.view', 'module' => 'reportes'],
            
            // Configuración
            ['name' => 'Configuración General', 'slug' => 'settings.general', 'module' => 'configuracion'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
