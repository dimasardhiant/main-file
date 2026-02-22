// pages/hr/awards/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Plus } from 'lucide-react';
import { format } from 'date-fns';
import MediaPicker from '@/components/MediaPicker';

export default function Awards() {
  const { t } = useTranslation();
  const { auth, awards, awardTypes, employees, filters: pageFilters = {}, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedAwardType, setSelectedAwardType] = useState(pageFilters.award_type_id || '_empty_');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '_empty_');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedAwardType !== '_empty_' || selectedEmployee !== '_empty_' || dateFrom !== '' || dateTo !== '' || searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedAwardType !== '_empty_' ? 1 : 0) + 
           (selectedEmployee !== '_empty_' ? 1 : 0) + 
           (dateFrom !== '' ? 1 : 0) + 
           (dateTo !== '' ? 1 : 0) + 
           (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.awards.index'), { 
      page: 1,
      search: searchTerm || undefined,
      award_type_id: selectedAwardType !== '_empty_' ? selectedAwardType : undefined,
      employee_id: selectedEmployee !== '_empty_' ? selectedEmployee : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.awards.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      award_type_id: selectedAwardType !== '_empty_' ? selectedAwardType : undefined,
      employee_id: selectedEmployee !== '_empty_' ? selectedEmployee : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
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
      case 'download-certificate':
        window.open(route('hr.awards.download-certificate', item.id), '_blank');
        break;
      case 'download-photo':
        window.open(route('hr.awards.download-photo', item.id), '_blank');
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    const data = formData;
    
    if (formMode === 'create') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Creating award...'));
      }

      router.post(route('hr.awards.store'), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to create award: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Updating award...'));
      }
      
      router.put(route('hr.awards.update', currentItem.id), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (!globalSettings?.is_demo) {
            toast.dismiss();
          }
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to update award: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Deleting award...'));
    }
    
    router.delete(route('hr.awards.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (!globalSettings?.is_demo) {
          toast.dismiss();
        }
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete award: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedAwardType('_empty_');
    setSelectedEmployee('_empty_');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.awards.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Add New Award" button if user has permission
  if (hasPermission(permissions, 'create-awards')) {
    pageActions.push({
      label: t('Add Award'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.awards.index') },
    { title: t('Awards') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'employee.name', 
      label: t('Employee'), 
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.employee?.name || '-'}</div>
          <div className="text-xs text-gray-500">{row.employee?.employee?.employee_id || ''}</div>
        </div>
      )
    },
    { 
      key: 'award_type.name', 
      label: t('Award Type'),
      render: (_, row) => row.award_type?.name || '-'
    },
    { 
      key: 'award_date', 
      label: t('Award Date'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    },
    { 
      key: 'gift', 
      label: t('Gift'),
      render: (value) => value || '-'
    },
    { 
      key: 'monetary_value', 
      label: t('Value'),
      render: (value) => value ? window.appSettings.formatCurrency(value) : '-'
    },
    { 
      key: 'files', 
      label: t('Files'),
      render: (_, row) => (
        <div className="flex space-x-2">
          {row.certificate && row.certificate.trim() !== '' && (
            <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 cursor-pointer"
                  onClick={() => handleAction('download-certificate', row)}>
              {t('Certificate')}
            </span>
          )}
          {row.photo && row.photo.trim() !== '' && (
            <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 cursor-pointer"
                  onClick={() => handleAction('download-photo', row)}>
              {t('Photo')}
            </span>
          )}
        </div>
      )
    }
  ];

  // Define table actions
  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-awards'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-awards'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-awards'
    }
  ];

  // Prepare award type options for filter
  const awardTypeOptions = [
    { value: '_empty_', label: t('All Award Types') },
    ...(awardTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  // Prepare employee options for filter
  const employeeOptions = [
    { value: '_empty_', label: t('Select Employee'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  return (
    <PageTemplate 
      title={t("Awards")} 
      url="/hr/awards"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Search and filters section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={[
            {
              name: 'award_type_id',
              label: t('Award Type'),
              type: 'select',
              value: selectedAwardType,
              onChange: setSelectedAwardType,
              options: awardTypeOptions,
               searchable: true
            },
            ...(hasPermission(permissions, 'manage-any-awards') ? [{
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: employeeOptions,
              searchable: true
            }] : []),
            {
              name: 'date_from',
              label: t('Date From'),
              type: 'date',
              value: dateFrom,
              onChange: setDateFrom
            },
            {
              name: 'date_to',
              label: t('Date To'),
              type: 'date',
              value: dateTo,
              onChange: setDateTo
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
            router.get(route('hr.awards.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              award_type_id: selectedAwardType !== '_empty_' ? selectedAwardType : undefined,
              employee_id: selectedEmployee !== '_empty_' ? selectedEmployee : undefined,
              date_from: dateFrom || undefined,
              date_to: dateTo || undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={awards?.data || []}
          from={awards?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-awards',
            create: 'create-awards',
            edit: 'edit-awards',
            delete: 'delete-awards'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={awards?.from || 0}
          to={awards?.to || 0}
          total={awards?.total || 0}
          links={awards?.links}
          entityName={t("awards")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { 
              name: 'employee_id', 
              label: t('Employee'), 
              type: 'select', 
              required: true,
              searchable: true,
              options: employeeOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'award_type_id', 
              label: t('Award Type'), 
              type: 'select', 
              required: true,
              options: awardTypeOptions.filter(opt => opt.value !== ''),
              searchable: true

            },
            { 
              name: 'award_date', 
              label: t('Award Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'gift', 
              label: t('Gift'), 
              type: 'text' 
            },
            { 
              name: 'monetary_value', 
              label: t('Monetary Value'), 
              type: 'number',
              min: 0,
              step: 0.01
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea' 
            },
            { 
              name: 'certificate', 
              label: t('Certificate'), 
              type: 'custom',
              render: (field, formData, handleChange) => (
                <MediaPicker
                  value={String(formData[field.name] || '')}
                  onChange={(url) => handleChange(field.name, url)}
                  placeholder={t('Select certificate file...')}
                />
              )
            },
            { 
              name: 'photo', 
              label: t('Photo'), 
              type: 'custom',
              render: (field, formData, handleChange) => (
                <MediaPicker                  
                  value={String(formData[field.name] || '')}
                  onChange={(url) => handleChange(field.name, url)}
                  placeholder={t('Select photo file...')}
                />
              )
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add New Award')
            : formMode === 'edit'
              ? t('Edit Award')
              : t('View Award')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''} - ${currentItem?.award_type?.name || ''}`}
        entityName="award"
      />
    </PageTemplate>
  );
}