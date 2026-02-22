<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DynamicStorageService
{
    /**
     * Configure dynamic storage disks based on database settings
     */
    public static function configureDynamicDisks(): void
    {
        try {
            $config = StorageConfigService::getStorageConfig();

            // Configure S3 disk if credentials exist
            if (!empty($config['s3']['key']) && !empty($config['s3']['secret'])) {
                self::configureS3Disk($config['s3']);
            }

            // Configure Wasabi disk if credentials exist
            if (!empty($config['wasabi']['key']) && !empty($config['wasabi']['secret'])) {
                self::configureWasabiDisk($config['wasabi']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to configure dynamic storage disks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private static function configureS3Disk(array $s3Config): void
    {
        // For standard AWS S3, endpoint should be null
        $endpoint = null;
        if (!empty($s3Config['endpoint']) && !str_contains($s3Config['endpoint'], 'amazonaws.com')) {
            $endpoint = $s3Config['endpoint'];
        }

        Config::set('filesystems.disks.s3', [
            'driver' => 's3',
            'key' => $s3Config['key'],
            'secret' => $s3Config['secret'],
            'region' => $s3Config['region'],
            'bucket' => $s3Config['bucket'],
            'url' => $s3Config['url'] ?: null,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => false,
            'visibility' => 'public',
        ]);
    }


    private static function configureWasabiDisk(array $wasabiConfig): void
    {
        $region = $wasabiConfig['region'] ?: 'us-east-1';
        $endpoint = $wasabiConfig['url'] ?: ('https://s3.' . $region . '.wasabisys.com');

        Config::set('filesystems.disks.wasabi', [
            'driver' => 's3',
            'key' => $wasabiConfig['key'],
            'secret' => $wasabiConfig['secret'],
            'region' => $region,
            'bucket' => $wasabiConfig['bucket'],
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => false,
            'visibility' => 'public',
        ]);
    }

    /**
     * Get the active storage disk instance
     */
    public static function getActiveDiskInstance()
    {
        $diskName = StorageConfigService::getActiveDisk();

        // Ensure disk is configured
        self::configureDynamicDisks();

        try {
            return Storage::disk($diskName);
        } catch (\Exception $e) {
            // Fallback to public disk
            return Storage::disk('public');
        }
    }

    /**
     * Test storage connection
     */
    public static function testConnection(string $diskName): bool
    {
        try {
            self::configureDynamicDisks();
            $disk = Storage::disk($diskName);

            // Try to write and read a test file
            $testContent = 'test-' . time();
            $testPath = 'test-connection.txt';

            $disk->put($testPath, $testContent);
            $retrieved = $disk->get($testPath);
            $disk->delete($testPath);

            return $retrieved === $testContent;
        } catch (\Exception $e) {
            return false;
        }
    }
}
