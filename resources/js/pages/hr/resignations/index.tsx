// pages/hr/resignations/index.tsx
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

export default function Resignations() {
  const { t } = useTranslation();
  const { auth, resignations, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedEmployee !== '' || selectedStatus !== 'all' || dateFrom !== '' || dateTo !== '' || searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) + 
           (selectedStatus !== 'all' ? 1 : 0) + 
           (dateFrom !== '' ? 1 : 0) + 
           (dateTo !== '' ? 1 : 0) + 
           (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.resignations.index'), { 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.resignations.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
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
      case 'change-status':
        setIsStatusModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.resignations.download-document', item.id), '_blank');
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
      toast.loading(t('Creating resignation...'));

      router.post(route('hr.resignations.store'), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Resignation created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t(`Failed to create resignation: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating resignation...'));
      
      router.put(route('hr.resignations.update', currentItem.id), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Resignation updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to update resignation: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    }
  };
  
  const handleStatusChange = (formData: any) => {
    toast.loading(t('Updating resignation status...'));
    
    router.put(route('hr.resignations.change-status', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsStatusModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Resignation status updated successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to update resignation status: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting resignation...'));
    
    router.delete(route('hr.resignations.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Resignation deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to delete resignation: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.resignations.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Add New Resignation" button if user has permission
  if (hasPermission(permissions, 'create-resignations')) {
    pageActions.push({
      label: t('Add Resignation'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.resignations.index') },
    { title: t('Resignations') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'employee.name', 
      label: t('Employee'), 
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.employee?.name || '-'}</div>
          <div className="text-xs text-gray-500">{row.employee?.employee_id || ''}</div>
        </div>
      )
    },
    { 
      key: 'resignation_date', 
      label: t('Resignation Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'
    },
    { 
      key: 'last_working_day', 
      label: t('Last Working Day'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'
    },
    { 
      key: 'notice_period', 
      label: t('Notice Period'),
      render: (value) => value || '-'
    },
    { 
      key: 'reason', 
      label: t('Reason'),
      render: (value) => value || '-'
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => {
        const statusClasses = {
          pending: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          approved: 'bg-green-50 text-green-700 ring-green-600/20',
          rejected: 'bg-red-50 text-red-700 ring-red-600/20',
          completed: 'bg-blue-50 text-blue-700 ring-blue-600/20'
        };
        
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    { 
      key: 'documents', 
      label: t('Documents'),
      render: (value, row) => value && value.trim() !== '' ? (
        <span 
          className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 cursor-pointer"
          onClick={(e) => {
            e.stopPropagation();
            handleAction('download-document', row);
          }}
        >
          {t('View Document')}
        </span>
      ) : '-'
    }
  ];

  // Define table actions
  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-resignations'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-resignations'
    },
    { 
      label: t('Change Status'), 
      icon: 'RefreshCw', 
      action: 'change-status', 
      className: 'text-green-500',
      requiredPermission: 'edit-resignations'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-resignations'
    }
  ];

  // Prepare employee options for filter
  const employeeOptions = [
    { value: '', label: t('All Employees') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'pending', label: t('Pending') },
    { value: 'approved', label: t('Approved') },
    { value: 'rejected', label: t('Rejected') },
    { value: 'completed', label: t('Completed') }
  ];

  return (
    <PageTemplate 
      title={t("Resignations")} 
      url="/hr/resignations"
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
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: employeeOptions,
              searchable : true,
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions
            },
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
            router.get(route('hr.resignations.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              status: selectedStatus !== 'all' ? selectedStatus : undefined,
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
          data={resignations?.data || []}
          from={resignations?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-resignations',
            create: 'create-resignations',
            edit: 'edit-resignations',
            delete: 'delete-resignations'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={resignations?.from || 0}
          to={resignations?.to || 0}
          total={resignations?.total || 0}
          links={resignations?.links}
          entityName={t("resignations")}
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
              options: employeeOptions.filter(opt => opt.value !== ''),
              searchable : true,
            },
            { 
              name: 'resignation_date', 
              label: t('Resignation Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'last_working_day', 
              label: t('Last Working Day'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'notice_period', 
              label: t('Notice Period'), 
              type: 'text',
              placeholder: 'e.g. 1 month, 2 weeks'
            },
            { 
              name: 'reason', 
              label: t('Reason'), 
              type: 'text' 
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea' 
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
              )
            },
            ...(formMode === 'edit' ? [
              { 
                name: 'status', 
                label: t('Status'), 
                type: 'select',
                options: [
                  { value: 'pending', label: 'Pending' },
                  { value: 'approved', label: 'Approved' },
                  { value: 'rejected', label: 'Rejected' },
                  { value: 'completed', label: 'Completed' }
                ]
              },
              { 
                name: 'exit_interview_conducted', 
                label: t('Exit Interview Conducted'), 
                type: 'checkbox'
              },
              { 
                name: 'exit_interview_date', 
                label: t('Exit Interview Date'), 
                type: 'date',
                showWhen: (formData) => formData.exit_interview_conducted
              },
              { 
                name: 'exit_feedback', 
                label: t('Exit Feedback'), 
                type: 'textarea',
                showWhen: (formData) => formData.status === 'completed'
              }
            ] : [])
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          resignation_date: currentItem.resignation_date ? window.appSettings.formatDateTimeSimple(currentItem.resignation_date, false) : currentItem.resignation_date,
          last_working_day: currentItem.last_working_day ? window.appSettings.formatDateTimeSimple(currentItem.last_working_day, false) : currentItem.last_working_day
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Resignation')
            : formMode === 'edit'
              ? t('Edit Resignation')
              : t('View Resignation')
        }
        mode={formMode}
      />

      {/* Status Change Modal */}
      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={handleStatusChange}
        formConfig={{
          fields: [
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select',
              required: true,
              options: [
                { value: 'pending', label: 'Pending' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'completed', label: 'Completed' }
              ],
              defaultValue: currentItem?.status
            },
            { 
              name: 'exit_interview_conducted', 
              label: t('Exit Interview Conducted'), 
              type: 'checkbox',
              showWhen: (formData) => formData.status === 'completed'
            },
            { 
              name: 'exit_interview_date', 
              label: t('Exit Interview Date'), 
              type: 'date',
              showWhen: (formData) => formData.status === 'completed' && formData.exit_interview_conducted
            },
            { 
              name: 'exit_feedback', 
              label: t('Exit Feedback'), 
              type: 'textarea',
              showWhen: (formData) => formData.status === 'completed'
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem}
        title={t('Change Resignation Status')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''}`}
        entityName="resignation"
      />
    </PageTemplate>
  );
}