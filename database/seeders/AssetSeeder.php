<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        $currentYear = date('Y');

        // Fixed assets data for consistent results
        $assetsData = [
            'Computer Hardware' => [
                ['name' => 'Dell OptiPlex 7090', 'serial' => 'DL001', 'cost' => 85000, 'condition' => 'new'],
                ['name' => 'HP EliteBook 840', 'serial' => 'HP001', 'cost' => 95000, 'condition' => 'good'],
                ['name' => 'Lenovo ThinkPad X1', 'serial' => 'LN001', 'cost' => 120000, 'condition' => 'new'],
                ['name' => 'MacBook Pro 16"', 'serial' => 'AP001', 'cost' => 250000, 'condition' => 'good']
            ],
            'Mobile Devices' => [
                ['name' => 'iPhone 14 Pro', 'serial' => 'IP001', 'cost' => 130000, 'condition' => 'new'],
                ['name' => 'Samsung Galaxy S23', 'serial' => 'SM001', 'cost' => 80000, 'condition' => 'good'],
                ['name' => 'iPad Air 5th Gen', 'serial' => 'ID001', 'cost' => 60000, 'condition' => 'new']
            ],
            'Office Equipment' => [
                ['name' => 'Canon ImageRunner Printer', 'serial' => 'CN001', 'cost' => 45000, 'condition' => 'good'],
                ['name' => 'Epson EcoTank Printer', 'serial' => 'EP001', 'cost' => 25000, 'condition' => 'new'],
                ['name' => 'Brother MFC Scanner', 'serial' => 'BR001', 'cost' => 35000, 'condition' => 'fair']
            ],
            'Furniture' => [
                ['name' => 'Executive Office Desk', 'serial' => 'FU001', 'cost' => 15000, 'condition' => 'good'],
                ['name' => 'Ergonomic Office Chair', 'serial' => 'FU002', 'cost' => 12000, 'condition' => 'new'],
                ['name' => 'Conference Table 12-Seater', 'serial' => 'FU003', 'cost' => 35000, 'condition' => 'good']
            ],
            'Vehicles' => [
                ['name' => 'Toyota Camry 2023', 'serial' => 'TC001', 'cost' => 3500000, 'condition' => 'new'],
                ['name' => 'Honda City 2022', 'serial' => 'HC001', 'cost' => 1800000, 'condition' => 'good']
            ],
            'Network Equipment' => [
                ['name' => 'Cisco Catalyst Switch', 'serial' => 'CS001', 'cost' => 75000, 'condition' => 'new'],
                ['name' => 'TP-Link Wireless Router', 'serial' => 'TP001', 'cost' => 8000, 'condition' => 'good']
            ]
        ];

        foreach ($companies as $company) {
            // Get asset types for this company
            $assetTypes = AssetType::where('created_by', $company->id)->get();

            if ($assetTypes->isEmpty()) {
                $this->command->warn('No asset types found for company: ' . $company->name . '. Please run AssetTypeSeeder first.');
                continue;
            }

            // Get employees for assignments
            $employees = User::where('type', 'employee')->where('created_by', $company->id)->get();

            foreach ($assetTypes as $assetType) {
                $typeAssets = $assetsData[$assetType->name] ?? [];

                foreach ($typeAssets as $index => $assetData) {
                    $assetCode = strtoupper(substr($assetType->name, 0, 3)) . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

                    // Check if asset already exists
                    if (Asset::where('asset_code', $assetCode)->where('created_by', $company->id)->exists()) {
                        continue;
                    }

                    try {
                        $asset = Asset::create([
                            'name' => $assetData['name'],
                            'asset_type_id' => $assetType->id,
                            'serial_number' => $assetData['serial'],
                            'asset_code' => $assetCode,
                            'purchase_date' => $currentYear . '-01-15',
                            'purchase_cost' => $assetData['cost'],
                            'status' => $index % 3 === 0 ? 'assigned' : 'available',
                            'condition' => $assetData['condition'],
                            'description' => 'Standard ' . $assetType->name . ' for business operations',
                            'location' => 'Main Office',
                            'supplier' => 'Tech Solutions Pvt Ltd',
                            'warranty_info' => '2 Year Manufacturer Warranty',
                            'warranty_expiry_date' => ($currentYear + 2) . '-01-15',
                            'images' => null,
                            'documents' => null,
                            'qr_code' => null,
                            'created_by' => $company->id,
                        ]);

                        // Create asset assignment for assigned assets
                        if ($asset->status === 'assigned' && $employees->isNotEmpty()) {
                            $this->createAssetAssignment($asset, $employees->first(), $company);
                        }

                        // Create asset depreciation
                        $this->createAssetDepreciation($asset, $company);

                        // Create maintenance record for all assets
                        $this->createAssetMaintenance($asset, $company, $index);
                    } catch (\Exception $e) {
                        $this->command->error('Failed to create asset: ' . $assetData['name'] . ' for company: ' . $company->name);
                        continue;
                    }
                }
            }
        }

        $this->command->info('Asset seeder completed successfully!');
    }

    /**
     * Create asset assignment
     */
    private function createAssetAssignment($asset, $employee, $company)
    {
        try {
            DB::table('asset_assignments')->insert([
                'asset_id' => $asset->id,
                'employee_id' => $employee->id,
                'checkout_date' => date('Y-m-d'),
                'expected_return_date' => null,
                'checkin_date' => null,
                'checkout_condition' => $asset->condition,
                'checkin_condition' => null,
                'notes' => 'Asset assigned for regular business use',
                'is_acknowledged' => true,
                'acknowledged_at' => now(),
                'assigned_by' => $company->id,
                'received_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Continue on error
        }
    }

    /**
     * Create asset depreciation
     */
    private function createAssetDepreciation($asset, $company)
    {
        $usefulLife = match ($asset->assetType->name) {
            'Computer Hardware', 'Mobile Devices' => 3,
            'Office Equipment', 'Network Equipment' => 5,
            'Furniture' => 10,
            'Vehicles' => 8,
            default => 5
        };

        $salvageValue = $asset->purchase_cost * 0.1; // 10% salvage value
        $currentValue = $asset->purchase_cost - (($asset->purchase_cost - $salvageValue) / $usefulLife);

        try {
            DB::table('asset_depreciations')->insert([
                'asset_id' => $asset->id,
                'method' => 'straight_line',
                'useful_life_years' => $usefulLife,
                'salvage_value' => $salvageValue,
                'current_value' => $currentValue,
                'last_calculated_date' => date('Y-m-d'),
                'created_by' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Continue on error
        }
    }

    /**
     * Create asset maintenance
     */
    private function createAssetMaintenance($asset, $company, $index)
    {
        try {
            DB::table('asset_maintenances')->insert([
                'asset_id' => $asset->id,
                'maintenance_type' => 'preventive',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d', strtotime('+7 days')),
                'cost' => $asset->purchase_cost * 0.05, // 5% of purchase cost
                'status' => ['scheduled', 'in_progress', 'completed', 'cancelled'][$index % 4],
                'details' => 'Regular preventive maintenance and system updates',
                'completion_notes' => 'Maintenance completed successfully, asset in good condition',
                'supplier' => 'Maintenance Services Ltd',
                'created_by' => $company->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Continue on error
        }
    }
}
