<?php

namespace Database\Seeders;

use App\Models\MediaDirectory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $isSaas =  isSaas();

        // Get super admin and companies
        $superAdmin = User::where('type', 'superadmin')->first();
        $companies = User::where('type', 'company')->get();

        if (!$superAdmin && $isSaas) {
            $this->command->warn('No super admin found. Please run DefaultCompanySeeder first.');
            return;
        }

        if ($isSaas) {
            // SaaS mode: Create media for both super admin and companies
            if ($superAdmin) {
                $this->createSuperAdminMedia($superAdmin);
            }
            foreach ($companies as $company) {
                $this->createCompanyMedia($company);
            }
        } else {
            // Non-SaaS mode: Create media only for companies
            foreach ($companies as $company) {
                $this->createCompanyMedia($company);
            }
        }

        $this->command->info('MediaItem seeder completed successfully!');
    }

    /**
     * Create media directories and items for Super Admin
     */
    private function createSuperAdminMedia($superAdmin)
    {
        // Create main system directories for Super Admin only
        $directories = [
            ['name' => 'System Templates', 'slug' => 'system-templates'],
            ['name' => 'System Documentation', 'slug' => 'system-documentation'],
            ['name' => 'Default Assets', 'slug' => 'default-assets']
        ];

        foreach ($directories as $dirData) {
            if (!MediaDirectory::where('slug', $dirData['slug'])->where('created_by', $superAdmin->id)->exists()) {
                MediaDirectory::create([
                    'name' => $dirData['name'],
                    'slug' => $dirData['slug'],
                    'parent_id' => null,
                    'created_by' => $superAdmin->id,
                ]);
            }
        }

        // Create system media items for Super Admin
        $mediaItems = [
            ['collection_name' => 'system', 'name' => 'Default System Logo', 'file_name' => 'system-default-logo.png', 'mime_type' => 'image/png', 'size' => 15360, 'directory' => 'default-assets'],
            ['collection_name' => 'templates', 'name' => 'System Template Image', 'file_name' => 'system-template-image.png', 'mime_type' => 'image/png', 'size' => 25600, 'directory' => 'system-templates'],
            ['collection_name' => 'documentation', 'name' => 'System Documentation', 'file_name' => 'system-user-manual.pdf', 'mime_type' => 'application/pdf', 'size' => 1048576, 'directory' => 'system-documentation'],
            ['collection_name' => 'system', 'name' => 'System Banner', 'file_name' => 'system-banner.png', 'mime_type' => 'image/png', 'size' => 204800, 'directory' => 'default-assets'],
            ['collection_name' => 'system', 'name' => 'System Icon Set', 'file_name' => 'system-icons.png', 'mime_type' => 'image/png', 'size' => 51200, 'directory' => 'default-assets'],
            ['collection_name' => 'templates', 'name' => 'Email Header Template', 'file_name' => 'email-header-template.png', 'mime_type' => 'image/png', 'size' => 30720, 'directory' => 'system-templates'],
            ['collection_name' => 'templates', 'name' => 'Invoice Template', 'file_name' => 'invoice-template.png', 'mime_type' => 'image/png', 'size' => 40960, 'directory' => 'system-templates'],
            ['collection_name' => 'documentation', 'name' => 'API Documentation', 'file_name' => 'api-documentation.pdf', 'mime_type' => 'application/pdf', 'size' => 512000, 'directory' => 'system-documentation'],
            ['collection_name' => 'system', 'name' => 'Default Avatar', 'file_name' => 'default-avatar.png', 'mime_type' => 'image/png', 'size' => 20480, 'directory' => 'default-assets'],
            ['collection_name' => 'system', 'name' => 'Loading Animation', 'file_name' => 'loading-animation.png', 'mime_type' => 'image/png', 'size' => 102400, 'directory' => 'default-assets'],
            ['collection_name' => 'templates', 'name' => 'Report Template', 'file_name' => 'report-template.png', 'mime_type' => 'image/png', 'size' => 35840, 'directory' => 'system-templates'],
            ['collection_name' => 'system', 'name' => 'Error Page Image', 'file_name' => 'error-page.png', 'mime_type' => 'image/png', 'size' => 81920, 'directory' => 'default-assets'],
            ['collection_name' => 'system', 'name' => 'Success Icon', 'file_name' => 'success-icon.png', 'mime_type' => 'image/png', 'size' => 12288, 'directory' => 'default-assets'],
            ['collection_name' => 'documentation', 'name' => 'Installation Guide', 'file_name' => 'installation-guide.pdf', 'mime_type' => 'application/pdf', 'size' => 256000, 'directory' => 'system-documentation'],
            ['collection_name' => 'templates', 'name' => 'Certificate Template', 'file_name' => 'certificate-template.png', 'mime_type' => 'image/png', 'size' => 122880, 'directory' => 'system-templates']
        ];

        foreach ($mediaItems as $mediaData) {
            $directory = MediaDirectory::where('slug', $mediaData['directory'])->where('created_by', $superAdmin->id)->first();

            if (!Media::where('file_name', $mediaData['file_name'])->where('created_by', $superAdmin->id)->exists()) {
                $media = new Media();
                $media->model_type = User::class;
                $media->model_id = $superAdmin->id;
                $media->uuid = Str::uuid();
                $media->collection_name = $mediaData['collection_name'];
                $media->name = $mediaData['name'];
                $media->file_name = $mediaData['file_name'];
                $media->mime_type = $mediaData['mime_type'];
                $media->disk = 'public';
                $media->conversions_disk = 'public';
                $media->size = $mediaData['size'];
                $media->manipulations = [];
                $media->custom_properties = [];
                $media->generated_conversions = [];
                $media->responsive_images = [];
                $media->order_column = 1;
                $media->directory_id = $directory?->id;
                $media->created_by = $superAdmin->id;
                $media->saveQuietly();
            }
        }
    }

    /**
     * Create media directories and items for Company
     */
    private function createCompanyMedia($company)
    {
        // Create organized directories for Company with company name prefix
        $companyName = str_replace(' ', '-', strtolower($company->name));
        $directories = [
            ['name' => $company->name . ' - Assets', 'slug' => $companyName . '-assets'],
            ['name' => $company->name . ' - Documents', 'slug' => $companyName . '-documents'],
            ['name' => $company->name . ' - Branding', 'slug' => $companyName . '-branding']
        ];

        foreach ($directories as $dirData) {
            if (!MediaDirectory::where('slug', $dirData['slug'])->where('created_by', $company->id)->exists()) {
                MediaDirectory::create([
                    'name' => $dirData['name'],
                    'slug' => $dirData['slug'],
                    'parent_id' => null,
                    'created_by' => $company->id,
                ]);
            }
        }

        // Create organized media items for Company
        $companyName = str_replace(' ', '-', strtolower($company->name));
        $mediaItems = [
            ['collection_name' => 'branding', 'name' => $company->name . ' Logo', 'file_name' => $companyName . '-logo.png', 'mime_type' => 'image/png', 'size' => 25600, 'directory' => $companyName . '-branding'],
            ['collection_name' => 'documents', 'name' => $company->name . ' Employee Handbook', 'file_name' => $companyName . '-employee-handbook.pdf', 'mime_type' => 'application/pdf', 'size' => 512000, 'directory' => $companyName . '-documents'],
            ['collection_name' => 'assets', 'name' => $company->name . ' Office Photo', 'file_name' => $companyName . '-office-photo.png', 'mime_type' => 'image/png', 'size' => 307200, 'directory' => $companyName . '-assets'],
            ['collection_name' => 'branding', 'name' => $company->name . ' Business Card', 'file_name' => $companyName . '-business-card.png', 'mime_type' => 'image/png', 'size' => 40960, 'directory' => $companyName . '-branding'],
            ['collection_name' => 'branding', 'name' => $company->name . ' Letterhead', 'file_name' => $companyName . '-letterhead.png', 'mime_type' => 'image/png', 'size' => 61440, 'directory' => $companyName . '-branding'],
            ['collection_name' => 'documents', 'name' => $company->name . ' Policy Document', 'file_name' => $companyName . '-policy-document.pdf', 'mime_type' => 'application/pdf', 'size' => 256000, 'directory' => $companyName . '-documents'],
            ['collection_name' => 'documents', 'name' => $company->name . ' Training Manual', 'file_name' => $companyName . '-training-manual.pdf', 'mime_type' => 'application/pdf', 'size' => 768000, 'directory' => $companyName . '-documents'],
            ['collection_name' => 'assets', 'name' => $company->name . ' Team Photo', 'file_name' => $companyName . '-team-photo.png', 'mime_type' => 'image/png', 'size' => 409600, 'directory' => $companyName . '-assets'],
            ['collection_name' => 'assets', 'name' => $company->name . ' Building Exterior', 'file_name' => $companyName . '-building-exterior.png', 'mime_type' => 'image/png', 'size' => 512000, 'directory' => $companyName . '-assets'],
            ['collection_name' => 'branding', 'name' => $company->name . ' Social Media Banner', 'file_name' => $companyName . '-social-banner.png', 'mime_type' => 'image/png', 'size' => 153600, 'directory' => $companyName . '-branding']
        ];

        foreach ($mediaItems as $mediaData) {
            $directory = MediaDirectory::where('slug', $mediaData['directory'])->where('created_by', $company->id)->first();

            if (!Media::where('file_name', $mediaData['file_name'])->where('created_by', $company->id)->exists()) {
                Media::create([
                    'model_type' => User::class,
                    'model_id' => $company->id,
                    'uuid' => Str::uuid(),
                    'collection_name' => $mediaData['collection_name'],
                    'name' => $mediaData['name'],
                    'file_name' => $mediaData['file_name'],
                    'mime_type' => $mediaData['mime_type'],
                    'disk' => 'public',
                    'conversions_disk' => 'public',
                    'size' => $mediaData['size'],
                    'manipulations' => [],
                    'custom_properties' => [],
                    'generated_conversions' => [],
                    'responsive_images' => [],
                    'order_column' => 1,
                    'directory_id' => $directory?->id,
                    'created_by' => $company->id,
                ]);
            }
        }
    }
}
