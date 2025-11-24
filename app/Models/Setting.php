<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    /**
     * Obtener un valor de configuración
     */
    public static function getValue($key, $default = null)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        $setting = self::where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Establecer un valor de configuración
     */
    public static function setValue($key, $value)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        return self::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Obtener múltiples configuraciones
     */
    public static function getMany(array $keys)
    {
        $tenantId = auth()->user()->tenant_id ?? 1;

        $settings = self::where('tenant_id', $tenantId)
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->toArray();

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $settings[$key] ?? null;
        }

        return $result;
    }

    /**
     * Establecer múltiples configuraciones
     */
    public static function setMany(array $data)
    {
        foreach ($data as $key => $value) {
            self::setValue($key, $value);
        }
    }
}
