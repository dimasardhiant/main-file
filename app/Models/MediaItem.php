<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\StorageConfigService;

class MediaItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['name', 'description'];

    public function registerMediaCollections(): void
    {
        $config = StorageConfigService::getStorageConfig();
        $allowedExtensions = array_map('trim', explode(',', strtolower($config['allowed_file_types'])));
        $maxSizeBytes = ($config['max_file_size_mb'] ?? 2) * 1024 * 1024; // Convert MB to bytes
        
        $this->addMediaCollection('images')
            ->acceptsFile(function ($file) use ($allowedExtensions, $maxSizeBytes) {
                // Check file extension
                $fileName = $file->name ?? $file->getFilename();
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (!in_array($extension, $allowedExtensions)) {
                    return false;
                }
                
                // Check file size
                $fileSize = $file->size ?? filesize($file->getPathname());
                if ($fileSize > $maxSizeBytes) {
                    return false;
                }
                
                return true;
            })
            ->useDisk(StorageConfigService::getActiveDisk());
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('images')
            ->nonQueued();
    }
}