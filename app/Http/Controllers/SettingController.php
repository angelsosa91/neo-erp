<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        // Obtener todas las configuraciones
        $settings = Setting::getMany([
            'company_name',
            'company_ruc',
            'company_address',
            'company_phone',
            'company_email',
            'company_city',
            'company_country',
            'invoice_prefix',
            'invoice_next_number',
            'purchase_prefix',
            'purchase_next_number',
            'expense_prefix',
            'expense_next_number',
            'default_tax_rate',
            'currency_symbol',
            'currency_code',
            'date_format',
            'timezone',
        ]);

        // Valores por defecto
        $defaults = [
            'company_name' => 'Mi Empresa',
            'company_ruc' => '',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_city' => 'Asunción',
            'company_country' => 'Paraguay',
            'invoice_prefix' => 'V-',
            'invoice_next_number' => '1',
            'purchase_prefix' => 'C-',
            'purchase_next_number' => '1',
            'expense_prefix' => 'G-',
            'expense_next_number' => '1',
            'default_tax_rate' => '10',
            'currency_symbol' => '₲',
            'currency_code' => 'PYG',
            'date_format' => 'd/m/Y',
            'timezone' => 'America/Asuncion',
        ];

        foreach ($defaults as $key => $default) {
            if (empty($settings[$key])) {
                $settings[$key] = $default;
            }
        }

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_ruc' => 'nullable|string|max:20',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_city' => 'nullable|string|max:100',
            'company_country' => 'nullable|string|max:100',
            'invoice_prefix' => 'required|string|max:10',
            'purchase_prefix' => 'required|string|max:10',
            'expense_prefix' => 'required|string|max:10',
            'default_tax_rate' => 'required|in:0,5,10',
            'currency_symbol' => 'required|string|max:5',
            'currency_code' => 'required|string|max:5',
            'date_format' => 'required|string|max:20',
            'timezone' => 'required|string|max:50',
        ]);

        $keys = [
            'company_name',
            'company_ruc',
            'company_address',
            'company_phone',
            'company_email',
            'company_city',
            'company_country',
            'invoice_prefix',
            'purchase_prefix',
            'expense_prefix',
            'default_tax_rate',
            'currency_symbol',
            'currency_code',
            'date_format',
            'timezone',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::setValue($key, $request->input($key));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada exitosamente',
        ]);
    }

    public function company()
    {
        $settings = Setting::getMany([
            'company_name',
            'company_ruc',
            'company_address',
            'company_phone',
            'company_email',
            'company_city',
            'company_country',
        ]);

        return view('settings.company', compact('settings'));
    }

    public function numbering()
    {
        $settings = Setting::getMany([
            'invoice_prefix',
            'invoice_next_number',
            'purchase_prefix',
            'purchase_next_number',
            'expense_prefix',
            'expense_next_number',
        ]);

        return view('settings.numbering', compact('settings'));
    }

    public function preferences()
    {
        $settings = Setting::getMany([
            'default_tax_rate',
            'currency_symbol',
            'currency_code',
            'date_format',
            'timezone',
        ]);

        return view('settings.preferences', compact('settings'));
    }
}
