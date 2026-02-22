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

export default function DocumentTemplates() {
  const { t } = useTranslation();
  const { auth, documentTemplates, categories, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [categoryFilter, setCategoryFilter] = useState(pageFilters.category_id || '_empty_');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [defaultFilter, setDefaultFilter] = useState(pageFilters.is_default || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
  const [isGenerateModalOpen, setIsGenerateModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [placeholderValues, setPlaceholderValues] = useState<Record<string, string>>({});
  
  const hasActiveFilters = () => {
    return categoryFilter !== '_empty_' || statusFilter !== '_empty_' || defaultFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (categoryFilter !== '_empty_' ? 1 : 0) + (statusFilter !== '_empty_' ? 1 : 0) + (defaultFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.documents.document-templates.index'), { 
      page: 1,
      search: searchTerm || undefined,
      category_id: categoryFilter !== '_empty_' ? categoryFilter : undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      is_default: defaultFilter !== '_empty_' ? defaultFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.documents.document-templates.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      category_id: categoryFilter !== '_empty_' ? categoryFilter : undefined,
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
        if (item.placeholders && item.placeholders.length > 0) {
          const initialValues = {};
          item.placeholders.forEach((placeholder: string) => {
            initialValues[placeholder] = item.default_values?.[placeholder] || '';
          });
          setPlaceholderValues(initialValues);
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
  
  const handleGenerate = (item: any, values: Record<string, string>) => {
    fetch(route('hr.documents.document-templates.generate', item.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ values })
    })
    .then(response => response.blob())
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${item.name}_${new Date().toISOString().split('T')[0]}.${item.file_format || 'txt'}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    });
  };

  const handleGenerateSubmit = () => {
    handleGenerate(currentItem, placeholderValues);
    setIsGenerateModalOpen(false);
    setPlaceholderValues({});
  };
  
  const handleFormSubmit = (formData: any) => {
    // Convert comma-separated strings to arrays
    if (formData.placeholders && typeof formData.placeholders === 'string') {
      formData.placeholders = formData.placeholders.split(',').map((item: string) => item.trim()).filter(Boolean);
    }
    
    // Convert default values string to object
    if (formData.default_values && typeof formData.default_values === 'string') {
      try {
        formData.default_values = JSON.parse(formData.default_values);
      } catch (e) {
        // If not valid JSON, create simple key-value pairs
        const pairs = formData.default_values.split(',');
        const obj = {};
        pairs.forEach((pair: string) => {
          const [key, value] = pair.split(':').map((s: string) => s.trim());
          if (key && value) obj[key] = value;
        });
        formData.default_values = obj;
      }
    }

    if (formMode === 'create') {
      toast.loading(t('Creating document template...'));
      
      router.post(route('hr.documents.document-templates.store'), formData, {
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
            toast.error(`Failed to create document template: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating document template...'));
      
      router.put(route('hr.documents.document-templates.update', currentItem.id), formData, {
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
            toast.error(`Failed to update document template: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting document template...'));
    
    router.delete(route('hr.documents.document-templates.destroy', currentItem.id), {
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
          toast.error(`Failed to delete document template: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleToggleStatus = (template: any) => {
    const newStatus = template.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} document template...`);
    
    router.put(route('hr.documents.document-templates.toggle-status', template.id), {}, {
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
          toast.error(`Failed to update document template status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setCategoryFilter('_empty_');
    setStatusFilter('_empty_');
    setDefaultFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.documents.document-templates.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-document-templates')) {
    pageActions.push({
      label: t('Add Template'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Document Management'), href: route('hr.documents.document-templates.index') },
    { title: t('Document Templates') }
  ];

  const columns = [
    { 
      key: 'name', 
      label: t('Template Name'), 
      sortable: true,
      render: (value, row) => (
        <div className="flex items-center gap-3">
          <div 
            className="w-10 h-10 rounded-lg flex items-center justify-center text-white"
            style={{ backgroundColor: row.category?.color || '#3B82F6' }}
          >
            <FileText className="h-5 w-5" />
          </div>
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
      key: 'category.name', 
      label: t('Category'),
      render: (_, row) => row.category?.name || '-'
    },
    { 
      key: 'placeholders', 
      label: t('Placeholders'),
      render: (value) => {
        if (!value || !Array.isArray(value) || value.length === 0) return '-';
        return (
          <div className="flex items-center gap-1">
            <Code className="h-4 w-4 text-gray-500" />
            <span>{value.length} {t('placeholders')}</span>
          </div>
        );
      }
    },
    { 
      key: 'file_format', 
      label: t('Format'),
      render: (value) => (
        <span className="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
          {value.toUpperCase()}
        </span>
      )
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
      render: (value: string) => {
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
            value === 'active'
              ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
              : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
          }`}>
            {value === 'active' ? t('Active') : t('Inactive')}
          </span>
        );
      }
    },
    { 
      key: 'created_at', 
      label: t('Created'),
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
      requiredPermission: 'view-document-templates'
    },
    { 
      label: t('Preview'), 
      icon: 'FileText', 
      action: 'preview', 
      className: 'text-purple-500',
      requiredPermission: 'view-document-templates'
    },
    { 
      label: t('Generate'), 
      icon: 'Download', 
      action: 'generate', 
      className: 'text-green-500',
      requiredPermission: 'view-document-templates'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-document-templates'
    },
    { 
      label: t('Toggle Status'), 
      icon: 'Lock', 
      action: 'toggle-status', 
      className: 'text-amber-500',
      requiredPermission: 'edit-document-templates'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-document-templates'
    }
  ];

  const categoryOptions = [
    { value: '_empty_', label: t('All Categories') },
    ...(categories || []).map((cat: any) => ({
      value: cat.id.toString(),
      label: cat.name
    }))
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') },
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  const defaultOptions = [
    { value: '_empty_', label: t('All') },
    { value: 'true', label: t('Default') },
    { value: 'false', label: t('Custom') }
  ];

  const categorySelectOptions = [
    { value: '_empty_', label: t('Select Category') },
    ...(categories || []).map((cat: any) => ({
      value: cat.id.toString(),
      label: cat.name
    }))
  ];

  const fileFormatOptions = [
    { value: 'pdf', label: 'PDF' },
    { value: 'doc', label: 'DOC' },
    { value: 'docx', label: 'DOCX' },
    { value: 'txt', label: 'TXT' }
  ];

  return (
    <PageTemplate 
      title={t("Document Templates")} 
      url="/hr/documents/document-templates"
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
              name: 'category_id',
              label: t('Category'),
              type: 'select',
              value: categoryFilter,
              onChange: setCategoryFilter,
              options: categoryOptions
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
            router.get(route('hr.documents.document-templates.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              category_id: categoryFilter !== '_empty_' ? categoryFilter : undefined,
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
          data={documentTemplates?.data || []}
          from={documentTemplates?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-document-templates',
            create: 'create-document-templates',
            edit: 'edit-document-templates',
            delete: 'delete-document-templates'
          }}
        />

        <Pagination
          from={documentTemplates?.from || 0}
          to={documentTemplates?.to || 0}
          total={documentTemplates?.total || 0}
          links={documentTemplates?.links}
          entityName={t("document templates")}
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
              name: 'category_id', 
              label: t('Category'), 
              type: 'select', 
              required: true,
              options: categorySelectOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'template_content', 
              label: t('Template Content'), 
              type: 'textarea', 
              required: true,
              rows: 12,
              helpText: t('Use {{placeholder_name}} for dynamic content')
            },
            { 
              name: 'placeholders', 
              label: t('Placeholders'), 
              type: 'text',
              helpText: t('Comma-separated list of placeholder names (without {{}})')
            },
            { 
              name: 'default_values', 
              label: t('Default Values'), 
              type: 'textarea',
              rows: 3,
              helpText: t('JSON object or key:value pairs separated by commas')
            },
            { 
              name: 'file_format', 
              label: t('File Format'), 
              type: 'select',
              options: fileFormatOptions
            },
            { 
              name: 'is_default', 
              label: t('Set as Default for Category'), 
              type: 'checkbox',
              helpText: t('Only one template can be default per category')
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
          placeholders: currentItem.placeholders ? currentItem.placeholders.join(', ') : '',
          default_values: currentItem.default_values ? JSON.stringify(currentItem.default_values, null, 2) : ''
        } : null}
        title={
          formMode === 'create'
            ? t('Add Document Template')
            : formMode === 'edit'
              ? t('Edit Document Template')
              : t('View Document Template')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="document template"
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
            {currentItem?.placeholders && currentItem.placeholders.length > 0 && (
              <div className="mt-4">
                <h4 className="text-sm font-medium mb-2">{t('Available Placeholders')}:</h4>
                <div className="flex flex-wrap gap-2">
                  {currentItem.placeholders.map((placeholder: string, index: number) => (
                    <span key={index} className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                      {`{{${placeholder}}}`}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>

      {/* Generate Document Modal */}
      <Dialog open={isGenerateModalOpen} onOpenChange={setIsGenerateModalOpen}>
        <DialogContent className="max-w-lg max-h-[80vh]">
          <DialogHeader>
            <DialogTitle className="text-lg font-semibold">
              {t('Generate Document')}
            </DialogTitle>
            <p className="text-sm text-gray-600">{currentItem?.name}</p>
          </DialogHeader>
          <div className="overflow-y-auto max-h-[50vh] space-y-3 py-2 pr-2">
            {currentItem?.placeholders?.map((placeholder: string) => (
              <div key={placeholder} className="space-y-1">
                <Label htmlFor={placeholder} className="text-sm font-medium">
                  {placeholder.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                </Label>
                <Input
                  id={placeholder}
                  value={placeholderValues[placeholder] || ''}
                  onChange={(e) => setPlaceholderValues(prev => ({ ...prev, [placeholder]: e.target.value }))}
                  placeholder={`Enter ${placeholder.replace(/_/g, ' ')}`}
                />
              </div>
            ))}
          </div>
          <div className="flex justify-end space-x-2 pt-2 border-t">
            <Button variant="outline" onClick={() => setIsGenerateModalOpen(false)}>
              {t('Cancel')}
            </Button>
            <Button onClick={handleGenerateSubmit} className="bg-red-500 hover:bg-red-600">
              {t('Generate Document')}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}