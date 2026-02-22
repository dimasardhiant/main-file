// pages/hr/assets/dashboard.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { List, BarChart, PieChart, Calendar, AlertTriangle, DollarSign } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';

export default function AssetDashboard() {
  const { t } = useTranslation();
  const { 
    assetCounts, 
    assetTypeData, 
    recentAssignments, 
    upcomingMaintenance, 
    expiringWarranties,
    assetValueSummary
  } = usePage().props as any;
  
  const handleViewAssets = () => {
    router.get(route('hr.assets.index'));
  };
  
  const handleViewDepreciationReport = () => {
    router.get(route('hr.assets.depreciation-report'));
  };
  
  // Define page actions
  const pageActions = [
    {
      label: t('Asset List'),
      icon: <List className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewAssets
    },
    {
      label: t('Depreciation Report'),
      icon: <BarChart className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewDepreciationReport
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.assets.index') },
    { title: t('Asset Management'), href: route('hr.assets.index') },
    { title: t('Asset Dashboard') }
  ];
  
  // Status colors for badges
  const statusColors = {
    'available': 'bg-green-50 text-green-700 ring-green-600/20',
    'assigned': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'under_maintenance': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'disposed': 'bg-red-50 text-red-700 ring-red-600/20'
  };
  
  // Status labels
  const statusLabels = {
    'available': t('Available'),
    'assigned': t('Assigned'),
    'under_maintenance': t('Under Maintenance'),
    'disposed': t('Disposed')
  };
  
  return (
    <PageTemplate 
      title={t("Asset Dashboard")} 
      url="/hr/assets/dashboard"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Asset Status Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Assets')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{assetCounts.total}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Available')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-green-600">{assetCounts.available}</div>
            <div className="text-sm text-gray-500 mt-1">
              {assetCounts.total > 0 ? Math.round((assetCounts.available / assetCounts.total) * 100) : 0}% {t('of total')}
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Assigned')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-blue-600">{assetCounts.assigned}</div>
            <div className="text-sm text-gray-500 mt-1">
              {assetCounts.total > 0 ? Math.round((assetCounts.assigned / assetCounts.total) * 100) : 0}% {t('of total')}
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Under Maintenance')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-amber-600">{assetCounts.under_maintenance}</div>
            <div className="text-sm text-gray-500 mt-1">
              {assetCounts.total > 0 ? Math.round((assetCounts.under_maintenance / assetCounts.total) * 100) : 0}% {t('of total')}
            </div>
          </CardContent>
        </Card>
      </div>
      
      {/* Asset Value Summary */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>{t('Asset Value Summary')}</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="flex flex-col">
              <span className="text-sm text-gray-500">{t('Total Purchase Value')}</span>
              <span className="text-2xl font-bold">{window.appSettings?.formatCurrency(assetValueSummary.total_purchase_value || 0)}</span>
            </div>
            <div className="flex flex-col">
              <span className="text-sm text-gray-500">{t('Total Current Value')}</span>
              <span className="text-2xl font-bold">{window.appSettings?.formatCurrency(assetValueSummary.total_current_value || 0)}</span>
            </div>
            <div className="flex flex-col">
              <span className="text-sm text-gray-500">{t('Total Depreciation')}</span>
              <span className="text-2xl font-bold">{window.appSettings?.formatCurrency(assetValueSummary.total_depreciation || 0)}</span>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex justify-between mb-1">
              <span className="text-sm">{t('Depreciation Progress')}</span>
              <span className="text-sm">
                {Number(assetValueSummary.total_purchase_value || 0) > 0 
                  ? Math.round((Number(assetValueSummary.total_depreciation || 0) / Number(assetValueSummary.total_purchase_value || 0)) * 100) 
                  : 0}%
              </span>
            </div>
            <Progress 
              value={Number(assetValueSummary.total_purchase_value || 0) > 0 
                ? (Number(assetValueSummary.total_depreciation || 0) / Number(assetValueSummary.total_purchase_value || 0)) * 100 
                : 0} 
              className="h-2"
            />
          </div>
        </CardContent>
        <CardFooter>
          <Button variant="outline" onClick={handleViewDepreciationReport}>
            <BarChart className="h-4 w-4 mr-2" />
            {t('View Depreciation Report')}
          </Button>
        </CardFooter>
      </Card>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {/* Asset Distribution by Type */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Asset Distribution by Type')}</CardTitle>
          </CardHeader>
          <CardContent>
            {assetTypeData.length > 0 ? (
              <div className="space-y-4">
                {assetTypeData.map((type: any, index: number) => (
                  <div key={index}>
                    <div className="flex justify-between mb-1">
                      <span>{type.name}</span>
                      <span>{type.count}</span>
                    </div>
                    <Progress 
                      value={assetCounts.total > 0 ? (type.count / assetCounts.total) * 100 : 0} 
                      className="h-2"
                    />
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">{t('No asset data available')}</div>
            )}
          </CardContent>
        </Card>
        
        {/* Recent Assignments */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Recent Assignments')}</CardTitle>
          </CardHeader>
          <CardContent>
            {recentAssignments && recentAssignments.length > 0 ? (
              <div className="space-y-4">
                {recentAssignments.map((assignment: any) => (
                  <div key={assignment.id} className="flex items-start justify-between border-b pb-3">
                    <div>
                      <div className="font-medium">{assignment.asset?.name}</div>
                      <div className="text-sm text-gray-500">{t('Assigned to')}: {assignment.employee?.name}</div>
                      <div className="text-xs text-gray-500">
                        {window.appSettings?.formatDateTimeSimple(assignment.checkout_date, false) || format(new Date(assignment.checkout_date), 'MMM dd, yyyy')}
                      </div>
                    </div>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => router.get(route('hr.assets.show', assignment.asset_id))}
                    >
                      {t('View')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">{t('No recent assignments')}</div>
            )}
          </CardContent>
        </Card>
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Upcoming Maintenance */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Upcoming Maintenance')}</CardTitle>
          </CardHeader>
          <CardContent>
            {upcomingMaintenance && upcomingMaintenance.length > 0 ? (
              <div className="space-y-4">
                {upcomingMaintenance.map((maintenance: any) => (
                  <div key={maintenance.id} className="flex items-start justify-between border-b pb-3">
                    <div>
                      <div className="font-medium">{maintenance.asset?.name}</div>
                      <div className="text-sm text-gray-500">{maintenance.maintenance_type}</div>
                      <div className="text-xs text-gray-500">
                        {window.appSettings?.formatDateTimeSimple(maintenance.start_date, false) || format(new Date(maintenance.start_date), 'MMM dd, yyyy')}
                      </div>
                    </div>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => router.get(route('hr.assets.show', maintenance.asset_id))}
                    >
                      {t('View')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">{t('No upcoming maintenance')}</div>
            )}
          </CardContent>
        </Card>
        
        {/* Expiring Warranties */}
        <Card>
          <CardHeader>
            <CardTitle>{t('Expiring Warranties')}</CardTitle>
          </CardHeader>
          <CardContent>
            {expiringWarranties && expiringWarranties.length > 0 ? (
              <div className="space-y-4">
                {expiringWarranties.map((asset: any) => (
                  <div key={asset.id} className="flex items-start justify-between border-b pb-3">
                    <div>
                      <div className="font-medium">{asset.name}</div>
                      <div className="text-sm text-gray-500">{asset.warranty_info}</div>
                      <div className="text-xs text-red-500">
                        {t('Expires')}: {window.appSettings?.formatDateTimeSimple(asset.warranty_expiry_date, false) || format(new Date(asset.warranty_expiry_date), 'MMM dd, yyyy')}
                      </div>
                    </div>
                    <Button 
                      variant="ghost" 
                      size="sm"
                      onClick={() => router.get(route('hr.assets.show', asset.id))}
                    >
                      {t('View')}
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-4 text-gray-500">{t('No expiring warranties')}</div>
            )}
          </CardContent>
        </Card>
      </div>
    </PageTemplate>
  );
}