<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StorageConfigService
{
    private static $config = null;

    /**
     * Get the active storage disk name
     */
    public static function getActiveDisk(): string
    {
        $userId = Auth::id();
        if (!$userId) {
            return 'public'; // Default for unauthenticated users
        }

        $cacheKey = 'active_storage_config';
        $config = Cache::remember($cacheKey, 300, function () use ($userId) {
            return self::loadStorageConfigFromDB($userId);
        });

        return $config['disk'] ?? 'public';
    }

    /**
     * Get file validation rules based on settings
     */
    public static function getFileValidationRules(): array
    {
        $config = self::getStorageConfig();

        $allowedTypes = $config['allowed_file_types'] ?? '';
        $maxSize = ($config['max_file_size_mb'] ?? 2) * 1024; // Convert MB to KB

        return [
            'mimes:' . $allowedTypes,
            'max:' . $maxSize
        ];
    }

    /**
     * Get complete storage configuration
     */
    public static function getStorageConfig(): array
    {
        try {
            // Check if user is authenticated
            if (!Auth::check() || !Auth::user()) {
                return self::getDefaultConfig();
            }

            $user = Auth::user();
            $userId = null;

            // Check if isSaaS function exists and handle accordingly
            if (isSaaS()) {
                if ($user->type === 'superadmin') {
                    $userId = $user->id;
                } else {
                    $userId = $user->created_by ?? null;
                }
            } else {
                if ($user->type === 'company') {
                    $userId = $user->id;
                } else {
                    $userId = $user->created_by ?? null;
                }
            }

            if (!$userId) {
                return self::getDefaultConfig();
            }

            $cacheKey = 'active_storage_config_' . $userId;
            // return Cache::remember($cacheKey, 300, function () use ($userId) {
            return self::loadStorageConfigFromDB($userId);
            // });
        } catch (\Exception $e) {
            \Log::error('Error in getStorageConfig', ['error' => $e->getMessage()]);
            return self::getDefaultConfig();
        }
    }

    /**
     * Clear storage configuration cache
     */
    public static function clearCache(): void
    {
        Cache::forget('active_storage_config');
    }

    /**
     * Load storage configuration from database
     */
    private static function loadStorageConfigFromDB($userId = null): array
    {
        try {

            if (!$userId) {
                return self::getDefaultConfig();
            }

            $settings = DB::table('settings')
                ->where('user_id', $userId)
                ->whereIn('key', [
                    'storage_type',
                    'storage_file_types',
                    'storage_max_upload_size',
                    'aws_access_key_id',
                    'aws_secret_access_key',
                    'aws_default_region',
                    'aws_bucket',
                    'aws_url',
                    'aws_endpoint',
                    'wasabi_access_key',
                    'wasabi_secret_key',
                    'wasabi_region',
                    'wasabi_bucket',
                    'wasabi_url',
                    'wasabi_root'
                ])
                ->pluck('value', 'key')
                ->toArray();
            // Map storage_type to correct disk name

            if (isSaaS()) {
                $superAdmin = User::where('type', 'superadmin')->first();
                if ($superAdmin) {
                    $superAdminSettings = DB::table('settings')->where('user_id', $superAdmin->id)->whereIn('key', [
                        'storage_file_types',
                        'storage_max_upload_size'
                    ])
                        ->pluck('value', 'key')
                        ->toArray();
                }
            } else {
                $superAdmin = User::where('type', 'company')->first();
                if ($superAdmin) {
                    $superAdminSettings = DB::table('settings')->where('user_id', $superAdmin->id)->whereIn('key', [
                        'storage_file_types',
                        'storage_max_upload_size'
                    ])
                        ->pluck('value', 'key')
                        ->toArray();
                }
            }

            $storageType = $settings['storage_type'] ?? 'local';
            $diskName = match ($storageType) {
                'local' => 'public',
                'aws_s3' => 's3',
                'wasabi' => 'wasabi',
                default => 'public'
            };

            return [
                'disk' => $diskName,
                'allowed_file_types' => $superAdminSettings['storage_file_types'] ?? 'jpg,jpeg,png,webp,gif,pdf,doc,docx,csv,txt,zip,mp4,mp3',
                'max_file_size_mb' => (int)($superAdminSettings['storage_max_upload_size'] ?? 2),
                's3' => [
                    'key' => $settings['aws_access_key_id'] ?? '',
                    'secret' => $settings['aws_secret_access_key'] ?? '',
                    'bucket' => $settings['aws_bucket'] ?? '',
                    'region' => $settings['aws_default_region'] ?? 'us-east-1',
                    'url' => $settings['aws_url'] ?? '',
                    'endpoint' => $settings['aws_endpoint'] ?? '',
                ],
                'wasabi' => [
                    'key' => $settings['wasabi_access_key'] ?? '',
                    'secret' => $settings['wasabi_secret_key'] ?? '',
                    'bucket' => $settings['wasabi_bucket'] ?? '',
                    'region' => $settings['wasabi_region'] ?? 'us-east-1',
                    'url' => $settings['wasabi_url'] ?? '',
                    'root' => $settings['wasabi_root'] ?? '',
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to load storage config from DB', ['error' => $e->getMessage()]);
            return self::getDefaultConfig();
        }
    }

    /**
     * Get default storage configuration
     */
    private static function getDefaultConfig(): array
    {
        return [
            'disk' => 'public',
            'allowed_file_types' => 'jpg,png,webp,gif,png',
            'max_file_size_mb' => 2,
            's3' => [],
            'wasabi' => []
        ];
    }
}
