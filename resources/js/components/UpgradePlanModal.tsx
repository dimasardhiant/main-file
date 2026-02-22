import React, { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { CheckCircle2, CreditCard, Circle } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Switch } from '@/components/ui/switch';

interface Plan {
  id: number;
  name: string;
  price: string | number;
  duration: string;
  description?: string;
  features?: string[];
  business?: number;
  max_users?: number;
  storage_limit?: string;
  is_active?: boolean;
  is_current?: boolean;
  is_default?: boolean;
}

interface UpgradePlanModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (planId: number, duration: string) => void;
  plans: Plan[];
  currentPlanId?: number;
  companyName: string;
}

export function UpgradePlanModal({
  isOpen,
  onClose,
  onConfirm,
  plans,
  currentPlanId,
  companyName
}: UpgradePlanModalProps) {
  const { t } = useTranslation();
  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);
  const [isYearly, setIsYearly] = useState(false);
  
  // Filter plans based on billing period
  const filteredPlans = plans.filter(plan => {
    const duration = plan.duration.toLowerCase();
    return isYearly ? duration === 'yearly' : duration === 'monthly';
  });

  // Initialize with current plan ID when modal opens
  useEffect(() => {
    if (isOpen && filteredPlans && filteredPlans.length > 0) {
      const currentPlan = filteredPlans.find(plan => plan.is_current === true);
      
      if (currentPlan) {
        setSelectedPlanId(currentPlan.id);
      } else if (currentPlanId) {
        const planExists = filteredPlans.find(plan => plan.id === currentPlanId);
        setSelectedPlanId(planExists ? currentPlanId : filteredPlans[0].id);
      } else {
        setSelectedPlanId(filteredPlans[0].id);
      }
    }
  }, [isOpen, plans, isYearly]);

  // Reset selected plan when switching billing periods if current selection is not available
  useEffect(() => {
    if (filteredPlans.length > 0 && selectedPlanId) {
      const currentSelected = filteredPlans.find(plan => plan.id === selectedPlanId);
      if (!currentSelected) {
        setSelectedPlanId(filteredPlans[0].id);
      }
    }
  }, [isYearly]);
  
  const handleConfirm = () => {
    if (selectedPlanId) {
      onConfirm(selectedPlanId, isYearly ? 'yearly' : 'monthly');
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{t("Upgrade Plan for")} {companyName}</DialogTitle>
          <DialogDescription>
            {t("Select a new plan for this company")}
          </DialogDescription>
        </DialogHeader>
        
        <div className="py-4">
          {/* Billing Period Toggle */}
          <div className="flex items-center justify-center space-x-4 mb-6 p-4 bg-gray-50 rounded-lg">
            <span className={`text-sm font-medium ${!isYearly ? 'text-primary' : 'text-gray-500'}`}>
              {t('Monthly')}
            </span>
            <Switch
              checked={isYearly}
              onCheckedChange={setIsYearly}
              className="data-[state=checked]:bg-primary"
            />
            <span className={`text-sm font-medium ${isYearly ? 'text-primary' : 'text-gray-500'}`}>
              {t('Yearly')}
            </span>
            {isYearly && (
              <Badge variant="secondary" className="ml-2 bg-green-100 text-green-800">
                {t('Save up to 20%')}
              </Badge>
            )}
          </div>

          <RadioGroup 
            value={selectedPlanId?.toString() || ""} 
            onValueChange={(value) => setSelectedPlanId(parseInt(value))}
            className="space-y-4"
          >
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto">
              {filteredPlans.length > 0 ? filteredPlans.map((plan) => (
                <div
                  key={plan.id}
                  className={`relative rounded-lg border p-4 cursor-pointer transition-all ${
                    selectedPlanId === plan.id ? 'border-primary bg-primary/5 shadow-md' : 'border-gray-200 hover:border-gray-300'
                  } ${plan.is_current ? 'bg-blue-50' : ''}`}
                  onClick={() => setSelectedPlanId(plan.id)}
                >
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center space-x-2">
                      <RadioGroupItem 
                        value={plan.id.toString()} 
                        id={`plan-${plan.id}`} 
                        className="h-5 w-5"
                      />
                      <h3 className="text-lg font-semibold">{plan.name}</h3>
                    </div>
                    {plan.is_current && (
                      <Badge variant="outline" className="bg-blue-100 text-blue-800 border-blue-200">
                        {t("Current")}
                      </Badge>
                    )}
                  </div>
                  
                  <div className="flex items-center mb-2">
                    <CreditCard className="mr-1.5 h-4 w-4 text-muted-foreground" />
                    <p className="text-xl font-bold text-primary">
                      {window.appSettings?.formatCurrency(plan.price) || `$${plan.price}`}
                    </p>
                    <span className="text-sm text-muted-foreground ml-1">/ {plan.duration.toLowerCase()}</span>
                  </div>
                  
                  {plan.description && (
                    <p className="text-sm text-muted-foreground mb-3">{plan.description}</p>
                  )}
                  
                  {(plan.max_employees || plan.max_users || plan.storage_limit) && (
                    <div className="grid grid-cols-3 gap-2 mb-3 p-2 bg-gray-50 rounded">
                      {plan.max_employees && (
                        <div className="text-center">
                          <div className="font-bold text-gray-900">{plan.max_employees}</div>
                          <div className="text-xs text-gray-500">{t('Employees')}</div>
                        </div>
                      )}
                      {plan.max_users && (
                        <div className="text-center">
                          <div className="font-bold text-gray-900">{plan.max_users}</div>
                          <div className="text-xs text-gray-500">{t('Users')}</div>
                        </div>
                      )}
                      {plan.storage_limit && (
                        <div className="text-center">
                          <div className="font-bold text-gray-900">{plan.storage_limit}</div>
                          <div className="text-xs text-gray-500">{t('Storage')}</div>
                        </div>
                      )}
                    </div>
                  )}
                  
                  {plan.features && plan.features.length > 0 && (
                    <div className="space-y-1">
                      {plan.features.slice(0, 4).map((feature, index) => (
                        <div key={`${plan.id}-${index}`} className="flex items-center text-xs text-muted-foreground">
                          <CheckCircle2 className="mr-1 h-3 w-3 text-green-500 flex-shrink-0" />
                          <span className="truncate">{feature}</span>
                        </div>
                      ))}
                      {plan.features.length > 4 && (
                        <div className="text-xs text-gray-400">+{plan.features.length - 4} more features</div>
                      )}
                    </div>
                  )}
                </div>
              )) : (
                <div className="col-span-full text-center py-8 text-gray-500">
                  <p>{t('No plans available for')} {isYearly ? t('yearly') : t('monthly')} {t('billing')}</p>
                </div>
              )}
            </div>
          </RadioGroup>
        </div>
        
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>
            {t("Cancel")}
          </Button>
          <Button 
            onClick={handleConfirm} 
            disabled={!selectedPlanId || filteredPlans.length === 0}
          >
            {t("Upgrade Plan")}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}