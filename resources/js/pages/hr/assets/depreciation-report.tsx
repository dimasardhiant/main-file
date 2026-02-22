// pages/hr/assets/depreciation-report.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { List, BarChart, Download, Printer } from 'lucide-react';
import { format } from 'date-fns';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

export default function DepreciationReport() {
  const { t } = useTranslation();
  const { 
    assets, 
    assetTypes, 
    totalPurchaseValue, 
    totalCurrentValue, 
    totalDepreciation,
    filters: pageFilters = {} 
  } = usePage().props as any;
  
  // State
  const [selectedAssetType, setSelectedAssetType] = useState(pageFilters.asset_type_id || '');
  const [purchaseDateFrom, setPurchaseDateFrom] = useState(pageFilters.purchase_date_from || '');
  const [purchaseDateTo, setPurchaseDateTo] = useState(pageFilters.purchase_date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  
  const handleViewAssets = () => {
    router.get(route('hr.assets.index'));
  };
  
  const handleViewDashboard = () => {
    router.get(route('hr.assets.dashboard'));
  };
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedAssetType !== '' || 
           purchaseDateFrom !== '' || 
           purchaseDateTo !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedAssetType !== '' ? 1 : 0) + 
           (purchaseDateFrom !== '' ? 1 : 0) + 
           (purchaseDateTo !== '' ? 1 : 0);
  };
  
  const applyFilters = () => {
    router.get(route('hr.assets.depreciation-report'), { 
      page: 1,
      asset_type_id: selectedAssetType || undefined,
      purchase_date_from: purchaseDateFrom || undefined,
      purchase_date_to: purchaseDateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.assets.depreciation-report'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      asset_type_id: selectedAssetType || undefined,
      purchase_date_from: purchaseDateFrom || undefined,
      purchase_date_to: purchaseDateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleResetFilters = () => {
    setSelectedAssetType('');
    setPurchaseDateFrom('');
    setPurchaseDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.assets.depreciation-report'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handlePrint = () => {
    window.print();
  };
  
  const handleExportCSV = () => {
    const params = new URLSearchParams({
      ...(selectedAssetType && { asset_type_id: selectedAssetType }),
      ...(purchaseDateFrom && { purchase_date_from: purchaseDateFrom }),
      ...(purchaseDateTo && { purchase_date_to: purchaseDateTo })
    });
    
    window.open(`${route('hr.assets.export-depreciation-csv')}?${params.toString()}`, '_blank');
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
      label: t('Dashboard'),
      icon: <BarChart className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewDashboard
    },
    {
      label: t('Print'),
      icon: <Printer className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handlePrint,
      className: 'print:hidden'
    },
    {
      label: t('Export CSV'),
      icon: <Download className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleExportCSV,
      className: 'print:hidden'
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.assets.index') },
    { title: t('Asset Management'), href: route('hr.assets.index') },
    { title: t('Depreciation Report') }
  ];
  
  // Prepare asset type options for filter
  const assetTypeOptions = [
    { value: '', label: t('All Types') },
    ...(assetTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];
  
  // Calculate depreciation percentage
  const calculateDepreciationPercentage = (purchaseCost: number, currentValue: number) => {
    if (!purchaseCost || purchaseCost === 0) return 0;
    return ((purchaseCost - currentValue) / purchaseCost) * 100;
  };
  
  return (
    <PageTemplate 
      title={t("Asset Depreciation Report")} 
      url="/hr/assets/depreciation-report"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Filters section - hidden when printing */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4 print:hidden">
        <SearchAndFilterBar
          searchTerm=""
          onSearchChange={() => {}}
          onSearch={() => {}}
          filters={[
            {
              name: 'asset_type_id',
              label: t('Asset Type'),
              type: 'select',
              value: selectedAssetType,
              onChange: setSelectedAssetType,
              options: assetTypeOptions
            },
            {
              name: 'purchase_date_from',
              label: t('Purchase Date From'),
              type: 'date',
              value: purchaseDateFrom,
              onChange: setPurchaseDateFrom
            },
            {
              name: 'purchase_date_to',
              label: t('Purchase Date To'),
              type: 'date',
              value: purchaseDateTo,
              onChange: setPurchaseDateTo
            }
          ]}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={activeFilterCount}
          onResetFilters={handleResetFilters}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            router.get(route('hr.assets.depreciation-report'), { 
              page: 1, 
              per_page: parseInt(value),
              asset_type_id: selectedAssetType || undefined,
              purchase_date_from: purchaseDateFrom || undefined,
              purchase_date_to: purchaseDateTo || undefined
            }, { preserveState: true, preserveScroll: true });
          }}
          hideSearch={true}
        />
      </div>
      
      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Purchase Value')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{window.appSettings?.formatCurrency(totalPurchaseValue || 0)}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Current Value')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{window.appSettings?.formatCurrency(totalCurrentValue || 0)}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-lg">{t('Total Depreciation')}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{window.appSettings?.formatCurrency(totalDepreciation || 0)}</div>
            <div className="text-sm text-gray-500 mt-1">
              {totalPurchaseValue > 0 ? Math.round((totalDepreciation / totalPurchaseValue) * 100) : 0}% {t('of purchase value')}
            </div>
          </CardContent>
        </Card>
      </div>
      
      {/* Report Table */}
      <Card>
        <CardHeader>
          <CardTitle>{t('Asset Depreciation Details')}</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{t('Asset Name')}</TableHead>
                <TableHead>{t('Purchase Date')}</TableHead>
                <TableHead className="text-right">{t('Purchase Cost')}</TableHead>
                <TableHead>{t('Depreciation Method')}</TableHead>
                <TableHead className="text-right">{t('Current Value')}</TableHead>
                <TableHead className="text-right">{t('Depreciation')}</TableHead>
                <TableHead className="text-right">{t('Depreciation %')}</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {assets.data && assets.data.length > 0 ? (
                assets.data.map((asset: any) => {
                  const purchaseCost = parseFloat(asset.purchase_cost || 0);
                  const currentValue = parseFloat(asset.depreciation?.current_value || 0);
                  const depreciation = purchaseCost - currentValue;
                  const depreciationPercentage = calculateDepreciationPercentage(purchaseCost, currentValue);
                  
                  return (
                    <TableRow key={asset.id}>
                      <TableCell className="font-medium">{asset.name}</TableCell>
                      <TableCell>
                        {asset.purchase_date ? (window.appSettings?.formatDateTimeSimple(asset.purchase_date, false) || format(new Date(asset.purchase_date), 'MMM dd, yyyy')) : '-'}
                      </TableCell>
                      <TableCell className="text-right">{window.appSettings?.formatCurrency(purchaseCost)}</TableCell>
                      <TableCell>
                        {asset.depreciation?.method === 'straight_line' ? t('Straight Line') : 
                         asset.depreciation?.method === 'reducing_balance' ? t('Reducing Balance') : '-'}
                      </TableCell>
                      <TableCell className="text-right">{window.appSettings?.formatCurrency(currentValue)}</TableCell>
                      <TableCell className="text-right">{window.appSettings?.formatCurrency(depreciation)}</TableCell>
                      <TableCell className="text-right">{depreciationPercentage.toFixed(2)}%</TableCell>
                    </TableRow>
                  );
                })
              ) : (
                <TableRow>
                  <TableCell colSpan={7} className="text-center py-4">
                    {t('No assets with depreciation data found')}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
      
      {/* Pagination - hidden when printing */}
      <div className="mt-4 print:hidden">
        <Pagination
          from={assets?.from || 0}
          to={assets?.to || 0}
          total={assets?.total || 0}
          links={assets?.links}
          entityName={t("assets")}
          onPageChange={(url) => router.get(url)}
        />
      </div>
    </PageTemplate>
  );
}