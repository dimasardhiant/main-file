// pages/hr/warnings/index.tsx
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

export default function Warnings() {
  const { t } = useTranslation();
  const { auth, warnings, employees, managers, warningTypes, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedWarningType, setSelectedWarningType] = useState(pageFilters.warning_type || '');
  const [selectedSeverity, setSelectedSeverity] = useState(pageFilters.severity || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [isImprovementPlanModalOpen, setIsImprovementPlanModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedEmployee !== '' || 
           selectedWarningType !== '' || 
           selectedSeverity !== '' ||
           selectedStatus !== 'all' || 
           dateFrom !== '' || 
           dateTo !== '' || 
           searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) + 
           (selectedWarningType !== '' ? 1 : 0) + 
           (selectedSeverity !== '' ? 1 : 0) +
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
    router.get(route('hr.warnings.index'), { 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      warning_type: selectedWarningType || undefined,
      severity: selectedSeverity || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.warnings.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      warning_type: selectedWarningType || undefined,
      severity: selectedSeverity || undefined,
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
      case 'improvement-plan':
        setIsImprovementPlanModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.warnings.download-document', item.id), '_blank');
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
      toast.loading(t('Creating warning...'));

      router.post(route('hr.warnings.store'), data, {
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
            toast.error(t('Failed to create warning: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating warning...'));
      
      router.put(route('hr.warnings.update', currentItem.id), data, {
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
            toast.error(t('Failed to update warning: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleStatusChange = (formData: any) => {
    toast.loading(t('Updating warning status...'));
    
    router.put(route('hr.warnings.change-status', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsStatusModalOpen(false);
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
          toast.error(t('Failed to update warning status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleImprovementPlanUpdate = (formData: any) => {
    toast.loading(t('Updating improvement plan...'));
    
    // Ensure has_improvement_plan is properly converted to boolean
    const updatedFormData = {
      ...formData,
      has_improvement_plan: formData.has_improvement_plan ? true : false
    };
    
    router.put(route('hr.warnings.update-improvement-plan', currentItem.id), updatedFormData, {
      onSuccess: (page) => {
        setIsImprovementPlanModalOpen(false);
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
          toast.error(t('Failed to update improvement plan: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting warning...'));
    
    router.delete(route('hr.warnings.destroy', currentItem.id), {
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
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete warning: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedWarningType('');
    setSelectedSeverity('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.warnings.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Add New Warning" button if user has permission
  if (hasPermission(permissions, 'create-warnings')) {
    pageActions.push({
      label: t('Add Warning'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.warnings.index') },
    { title: t('Warnings') }
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
      key: 'subject', 
      label: t('Subject'),
      render: (value) => value || '-'
    },
    { 
      key: 'warning_type', 
      label: t('Type'),
      render: (value) => value || '-'
    },
    { 
      key: 'severity', 
      label: t('Severity'),
      render: (value) => {
        const severityClasses = {
          'verbal': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'written': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'final': 'bg-red-50 text-red-700 ring-red-600/20'
        };
        
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${severityClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    { 
      key: 'warning_date', 
      label: t('Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => {
        const statusClasses = {
          'draft': 'bg-gray-50 text-gray-700 ring-gray-600/20',
          'issued': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'acknowledged': 'bg-green-50 text-green-700 ring-green-600/20',
          'expired': 'bg-blue-50 text-blue-700 ring-blue-600/20'
        };
        
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    { 
      key: 'has_improvement_plan', 
      label: t('Improvement Plan'),
      render: (value) => value ? (
        <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
          {t('Yes')}
        </span>
      ) : (
        <span className="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">
          {t('No')}
        </span>
      )
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
      requiredPermission: 'view-warnings'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-warnings'
    },
    { 
      label: t('Change Status'), 
      icon: 'RefreshCw', 
      action: 'change-status', 
      className: 'text-green-500',
      requiredPermission: 'edit-warnings'
    },
    { 
      label: t('Improvement Plan'), 
      icon: 'LineChart', 
      action: 'improvement-plan', 
      className: 'text-purple-500',
      requiredPermission: 'edit-warnings'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-warnings'
    }
  ];

  // Prepare employee options for filter
  const employeeOptions = [
    { value: '', label: t('All Employees'), disabled: true },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare warning type options for filter
  const warningTypeOptions = [
    { value: '', label: t('All Types'), disabled: true },
    ...(warningTypes || []).map((type: string) => ({
      value: type,
      label: type
    }))
  ];

  // Prepare severity options for filter
  const severityOptions = [
    { value: '', label: t('All Severities'), disabled: true },
    { value: 'verbal', label: t('Verbal') },
    { value: 'written', label: t('Written') },
    { value: 'final', label: t('Final') }
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses'), disabled: true },
    { value: 'draft', label: t('Draft') },
    { value: 'issued', label: t('Issued') },
    { value: 'acknowledged', label: t('Acknowledged') },
    { value: 'expired', label: t('Expired') }
  ];

  // Prepare warning type options for form
  const warningTypeFormOptions = [
    { value: 'Attendance', label: t('Attendance') },
    { value: 'Performance', label: t('Performance') },
    { value: 'Conduct', label: t('Conduct') },
    { value: 'Policy Violation', label: t('Policy Violation') },
    { value: 'Safety', label: t('Safety') },
    { value: 'Communication', label: t('Communication') },
    { value: 'Insubordination', label: t('Insubordination') },
    { value: 'Confidentiality', label: t('Confidentiality') }
  ];

  return (
    <PageTemplate 
      title={t("Warnings")} 
      url="/hr/warnings"
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
              searchable: true
            },
            {
              name: 'warning_type',
              label: t('Type'),
              type: 'select',
              value: selectedWarningType,
              onChange: setSelectedWarningType,
              options: warningTypeOptions,
              searchable: true
            },
            {
              name: 'severity',
              label: t('Severity'),
              type: 'select',
              value: selectedSeverity,
              onChange: setSelectedSeverity,
              options: severityOptions,
              searchable: true
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
              options: statusOptions,
              searchable: true
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
            router.get(route('hr.warnings.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              warning_type: selectedWarningType || undefined,
              severity: selectedSeverity || undefined,
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
          data={warnings?.data || []}
          from={warnings?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-warnings',
            create: 'create-warnings',
            edit: 'edit-warnings',
            delete: 'delete-warnings'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={warnings?.from || 0}
          to={warnings?.to || 0}
          total={warnings?.total || 0}
          links={warnings?.links}
          entityName={t("warnings")}
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
              searchable: true,
            },
            { 
              name: 'warning_by', 
              label: t('Warning By'), 
              type: 'select', 
              required: true,
              searchable: true,
              options: managers?.map((manager: any) => ({
                value: manager.id.toString(),
                label: manager.name
              })) || []
            },
            { 
              name: 'warning_type', 
              label: t('Warning Type'), 
              type: 'select', 
              required: true,
              options: warningTypeFormOptions,
              searchable: true,
            },
            { 
              name: 'subject', 
              label: t('Subject'), 
              type: 'text',
              required: true
            },
            { 
              name: 'severity', 
              label: t('Severity'), 
              type: 'select',
              required: true,
              options: [
                { value: 'verbal', label: t('Verbal') },
                { value: 'written', label: t('Written') },
                { value: 'final', label: t('Final') }
              ]
            },
            { 
              name: 'warning_date', 
              label: t('Warning Date'), 
              type: 'date', 
              required: true 
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
            { 
              name: 'expiry_date', 
              label: t('Expiry Date'), 
              type: 'date'
            },
            { 
              name: 'has_improvement_plan', 
              label: t('Has Improvement Plan'), 
              type: formMode === 'view' ? 'text' : 'checkbox',
              render: formMode === 'view' ? (value) => value ? t('Yes') : t('No') : undefined
            },
            { 
              name: 'improvement_plan_goals', 
              label: t('Improvement Plan Goals'), 
              type: 'textarea',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            },
            { 
              name: 'improvement_plan_start_date', 
              label: t('Improvement Plan Start Date'), 
              type: 'date',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            },
            { 
              name: 'improvement_plan_end_date', 
              label: t('Improvement Plan End Date'), 
              type: 'date',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            },
            ...(formMode === 'edit' ? [
              { 
                name: 'status', 
                label: t('Status'), 
                type: 'select',
                options: [
                  { value: 'draft', label: t('Draft') },
                  { value: 'issued', label: t('Issued') },
                  { value: 'acknowledged', label: t('Acknowledged') },
                  { value: 'expired', label: t('Expired') }
                ]
              },
              { 
                name: 'acknowledgment_date', 
                label: t('Acknowledgment Date'), 
                type: 'date',
                showWhen: (formData) => ['acknowledged', 'expired'].includes(formData.status)
              },
              { 
                name: 'employee_response', 
                label: t('Employee Response'), 
                type: 'textarea',
                showWhen: (formData) => ['acknowledged', 'expired'].includes(formData.status)
              },
              { 
                name: 'improvement_plan_progress', 
                label: t('Improvement Plan Progress'), 
                type: 'textarea',
                showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
              }
            ] : [])
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          has_improvement_plan: currentItem.has_improvement_plan === 1 || currentItem.has_improvement_plan === true || currentItem.has_improvement_plan === '1',
          warning_date: currentItem.warning_date ? window.appSettings.formatDateTimeSimple(currentItem.warning_date, false) : currentItem.warning_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Warning')
            : formMode === 'edit'
              ? t('Edit Warning')
              : t('View Warning')
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
                { value: 'draft', label: t('Draft') },
                { value: 'issued', label: t('Issued') },
                { value: 'acknowledged', label: t('Acknowledged') },
                { value: 'expired', label: t('Expired') }
              ],
              defaultValue: currentItem?.status
            },
            { 
              name: 'acknowledgment_date', 
              label: t('Acknowledgment Date'), 
              type: 'date',
              showWhen: (formData) => ['acknowledged', 'expired'].includes(formData.status)
            },
            { 
              name: 'employee_response', 
              label: t('Employee Response'), 
              type: 'textarea',
              showWhen: (formData) => ['acknowledged', 'expired'].includes(formData.status)
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem}
        title={t('Change Warning Status')}
        mode="edit"
      />

      {/* Improvement Plan Modal */}
      <CrudFormModal
        isOpen={isImprovementPlanModalOpen}
        onClose={() => setIsImprovementPlanModalOpen(false)}
        onSubmit={handleImprovementPlanUpdate}
        formConfig={{
          fields: [
            { 
              name: 'has_improvement_plan', 
              label: t('Has Improvement Plan'), 
              type: 'checkbox',
              defaultValue: currentItem?.has_improvement_plan === true || currentItem?.has_improvement_plan === 1 || currentItem?.has_improvement_plan === '1'
            },
            { 
              name: 'improvement_plan_goals', 
              label: t('Improvement Plan Goals'), 
              type: 'textarea',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            },
            { 
              name: 'improvement_plan_start_date', 
              label: t('Improvement Plan Start Date'), 
              type: 'date',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            },
            { 
              name: 'improvement_plan_end_date', 
              label: t('Improvement Plan End Date'), 
              type: 'date',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            },
            { 
              name: 'improvement_plan_progress', 
              label: t('Improvement Plan Progress'), 
              type: 'textarea',
              showWhen: (formData) => formData.has_improvement_plan === true || formData.has_improvement_plan === 1 || formData.has_improvement_plan === '1'
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem ? {
          ...currentItem,
          has_improvement_plan: currentItem.has_improvement_plan === true || currentItem.has_improvement_plan === 1 || currentItem.has_improvement_plan === '1'
        } : null}
        title={t('Update Improvement Plan')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.name || ''} - ${currentItem?.subject || ''}`}
        entityName="warning"
      />
    </PageTemplate>
  );
}