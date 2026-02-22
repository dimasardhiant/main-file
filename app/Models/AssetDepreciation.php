<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDepreciation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'method',
        'useful_life_years',
        'salvage_value',
        'current_value',
        'last_calculated_date',
        'created_by'
    ];

    protected $casts = [
        'last_calculated_date' => 'date',
        'salvage_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'useful_life_years' => 'integer',
    ];

    /**
     * Get the asset that is being depreciated.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user who created this depreciation record.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate the current value of the asset.
     */
    public function calculateCurrentValue()
    {
        $asset = $this->asset;
        $purchaseDate = $asset->purchase_date;
        $purchaseCost = $asset->purchase_cost;
        $today = now();
        
        if (!$purchaseDate || !$purchaseCost) {
            return $purchaseCost;
        }
        
        $ageInYears = $purchaseDate->diffInDays($today) / 365;
        
        if ($this->method === 'straight_line') {
            // Straight-line depreciation
            $annualDepreciation = ($purchaseCost - $this->salvage_value) / $this->useful_life_years;
            $totalDepreciation = min($ageInYears, $this->useful_life_years) * $annualDepreciation;
            $currentValue = $purchaseCost - $totalDepreciation;
        } elseif ($this->method === 'reducing_balance') {
            // Reducing balance depreciation
            $depreciationRate = 1 - pow($this->salvage_value / $purchaseCost, 1 / $this->useful_life_years);
            $currentValue = $purchaseCost * pow(1 - $depreciationRate, min($ageInYears, $this->useful_life_years));
        } else {
            // Default to straight-line if method is not recognized
            $annualDepreciation = ($purchaseCost - $this->salvage_value) / $this->useful_life_years;
            $totalDepreciation = min($ageInYears, $this->useful_life_years) * $annualDepreciation;
            $currentValue = $purchaseCost - $totalDepreciation;
        }
        
        // Ensure current value doesn't go below salvage value
        return max($currentValue, $this->salvage_value);
    }

    /**
     * Update the current value of the asset.
     */
    public function updateCurrentValue()
    {
        $this->current_value = $this->calculateCurrentValue();
        $this->last_calculated_date = now();
        $this->save();
        
        return $this->current_value;
    }
}