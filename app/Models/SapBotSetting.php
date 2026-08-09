<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SapBotSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    public static function get($key, $default = null)
    {
        return Cache::remember('sap_bot_setting_' . $key, 60, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) return $default;
            
            if ($setting->is_encrypted && $setting->value) {
                try {
                    return Crypt::decryptString($setting->value);
                } catch (\Exception $e) {
                    return $default;
                }
            }
            return $setting->value;
        });
    }

    public static function put($key, $value, $encrypt = false)
    {
        $valToSave = $value;
        if ($encrypt && $value) {
            $valToSave = Crypt::encryptString($value);
        }

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $valToSave, 'is_encrypted' => $encrypt]
        );

        Cache::forget('sap_bot_setting_' . $key);
    }
}
