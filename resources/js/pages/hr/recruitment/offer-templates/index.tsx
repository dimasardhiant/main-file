import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Plus, X } from 'lucide-react';
import { format } from 'date-fns';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function OfferTemplates() {
  const { t } = useTranslation();
  const { auth, offerTemplates, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
  const [isGenerateModalOpen, setIsGenerateModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [variableValues, setVariableValues] = useState<Record<string, string>>({});
  
  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.recruitment.offer-templates.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.recruitment.offer-templates.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);
    
    switch (action) {
      case 'view':
        setFormMode('view');
        setIsFormModalOpen(true);
        break;
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'toggle-status':
        handleToggleStatus(item);
        break;
      case 'preview':
        setCurrentItem(item);
        setIsPreviewModalOpen(true);
        break;
      case 'generate':
        if (item.variables && item.variables.length > 0) {
          const initialValues = {};
          item.variables.forEach((variable: string) => {
            initialValues[variable] = '';
          });
          setVariableValues(initialValues);
          setCurrentItem(item);
          setIsGenerateModalOpen(true);
        } else {
          handleGenerate(item, {});
        }
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleToggleStatus = (template: any) => {
    const newStatus = template.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} template...`);

    router.put(route('hr.recruitment.offer-templates.toggle-status', template.id), {}, {
      onSuccess: (page) => {
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
          toast.error(errors);
        } else {
          toast.error(`Failed to update template status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleFormSubmit = (formData: any) => {
    // Convert variables string to array if needed
    if (formData.variables && typeof formData.variables === 'string') {
      formData.variables = formData.variables.split(',').map((v: string) => v.trim()).filter(Boolean);
    }

    if (formMode === 'create') {
      toast.loading(t('Creating template...'));

      router.post(route('hr.recruitment.offer-templates.store'), formData, {
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
            toast.error(errors);
          } else {
            toast.error(`Failed to create template: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating template...'));

      router.put(route('hr.recruitment.offer-templates.update', currentItem.id), formData, {
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
            toast.error(errors);
          } else {
            toast.error(`Failed to update template: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting template...'));

    router.delete(route('hr.recruitment.offer-templates.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
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
          toast.error(errors);
        } else {
          toast.error(`Failed to delete template: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleGenerate = (item: any, variables: Record<string, string>) => {
    fetch(route('hr.recruitment.offer-templates.generate', item.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ variables })
    })
    .then(response => response.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${item.name}_${new Date().toISOString().split('T')[0]}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    });
  };

  const handleGenerateSubmit = () => {
    handleGenerate(currentItem, variableValues);
    setIsGenerateModalOpen(false);
    setVariableValues({});
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.recruitment.offer-templates.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-offer-templates')) {
    pageActions.push({
      label: t('Add Template'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.offer-templates.index') },
    { title: t('Offer Templates') }
  ];

  const columns = [
    { 
      key: 'name', 
      label: t('Name'), 
      sortable: true,
      render: (value) => <div className="font-medium">{value}</div>
    },
    { 
      key: 'template_content', 
      label: t('Content Preview'),
      render: (value) => (
        <div className="max-w-xs">
          <div className="text-sm text-gray-600 truncate">
            {value ? value.substring(0, 100) + (value.length > 100 ? '...' : '') : '-'}
          </div>
        </div>
      )
    },
    { 
      key: 'variables', 
      label: t('Variables'),
      render: (value) => {
        if (!value || !Array.isArray(value) || value.length === 0) return '-';
        return (
          <div className="flex flex-wrap gap-1">
            {value.slice(0, 3).map((variable: string, index: number) => (
              <span key={index} className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                {variable}
              </span>
            ))}
            {value.length > 3 && (
              <span className="text-xs text-gray-500">+{value.length - 3} more</span>
            )}
          </div>
        );
      }
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'active'
          ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
          : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
        }`}>
          {value === 'active' ? t('Active') : t('Inactive')}
        </span>
      )
    },
    { 
      key: 'created_at', 
      label: t('Created At'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-offer-templates'
    },
    { 
      label: t('Preview'), 
      icon: 'FileText', 
      action: 'preview', 
      className: 'text-purple-500',
      requiredPermission: 'view-offer-templates'
    },
    { 
      label: t('Generate Offer'), 
      icon: 'Download', 
      action: 'generate', 
      className: 'text-green-500',
      requiredPermission: 'view-offer-templates'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-offer-templates'
    },
    { 
      label: t('Toggle Status'), 
      icon: 'Lock', 
      action: 'toggle-status', 
      className: 'text-amber-500',
      requiredPermission: 'edit-offer-templates'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-offer-templates'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') },
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  return (
    <PageTemplate 
      title={t("Offer Templates")} 
      url="/hr/recruitment/offer-templates"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: statusFilter,
              onChange: setStatusFilter,
              options: statusOptions
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
            router.get(route('hr.recruitment.offer-templates.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={offerTemplates?.data || []}
          from={offerTemplates?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-offer-templates',
            create: 'create-offer-templates',
            edit: 'edit-offer-templates',
            delete: 'delete-offer-templates'
          }}
        />

        <Pagination
          from={offerTemplates?.from || 0}
          to={offerTemplates?.to || 0}
          total={offerTemplates?.total || 0}
          links={offerTemplates?.links}
          entityName={t("offer templates")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { 
              name: 'name', 
              label: t('Template Name'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'template_content', 
              label: t('Template Content'), 
              type: 'textarea', 
              required: true,
              rows: 10,
              helpText: t('Use {{variable_name}} for dynamic content')
            },
            { 
              name: 'variables', 
              label: t('Variables'), 
              type: 'text',
              helpText: t('Comma-separated list of variables (e.g., candidate_name, position, salary)')
            },
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select', 
              required: true,
              options: statusOptions.filter(opt => opt.value !== '_empty_')
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          variables: currentItem.variables ? currentItem.variables.join(', ') : ''
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Offer Template')
            : formMode === 'edit'
              ? t('Edit Offer Template')
              : t('View Offer Template')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="offer template"
      />

      <Dialog open={isPreviewModalOpen} onOpenChange={setIsPreviewModalOpen}>
        <DialogContent className="max-w-4xl max-h-[80vh]">
          <DialogHeader>
            <DialogTitle>
              {t('Template Preview')}: {currentItem?.name}
            </DialogTitle>
          </DialogHeader>
          <div className="mt-4 overflow-y-auto max-h-[60vh] pr-1">
            <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border">
              <pre className="whitespace-pre-wrap text-sm font-mono">
                {currentItem?.template_content || t('No content available')}
              </pre>
            </div>
            {currentItem?.variables && currentItem.variables.length > 0 && (
              <div className="mt-4">
                <h4 className="text-sm font-medium mb-2">{t('Available Variables')}:</h4>
                <div className="flex flex-wrap gap-2">
                  {currentItem.variables.map((variable: string, index: number) => (
                    <span key={index} className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                      {`{{${variable}}}`}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* Generate Offer Modal Popup */}
      <Dialog open={isGenerateModalOpen} onOpenChange={setIsGenerateModalOpen}>
        <DialogContent className="max-w-lg max-h-[80vh]">
          <DialogHeader>
            <DialogTitle className="text-lg font-semibold">
              {t('Generate Offer')}
            </DialogTitle>
            <p className="text-sm text-gray-600">{currentItem?.name}</p>
          </DialogHeader>
          <div className="overflow-y-auto max-h-[50vh] space-y-3 py-2 pr-2">
            {currentItem?.variables?.map((variable: string) => (
              <div key={variable} className="space-y-1">
                <Label htmlFor={variable} className="text-sm font-medium">
                  {variable.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                </Label>
                <Input
                  id={variable}
                  value={variableValues[variable] || ''}
                  onChange={(e) => setVariableValues(prev => ({ ...prev, [variable]: e.target.value }))}
                  placeholder={`Enter ${variable.replace(/_/g, ' ')}`}
                />
              </div>
            ))}
          </div>
          <div className="flex justify-end space-x-2 pt-2 border-t">
            <Button variant="outline" onClick={() => setIsGenerateModalOpen(false)}>
              {t('Cancel')}
            </Button>
            <Button onClick={handleGenerateSubmit} className="bg-red-500 hover:bg-red-600">
              {t('Generate PDF')}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}