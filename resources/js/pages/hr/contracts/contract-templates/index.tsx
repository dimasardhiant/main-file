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
import { Plus, FileText, Code, Eye, Star, Download } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';

export default function ContractTemplates() {
  const { t } = useTranslation();
  const { auth, contractTemplates, contractTypes, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [typeFilter, setTypeFilter] = useState(pageFilters.contract_type_id || '_empty_');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [defaultFilter, setDefaultFilter] = useState(pageFilters.is_default || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
  const [isGenerateModalOpen, setIsGenerateModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [variableValues, setVariableValues] = useState<Record<string, string>>({});

  const hasActiveFilters = () => {
    return typeFilter !== '_empty_' || statusFilter !== '_empty_' || defaultFilter !== '_empty_' || searchTerm !== '';
  };

  const activeFilterCount = () => {
    return (typeFilter !== '_empty_' ? 1 : 0) + (statusFilter !== '_empty_' ? 1 : 0) + (defaultFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.contracts.contract-templates.index'), {
      page: 1,
      search: searchTerm || undefined,
      contract_type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      is_default: defaultFilter !== '_empty_' ? defaultFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.contracts.contract-templates.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      contract_type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      is_default: defaultFilter !== '_empty_' ? defaultFilter : undefined,
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

  const handleGenerate = (item: any, variables: Record<string, string>) => {
    fetch(route('hr.contracts.contract-templates.generate', item.id), {
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

  const handleFormSubmit = (formData: any) => {
    // Convert comma-separated strings to arrays
    if (formData.variables && typeof formData.variables === 'string') {
      formData.variables = formData.variables.split(',').map((item: string) => item.trim()).filter(Boolean);
    }
    if (formData.clauses && typeof formData.clauses === 'string') {
      formData.clauses = formData.clauses.split(',').map((item: string) => item.trim()).filter(Boolean);
    }

    if (formMode === 'create') {
      toast.loading(t('Creating contract template...'));

      router.post(route('hr.contracts.contract-templates.store'), formData, {
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
            toast.error(`Failed to create contract template: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating contract template...'));

      router.put(route('hr.contracts.contract-templates.update', currentItem.id), formData, {
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
            toast.error(`Failed to update contract template: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting contract template...'));

    router.delete(route('hr.contracts.contract-templates.destroy', currentItem.id), {
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
          toast.error(`Failed to delete contract template: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleToggleStatus = (item: any) => {
    const newStatus = item.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} contract template...`);

    router.put(route('hr.contracts.contract-templates.toggle-status', item.id), {}, {
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
          toast.error(`Failed to update contract template status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setTypeFilter('_empty_');
    setStatusFilter('_empty_');
    setDefaultFilter('_empty_');
    setShowFilters(false);

    router.get(route('hr.contracts.contract-templates.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];

  if (hasPermission(permissions, 'create-contract-templates')) {
    pageActions.push({
      label: t('Add Template'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Contract Management'), href: route('hr.contracts.contract-templates.index') },
    { title: t('Contract Templates') }
  ];

  const columns = [
    {
      key: 'name',
      label: t('Template Name'),
      sortable: true,
      render: (value, row) => (
        <div className="flex items-center gap-2">
          <FileText className="h-4 w-4 text-gray-500" />
          <div>
            <div className="font-medium flex items-center gap-2">
              {value}
              {row.is_default && (
                <Star className="h-4 w-4 text-yellow-500 fill-current" />
              )}
            </div>
            <div className="text-xs text-gray-500">{row.description}</div>
          </div>
        </div>
      )
    },
    {
      key: 'contract_type.name',
      label: t('Contract Type'),
      render: (_, row) => row.contract_type?.name || '-'
    },
    {
      key: 'variables',
      label: t('Variables'),
      render: (value) => {
        if (!value || !Array.isArray(value) || value.length === 0) return '-';
        return (
          <div className="flex items-center gap-1">
            <Code className="h-4 w-4 text-gray-500" />
            <span>{value.length} {t('variables')}</span>
          </div>
        );
      }
    },
    {
      key: 'clauses',
      label: t('Clauses'),
      render: (value) => {
        if (!value || !Array.isArray(value) || value.length === 0) return '-';
        return (
          <span>{value.length} {t('clauses')}</span>
        );
      }
    },
    {
      key: 'template_content',
      label: t('Content Length'),
      render: (value) => (
        <span className="text-sm text-gray-600">
          {value ? `${value.length} ${t('characters')}` : '-'}
        </span>
      )
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${value === 'active'
            ? 'bg-green-50 text-green-700 ring-green-600/20'
            : 'bg-red-50 text-red-700 ring-red-600/20'
          }`}>
          {t(value === 'active' ? 'Active' : 'Inactive')}
        </span>
      )
    },
    {
      key: 'created_at',
      label: t('Created'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    }
  ];

  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-contract-templates'
    },
    {
      label: t('Preview'),
      icon: 'FileText',
      action: 'preview',
      className: 'text-purple-500',
      requiredPermission: 'view-contract-templates'
    },
    {
      label: t('Generate Contract'),
      icon: 'Download',
      action: 'generate',
      className: 'text-green-500',
      requiredPermission: 'view-contract-templates'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-contract-templates'
    },
    {
      label: t('Toggle Status'),
      icon: 'Lock',
      action: 'toggle-status',
      className: 'text-amber-500',
      requiredPermission: 'edit-contract-templates'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-contract-templates'
    }
  ];

  const typeOptions = [
    { value: '_empty_', label: t('All Types'), disabled: true },
    ...(contractTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses'), disabled: true },
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  const defaultOptions = [
    { value: '_empty_', label: t('All'), disabled: true },
    { value: 'true', label: t('Default') },
    { value: 'false', label: t('Custom') }
  ];

  const typeSelectOptions = [
    { value: '_empty_', label: t('Select Contract Type') },
    ...(contractTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  return (
    <PageTemplate
      title={t("Contract Templates")}
      url="/hr/contracts/contract-templates"
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
              name: 'contract_type_id',
              label: t('Contract Type'),
              type: 'select',
              value: typeFilter,
              onChange: setTypeFilter,
              options: typeOptions,
              searchable: true
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: statusFilter,
              onChange: setStatusFilter,
              options: statusOptions
            },
            {
              name: 'is_default',
              label: t('Type'),
              type: 'select',
              value: defaultFilter,
              onChange: setDefaultFilter,
              options: defaultOptions
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
            router.get(route('hr.contracts.contract-templates.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              contract_type_id: typeFilter !== '_empty_' ? typeFilter : undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              is_default: defaultFilter !== '_empty_' ? defaultFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={contractTemplates?.data || []}
          from={contractTemplates?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-contract-templates',
            create: 'create-contract-templates',
            edit: 'edit-contract-templates',
            delete: 'delete-contract-templates'
          }}
        />

        <Pagination
          from={contractTemplates?.from || 0}
          to={contractTemplates?.to || 0}
          total={contractTemplates?.total || 0}
          links={contractTemplates?.links}
          entityName={t("contract templates")}
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
              name: 'description',
              label: t('Description'),
              type: 'textarea',
              rows: 2
            },
            {
              name: 'contract_type_id',
              label: t('Contract Type'),
              type: 'select',
              required: true,
              options: typeSelectOptions.filter(opt => opt.value !== '_empty_')
            },
            {
              name: 'template_content',
              label: t('Template Content'),
              type: 'textarea',
              required: true,
              rows: 12,
              helpText: t('Use {{variable_name}} for dynamic content')
            },
            {
              name: 'variables',
              label: t('Variables'),
              type: 'text',
              helpText: t('Comma-separated list of variable names (without {{}})')
            },
            {
              name: 'clauses',
              label: t('Clauses'),
              type: 'text',
              helpText: t('Comma-separated list of contract clauses')
            },
            {
              name: 'is_default',
              label: t('Set as Default for Type'),
              type: 'checkbox',
              helpText: t('Only one template can be default per contract type')
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
          variables: currentItem.variables ? currentItem.variables.join(', ') : '',
          clauses: currentItem.clauses ? currentItem.clauses.join(', ') : ''
        } : null}
        title={
          formMode === 'create'
            ? t('Add Contract Template')
            : formMode === 'edit'
              ? t('Edit Contract Template')
              : t('View Contract Template')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="contract template"
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

      {/* Generate Contract Modal */}
      <Dialog open={isGenerateModalOpen} onOpenChange={setIsGenerateModalOpen}>
        <DialogContent className="max-w-lg max-h-[80vh]">
          <DialogHeader>
            <DialogTitle className="text-lg font-semibold">
              {t('Generate Contract')}
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