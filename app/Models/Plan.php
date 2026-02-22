<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'price',
        'yearly_price',
        'duration',
        'description',
        'max_users',
        'max_employees',
        'enable_chatgpt',
        'storage_limit',
        'is_trial',
        'trial_day',
        'is_plan_enable',
        'is_default',
    ];
    
    protected $casts = [
        'is_default' => 'boolean',
        'price' => 'float',
        'yearly_price' => 'float',
        'max_users' => 'integer',
        'max_employees' => 'integer',
        'trial_day' => 'integer',
    ];
    
    /**
     * Get the default plan
     *
     * @return Plan|null
     */
    public static function getDefaultPlan()
    {
        if (!isSaas()) {
            return null; // No plans in non-SaaS
        }
        return self::where('is_default', true)->first();
    }
    
    /**
     * Check if the plan is the default plan
     *
     * @return bool
     */
    public function isDefault()
    {
        return (bool) $this->is_default;
    }
    
    /**
     * Get the price based on billing cycle
     *
     * @param string $cycle 'monthly' or 'yearly'
     * @return float
     */
    public function getPriceForCycle($cycle = 'monthly')
    {
        if ($cycle === 'yearly' && $this->yearly_price) {
            return $this->yearly_price;
        }
        
        return $this->price;
    }
    
    /**
     * Get users subscribed to this plan
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}