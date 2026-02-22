// pages/hr/assets/show.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Edit, Trash, UserPlus, ArrowDownLeft, Wrench, QrCode, Download, Image } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { hasPermission } from '@/utils/authorization';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { Progress } from '@/components/ui/progress';
import MediaPicker from '@/components/MediaPicker';

export default function AssetShow() {
  const { t } = useTranslation();
  const { auth, asset, assetTypes, employees } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
  const [isReturnModalOpen, setIsReturnModalOpen] = useState(false);
  const [isMaintenanceModalOpen, setIsMaintenanceModalOpen] = useState(false);
  const [isUpdateMaintenanceModalOpen, setIsUpdateMaintenanceModalOpen] = useState(false);
  const [currentMaintenance, setCurrentMaintenance] = useState<any>(null);
  
  const handleBackToList = () => {
    router.get(route('hr.assets.index'));
  };
  
  const handleEdit = () => {
    setIsFormModalOpen(true);
  };
  
  const handleDelete = () => {
    setIsDeleteModalOpen(true);
  };
  
  const handleAssign = () => {
    setIsAssignModalOpen(true);
  };
  
  const handleReturn = () => {
    setIsReturnModalOpen(true);
  };
  
  const handleMaintenance = () => {
    setIsMaintenanceModalOpen(true);
  };
  
  const handleUpdateMaintenance = (maintenance: any) => {
    setCurrentMaintenance(maintenance);
    setIsUpdateMaintenanceModalOpen(true);
  };
  
  const handleDownloadQrCode = () => {
    window.open(route('hr.assets.download-qrcode', asset.id), '_blank');
  };
  
  const handleDownloadDocument = () => {
    window.open(route('hr.assets.download-document', asset.id), '_blank');
  };
  
  const handleViewImage = () => {
    window.open(route('hr.assets.view-image', asset.id), '_blank');
  };
  
  const handleFormSubmit = (formData: any) => {
    toast.loading(t('Updating asset...'));

    router.put(route('hr.assets.update', asset.id), formData, {
      onSuccess: (page) => {
        setIsFormModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to update asset: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleAssignSubmit = (formData: any) => {
    toast.loading(t('Assigning asset...'));
    
    router.post(route('hr.assets.assign', asset.id), formData, {
      onSuccess: (page) => {
        setIsAssignModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to assign asset: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleReturnSubmit = (formData: any) => {
    toast.loading(t('Returning asset...'));
    
    router.post(route('hr.assets.return', asset.id), formData, {
      onSuccess: (page) => {
        setIsReturnModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to return asset: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleMaintenanceSubmit = (formData: any) => {
    toast.loading(t('Scheduling maintenance...'));
    
    router.post(route('hr.assets.schedule-maintenance', asset.id), formData, {
      onSuccess: (page) => {
        setIsMaintenanceModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to schedule maintenance: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleUpdateMaintenanceSubmit = (formData: any) => {
    toast.loading(t('Updating maintenance...'));
    
    router.put(route('hr.assets.update-maintenance', currentMaintenance.id), formData, {
      onSuccess: (page) => {
        setIsUpdateMaintenanceModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to update maintenance: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting asset...'));
    
    router.delete(route('hr.assets.destroy', asset.id), {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
        router.get(route('hr.assets.index'));
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete asset: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  // Define page actions
  const pageActions = [];
  
  // Add the "Back to List" button
  pageActions.push({
    label: t('Back to List'),
    icon: <ArrowLeft className="h-4 w-4 mr-2" />,
    variant: 'outline' as const,
    onClick: handleBackToList
  });
  
  // Add action buttons based on permissions and asset status
  if (hasPermission(permissions, 'edit-assets')) {
    pageActions.push({
      label: t('Edit'),
      icon: <Edit className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleEdit
    });
  }
  
  if (hasPermission(permissions, 'assign-assets') && asset.status === 'available') {
    pageActions.push({
      label: t('Assign'),
      icon: <UserPlus className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleAssign
    });
  }
  
  if (hasPermission(permissions, 'assign-assets') && asset.status === 'assigned') {
    pageActions.push({
      label: t('Return'),
      icon: <ArrowDownLeft className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleReturn
    });
  }
  
  if (hasPermission(permissions, 'manage-asset-maintenance') && asset.status !== 'disposed') {
    pageActions.push({
      label: t('Maintenance'),
      icon: <Wrench className="h-4 w-4 mr-2" />,
      variant: 'default' as const,
      onClick: handleMaintenance
    });
  }
  
  if (asset.qr_code) {
    pageActions.push({
      label: t('QR Code'),
      icon: <QrCode className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleDownloadQrCode
    });
  }
  
  if (hasPermission(permissions, 'delete-assets') && asset.status !== 'assigned') {
    pageActions.push({
      label: t('Delete'),
      icon: <Trash className="h-4 w-4 mr-2" />,
      variant: 'destructive' as const,
      onClick: handleDelete
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.assets.index') },
    { title: t('Asset Management'), href: route('hr.assets.index') },
    { title: t('Assets'), href: route('hr.assets.index') },
    { title: asset.name }
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
  
  // Condition colors for badges
  const conditionColors = {
    'new': 'bg-green-50 text-green-700 ring-green-600/20',
    'good': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'fair': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'poor': 'bg-red-50 text-red-700 ring-red-600/20'
  };
  
  // Maintenance status colors
  const maintenanceStatusColors = {
    'scheduled': 'bg-blue-50 text-blue-700 ring-blue-600/20',
    'in_progress': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'completed': 'bg-green-50 text-green-700 ring-green-600/20',
    'cancelled': 'bg-red-50 text-red-700 ring-red-600/20'
  };
  
  // Calculate depreciation percentage
  const calculateDepreciationPercentage = () => {
    if (!asset.purchase_cost || asset.purchase_cost === 0 || !asset.depreciation) return 0;
    return ((asset.purchase_cost - asset.depreciation.current_value) / asset.purchase_cost) * 100;
  };
  
  return (
    <PageTemplate 
      title={asset.name} 
      url={`/hr/assets/${asset.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Asset Details */}
        <div className="lg:col-span-2">
          <Card className="mb-6">
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle className="text-2xl">{asset.name}</CardTitle>
                  <CardDescription className="mt-2">
                    {asset.asset_type?.name || t('Unknown Type')}
                  </CardDescription>
                </div>
                <div>
                  <span className={`inline-flex items-center rounded-md px-2 py-1 text-sm font-medium ring-1 ring-inset ${statusColors[asset.status] || ''}`}>
                    {statusLabels[asset.status] || asset.status}
                  </span>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Asset Code')}</h3>
                  <p>{asset.asset_code || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Serial Number')}</h3>
                  <p>{asset.serial_number || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Purchase Date')}</h3>
                  <p>{asset.purchase_date ? (window.appSettings?.formatDateTimeSimple(asset.purchase_date, false) || new Date(asset.purchase_date).toLocaleDateString()) : '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Purchase Cost')}</h3>
                  <p>{window.appSettings?.formatCurrency(asset.purchase_cost) || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Condition')}</h3>
                  <p>
                    {asset.condition ? (
                      <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${conditionColors[asset.condition] || ''}`}>
                        {asset.condition.charAt(0).toUpperCase() + asset.condition.slice(1)}
                      </span>
                    ) : '-'}
                  </p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Location')}</h3>
                  <p>{asset.location || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Supplier')}</h3>
                  <p>{asset.supplier || '-'}</p>
                </div>
                <div>
                  <h3 className="text-sm font-medium text-gray-500">{t('Warranty')}</h3>
                  <p>
                    {asset.warranty_info || '-'}
                    {asset.warranty_expiry_date && (
                      <span className="block text-xs text-gray-500">
                        {t('Expires')}: {window.appSettings?.formatDateTimeSimple(asset.warranty_expiry_date, false) || format(new Date(asset.warranty_expiry_date), 'MMM dd, yyyy')}
                      </span>
                    )}
                  </p>
                </div>
              </div>
              
              {asset.description && (
                <div className="mt-4">
                  <h3 className="text-sm font-medium text-gray-500">{t('Description')}</h3>
                  <p className="mt-1">{asset.description}</p>
                </div>
              )}
              
              {/* Documents and Images */}
              <div className="mt-4 flex flex-wrap gap-2">
                {asset.documents && (
                  <Button 
                    variant="outline" 
                    size="sm"
                    onClick={handleDownloadDocument}
                    className="flex items-center"
                  >
                    <Download className="h-4 w-4 mr-2" />
                    {t('Download Document')}
                  </Button>
                )}
                
                {asset.images && (
                  <Button 
                    variant="outline" 
                    size="sm"
                    onClick={handleViewImage}
                    className="flex items-center"
                  >
                    <Image className="h-4 w-4 mr-2" />
                    {t('View Image')}
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
          
          {/* Tabs for different sections */}
          <Tabs defaultValue="assignments" className="w-full">
            <TabsList className="grid grid-cols-3 mb-4">
              <TabsTrigger value="assignments">{t('Assignments')}</TabsTrigger>
              <TabsTrigger value="maintenance">{t('Maintenance')}</TabsTrigger>
              <TabsTrigger value="depreciation">{t('Depreciation')}</TabsTrigger>
            </TabsList>
            
            {/* Assignments Tab */}
            <TabsContent value="assignments">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Assignment History')}</CardTitle>
                </CardHeader>
                <CardContent>
                  {asset.assignments && asset.assignments.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>{t('Employee')}</TableHead>
                          <TableHead>{t('Checkout Date')}</TableHead>
                          <TableHead>{t('Return Date')}</TableHead>
                          <TableHead>{t('Status')}</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {asset.assignments.map((assignment: any) => (
                          <TableRow key={assignment.id}>
                            <TableCell className="font-medium">
                              {assignment.employee?.name || '-'}
                            </TableCell>
                            <TableCell>
                              {window.appSettings?.formatDateTimeSimple(assignment.checkout_date, false) || format(new Date(assignment.checkout_date), 'MMM dd, yyyy')}
                            </TableCell>
                            <TableCell>
                              {assignment.checkin_date 
                                ? (window.appSettings?.formatDateTimeSimple(assignment.checkin_date, false) || format(new Date(assignment.checkin_date), 'MMM dd, yyyy')) 
                                : assignment.expected_return_date 
                                  ? `${t('Expected')}: ${window.appSettings?.formatDateTimeSimple(assignment.expected_return_date, false) || format(new Date(assignment.expected_return_date), 'MMM dd, yyyy')}` 
                                  : '-'}
                            </TableCell>
                            <TableCell>
                              {assignment.checkin_date 
                                ? <Badge variant="outline" className="bg-green-50 text-green-700">{t('Returned')}</Badge>
                                : <Badge variant="outline" className="bg-blue-50 text-blue-700">{t('Assigned')}</Badge>}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      {t('No assignment history available')}
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
            
            {/* Maintenance Tab */}
            <TabsContent value="maintenance">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Maintenance History')}</CardTitle>
                </CardHeader>
                <CardContent>
                  {asset.maintenances && asset.maintenances.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>{t('Type')}</TableHead>
                          <TableHead>{t('Start Date')}</TableHead>
                          <TableHead>{t('End Date')}</TableHead>
                          <TableHead>{t('Status')}</TableHead>
                          <TableHead>{t('Cost')}</TableHead>
                          <TableHead>{t('Actions')}</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {asset.maintenances.map((maintenance: any) => (
                          <TableRow key={maintenance.id}>
                            <TableCell className="font-medium">
                              {maintenance.maintenance_type}
                            </TableCell>
                            <TableCell>
                              {window.appSettings?.formatDateTimeSimple(maintenance.start_date, false) || format(new Date(maintenance.start_date), 'MMM dd, yyyy')}
                            </TableCell>
                            <TableCell>
                              {maintenance.end_date 
                                ? (window.appSettings?.formatDateTimeSimple(maintenance.end_date, false) || format(new Date(maintenance.end_date), 'MMM dd, yyyy')) 
                                : '-'}
                            </TableCell>
                            <TableCell>
                              <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${maintenanceStatusColors[maintenance.status] || ''}`}>
                                {maintenance.status.charAt(0).toUpperCase() + maintenance.status.slice(1).replace('_', ' ')}
                              </span>
                            </TableCell>
                            <TableCell>
                              {maintenance.cost ? window.appSettings?.formatCurrency(maintenance.cost) : '-'}
                            </TableCell>
                            <TableCell>
                              {(maintenance.status === 'scheduled' || maintenance.status === 'in_progress') && 
                               hasPermission(permissions, 'manage-asset-maintenance') && (
                                <Button 
                                  variant="ghost" 
                                  size="sm"
                                  onClick={() => handleUpdateMaintenance(maintenance)}
                                >
                                  {t('Update')}
                                </Button>
                              )}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      {t('No maintenance history available')}
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
            
            {/* Depreciation Tab */}
            <TabsContent value="depreciation">
              <Card>
                <CardHeader>
                  <CardTitle>{t('Depreciation Information')}</CardTitle>
                </CardHeader>
                <CardContent>
                  {asset.depreciation ? (
                    <div className="space-y-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <h3 className="text-sm font-medium text-gray-500">{t('Depreciation Method')}</h3>
                          <p>
                            {asset.depreciation.method === 'straight_line' ? t('Straight Line') : 
                             asset.depreciation.method === 'reducing_balance' ? t('Reducing Balance') : '-'}
                          </p>
                        </div>
                        <div>
                          <h3 className="text-sm font-medium text-gray-500">{t('Useful Life')}</h3>
                          <p>{asset.depreciation.useful_life_years} {t('years')}</p>
                        </div>
                        <div>
                          <h3 className="text-sm font-medium text-gray-500">{t('Purchase Value')}</h3>
                          <p>{window.appSettings?.formatCurrency(asset.purchase_cost || 0)}</p>
                        </div>
                        <div>
                          <h3 className="text-sm font-medium text-gray-500">{t('Salvage Value')}</h3>
                          <p>{window.appSettings?.formatCurrency(asset.depreciation.salvage_value || 0)}</p>
                        </div>
                        <div>
                          <h3 className="text-sm font-medium text-gray-500">{t('Current Value')}</h3>
                          <p>{window.appSettings?.formatCurrency(asset.depreciation.current_value || 0)}</p>
                        </div>
                        <div>
                          <h3 className="text-sm font-medium text-gray-500">{t('Last Calculated')}</h3>
                          <p>
                            {asset.depreciation.last_calculated_date 
                              ? (window.appSettings?.formatDateTimeSimple(asset.depreciation.last_calculated_date, false) || format(new Date(asset.depreciation.last_calculated_date), 'MMM dd, yyyy')) 
                              : '-'}
                          </p>
                        </div>
                      </div>
                      
                      <div>
                        <h3 className="text-sm font-medium text-gray-500 mb-1">{t('Depreciation Progress')}</h3>
                        <div className="flex justify-between mb-1">
                          <span className="text-xs">{t('Current Value')}</span>
                          <span className="text-xs">{calculateDepreciationPercentage().toFixed(2)}% {t('depreciated')}</span>
                        </div>
                        <Progress 
                          value={calculateDepreciationPercentage()} 
                          className="h-2"
                        />
                      </div>
                    </div>
                  ) : (
                    <div className="text-center py-4 text-gray-500">
                      {t('No depreciation information available')}
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
        
        {/* Current Assignment and QR Code */}
        <div>
          {/* Current Assignment */}
          {asset.status === 'assigned' && asset.current_assignment && (
            <Card className="mb-6">
              <CardHeader>
                <CardTitle>{t('Current Assignment')}</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div>
                    <h3 className="text-sm font-medium text-gray-500">{t('Assigned To')}</h3>
                    <p className="font-medium">{asset.current_assignment.employee?.name || '-'}</p>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-gray-500">{t('Checkout Date')}</h3>
                    <p>{window.appSettings?.formatDateTimeSimple(asset.current_assignment.checkout_date, false) || format(new Date(asset.current_assignment.checkout_date), 'MMM dd, yyyy')}</p>
                  </div>
                  {asset.current_assignment.expected_return_date && (
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Expected Return')}</h3>
                      <p>{window.appSettings?.formatDateTimeSimple(asset.current_assignment.expected_return_date, false) || format(new Date(asset.current_assignment.expected_return_date), 'MMM dd, yyyy')}</p>
                    </div>
                  )}
                  {asset.current_assignment.notes && (
                    <div>
                      <h3 className="text-sm font-medium text-gray-500">{t('Notes')}</h3>
                      <p className="text-sm">{asset.current_assignment.notes}</p>
                    </div>
                  )}
                </div>
              </CardContent>
              {hasPermission(permissions, 'assign-assets') && (
                <CardFooter>
                  <Button 
                    variant="default" 
                    onClick={handleReturn}
                    className="w-full"
                  >
                    <ArrowDownLeft className="h-4 w-4 mr-2" />
                    {t('Return Asset')}
                  </Button>
                </CardFooter>
              )}
            </Card>
          )}
          
          {/* QR Code */}
          {asset.qr_code && (
            <Card>
              <CardHeader>
                <CardTitle>{t('Asset QR Code')}</CardTitle>
              </CardHeader>
              <CardContent className="flex justify-center">
                <img 
                  src={`/storage/${asset.qr_code}`} 
                  alt="Asset QR Code" 
                  className="max-w-full h-auto"
                />
              </CardContent>
              <CardFooter>
                <Button 
                  variant="outline" 
                  onClick={handleDownloadQrCode}
                  className="w-full"
                >
                  <Download className="h-4 w-4 mr-2" />
                  {t('Download QR Code')}
                </Button>
              </CardFooter>
            </Card>
          )}
        </div>
      </div>
      
      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { 
              name: 'name', 
              label: t('Name'), 
              type: 'text',
              required: true
            },
            { 
              name: 'asset_type_id', 
              label: t('Asset Type'), 
              type: 'select',
              required: true,
              options: (assetTypes || []).map((type: any) => ({
                value: type.id.toString(),
                label: type.name
              }))
            },
            { 
              name: 'serial_number', 
              label: t('Serial Number'), 
              type: 'text'
            },
            { 
              name: 'asset_code', 
              label: t('Asset Code'), 
              type: 'text'
            },
            { 
              name: 'purchase_date', 
              label: t('Purchase Date'), 
              type: 'date'
            },
            { 
              name: 'purchase_cost', 
              label: t('Purchase Cost'), 
              type: 'number',
              min: 0,
              step: 0.01
            },
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select',
              required: true,
              options: [
                { value: 'available', label: t('Available') },
                { value: 'assigned', label: t('Assigned') },
                { value: 'under_maintenance', label: t('Under Maintenance') },
                { value: 'disposed', label: t('Disposed') }
              ]
            },
            { 
              name: 'condition', 
              label: t('Condition'), 
              type: 'select',
              options: [
                { value: 'new', label: t('New') },
                { value: 'good', label: t('Good') },
                { value: 'fair', label: t('Fair') },
                { value: 'poor', label: t('Poor') }
              ]
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea'
            },
            { 
              name: 'location', 
              label: t('Location'), 
              type: 'text'
            },
            { 
              name: 'supplier', 
              label: t('Supplier'), 
              type: 'text'
            },
            { 
              name: 'warranty_info', 
              label: t('Warranty Information'), 
              type: 'text'
            },
            { 
              name: 'warranty_expiry_date', 
              label: t('Warranty Expiry Date'), 
              type: 'date'
            },
            { 
              name: 'images', 
              label: t('Images'), 
              type: 'custom',
              render: (field, formData, handleChange) => (
                <MediaPicker
                  value={String(formData[field.name] || '')}
                  onChange={(url) => handleChange(field.name, url)}
                  placeholder={t('Select image file...')}
                />
              ),
              helpText: t('Upload image file (max 5MB)')
            },
            { 
              name: 'documents', 
              label: t('Documents'), 
              type: 'custom',
              render: (field, formData, handleChange) => (
                <MediaPicker
                  value={String(formData[field.name] || '')}
                  onChange={(url) => handleChange(field.name, url)}
                  placeholder={t('Select document file...')}
                />
              ),
              helpText: t('Upload PDF or Word document (max 5MB)')
            },
            { 
              name: 'depreciation_method', 
              label: t('Depreciation Method'), 
              type: 'select',
              options: [
                { value: 'none', label: t('No Depreciation') },
                { value: 'straight_line', label: t('Straight Line') },
                { value: 'reducing_balance', label: t('Reducing Balance') }
              ],
              showWhen: (formData) => formData.purchase_cost && formData.purchase_date
            },
            { 
              name: 'useful_life_years', 
              label: t('Useful Life (Years)'), 
              type: 'number',
              min: 1,
              step: 1,
              showWhen: (formData) => formData.depreciation_method && formData.depreciation_method !== 'none'
            },
            { 
              name: 'salvage_value', 
              label: t('Salvage Value'), 
              type: 'number',
              min: 0,
              step: 0.01,
              showWhen: (formData) => formData.depreciation_method && formData.depreciation_method !== 'none'
            }
          ],
          modalSize: 'lg'
        }}
        initialData={{
          ...asset,
          depreciation_method: asset.depreciation?.method || 'none',
          useful_life_years: asset.depreciation?.useful_life_years || 5,
          salvage_value: asset.depreciation?.salvage_value || 0
        }}
        title={t('Edit Asset')}
        mode="edit"
      />
      
      {/* Assign Modal */}
      <CrudFormModal
        isOpen={isAssignModalOpen}
        onClose={() => setIsAssignModalOpen(false)}
        onSubmit={handleAssignSubmit}
        formConfig={{
          fields: [
            { 
              name: 'employee_id', 
              label: t('Employee'), 
              type: 'select',
              required: true,
              options: (employees || []).map((emp: any) => ({
                value: emp.id.toString(),
                label: `${emp.name} (${emp.employee_id})`
              }))
            },
            { 
              name: 'checkout_date', 
              label: t('Checkout Date'), 
              type: 'date',
              required: true,
              defaultValue: new Date().toISOString().split('T')[0]
            },
            { 
              name: 'expected_return_date', 
              label: t('Expected Return Date'), 
              type: 'date'
            },
            { 
              name: 'checkout_condition', 
              label: t('Checkout Condition'), 
              type: 'select',
              options: [
                { value: 'new', label: t('New') },
                { value: 'good', label: t('Good') },
                { value: 'fair', label: t('Fair') },
                { value: 'poor', label: t('Poor') }
              ],
              defaultValue: asset.condition
            },
            { 
              name: 'notes', 
              label: t('Notes'), 
              type: 'textarea'
            }
          ],
          modalSize: 'md'
        }}
        initialData={{}}
        title={t('Assign Asset')}
        mode="create"
      />
      
      {/* Return Modal */}
      <CrudFormModal
        isOpen={isReturnModalOpen}
        onClose={() => setIsReturnModalOpen(false)}
        onSubmit={handleReturnSubmit}
        formConfig={{
          fields: [
            { 
              name: 'checkin_date', 
              label: t('Check-in Date'), 
              type: 'date',
              required: true,
              defaultValue: new Date().toISOString().split('T')[0]
            },
            { 
              name: 'checkin_condition', 
              label: t('Check-in Condition'), 
              type: 'select',
              options: [
                { value: 'new', label: t('New') },
                { value: 'good', label: t('Good') },
                { value: 'fair', label: t('Fair') },
                { value: 'poor', label: t('Poor') }
              ],
              defaultValue: asset.condition
            },
            { 
              name: 'notes', 
              label: t('Notes'), 
              type: 'textarea'
            }
          ],
          modalSize: 'md'
        }}
        initialData={{}}
        title={t('Return Asset')}
        mode="create"
      />
      
      {/* Maintenance Modal */}
      <CrudFormModal
        isOpen={isMaintenanceModalOpen}
        onClose={() => setIsMaintenanceModalOpen(false)}
        onSubmit={handleMaintenanceSubmit}
        formConfig={{
          fields: [
            { 
              name: 'maintenance_type', 
              label: t('Maintenance Type'), 
              type: 'select',
              required: true,
              options: [
                { value: 'repair', label: t('Repair') },
                { value: 'preventive', label: t('Preventive') },
                { value: 'calibration', label: t('Calibration') },
                { value: 'software update', label: t('Software Update') },
                { value: 'hardware upgrade', label: t('Hardware Upgrade') }
              ]
            },
            { 
              name: 'start_date', 
              label: t('Start Date'), 
              type: 'date',
              required: true,
              defaultValue: new Date().toISOString().split('T')[0]
            },
            { 
              name: 'end_date', 
              label: t('End Date'), 
              type: 'date'
            },
            { 
              name: 'cost', 
              label: t('Cost'), 
              type: 'number',
              min: 0,
              step: 0.01
            },
            { 
              name: 'details', 
              label: t('Details'), 
              type: 'textarea'
            },
            { 
              name: 'supplier', 
              label: t('Supplier'), 
              type: 'text'
            }
          ],
          modalSize: 'md'
        }}
        initialData={{}}
        title={t('Schedule Maintenance')}
        mode="create"
      />
      
      {/* Update Maintenance Modal */}
      <CrudFormModal
        isOpen={isUpdateMaintenanceModalOpen}
        onClose={() => setIsUpdateMaintenanceModalOpen(false)}
        onSubmit={handleUpdateMaintenanceSubmit}
        formConfig={{
          fields: [
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select',
              required: true,
              options: [
                { value: 'scheduled', label: t('Scheduled') },
                { value: 'in_progress', label: t('In Progress') },
                { value: 'completed', label: t('Completed') },
                { value: 'cancelled', label: t('Cancelled') }
              ]
            },
            { 
              name: 'end_date', 
              label: t('End Date'), 
              type: 'date',
              showWhen: (formData) => ['completed', 'cancelled'].includes(formData.status)
            },
            { 
              name: 'completion_notes', 
              label: t('Completion Notes'), 
              type: 'textarea',
              showWhen: (formData) => ['completed', 'cancelled'].includes(formData.status)
            },
            { 
              name: 'cost', 
              label: t('Cost'), 
              type: 'number',
              min: 0,
              step: 0.01
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentMaintenance}
        title={t('Update Maintenance')}
        mode="edit"
      />
      
      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={asset.name}
        entityName="asset"
      />
    </PageTemplate>
  );
}