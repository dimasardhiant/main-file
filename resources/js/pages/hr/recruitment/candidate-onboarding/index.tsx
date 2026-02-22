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
import { Plus } from 'lucide-react';
import { format } from 'date-fns';

export default function CandidateOnboarding() {
  const { t } = useTranslation();
  const { auth, candidateOnboarding, employees, checklists, buddyEmployees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [employeeFilter, setEmployeeFilter] = useState(pageFilters.employee_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || employeeFilter !== '_empty_' || searchTerm !== '';
  };

  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (employeeFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.recruitment.candidate-onboarding.index'), {
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      employee_id: employeeFilter !== '_empty_' ? employeeFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.recruitment.candidate-onboarding.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      employee_id: employeeFilter !== '_empty_' ? employeeFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);

    switch (action) {
      case 'view':
        router.get(route('hr.recruitment.candidate-onboarding.show', item.id));
        break;
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'update-status':
        setIsStatusModalOpen(true);
        break;
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleFormSubmit = (formData: any) => {
    // Filter out selected employee from buddy options before submission
    const filteredBuddyOptions = buddyEmployeeOptions.filter(opt => 
      opt.value === '_empty_' || opt.value !== formData.employee_id
    );
    
    if (formMode === 'create') {
      toast.loading(t('Creating candidate onboarding...'));

      router.post(route('hr.recruitment.candidate-onboarding.store'), formData, {
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
            toast.error(t('Failed to create candidate onboarding: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating candidate onboarding...'));

      router.put(route('hr.recruitment.candidate-onboarding.update', currentItem.id), formData, {
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
            toast.error(t('Failed to update candidate onboarding: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting candidate onboarding...'));

    router.delete(route('hr.recruitment.candidate-onboarding.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete candidate onboarding: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleStatusUpdate = (formData: any) => {
    toast.loading(t('Updating status...'));

    router.put(route('hr.recruitment.candidate-onboarding.update-status', currentItem.id), formData, {
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
          toast.error(t('Failed to update status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setEmployeeFilter('_empty_');
    setShowFilters(false);

    router.get(route('hr.recruitment.candidate-onboarding.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];

  if (hasPermission(permissions, 'create-candidate-onboarding')) {
    pageActions.push({
      label: t('Start Onboarding'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.candidate-onboarding.index') },
    { title: t('Candidate Onboarding') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Pending': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'In Progress': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Completed': return 'bg-green-50 text-green-700 ring-green-600/20';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const columns = [
    {
      key: 'employee.name',
      label: t('Employee'),
      render: (_, row) => (
        row.employee?.name ? (
          <div>
            <div className="font-medium">{row.employee.name}</div>
            <div className="text-xs text-gray-500">{row.employee.email || '-'}</div>
          </div>
        ) : '-'
      )
    },
    {
      key: 'checklist.name',
      label: t('Checklist'),
      render: (_, row) => row.checklist?.name || '-'
    },
    {
      key: 'start_date',
      label: t('Start Date'),
      sortable: true,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    },
    {
      key: 'buddy_employee.name',
      label: t('Buddy'),
      render: (_, row) => row.buddy_employee?.name || '-'
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
          {t(value)}
        </span>
      )
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
      requiredPermission: 'view-candidate-onboarding'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-candidate-onboarding'
    },
    {
      label: t('Update Status'),
      icon: 'RefreshCw',
      action: 'update-status',
      className: 'text-green-500',
      requiredPermission: 'manage-candidate-onboarding-status'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-candidate-onboarding'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses'), disabled: true },
    { value: 'Pending', label: t('Pending') },
    { value: 'In Progress', label: t('In Progress') },
    { value: 'Completed', label: t('Completed') }
  ];

  const employeeOptions = [
    { value: '_empty_', label: t('All Employees') , disabled: true},
    ...(employees || []).map((employee: any) => ({
      value: employee.id.toString(),
      label: employee.name
    }))
  ];

  const employeeSelectOptions = [
    { value: '_empty_', label: t('Select Employee') },
    ...(employees || []).map((employee: any) => ({
      value: employee.id.toString(),
      label: employee.name
    }))
  ];

  const checklistOptions = [
    { value: '_empty_', label: t('Select Checklist') },
    ...(checklists || []).map((checklist: any) => ({
      value: checklist.id.toString(),
      label: checklist.name
    }))
  ];

  const buddyEmployeeOptions = [
    { value: '_empty_', label: t('Select Buddy') },
    ...(buddyEmployees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate
      title={t("Candidate Onboarding")}
      url="/hr/recruitment/candidate-onboarding"
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
              options: statusOptions,
              searchable: true
            },
            {
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: employeeFilter,
              onChange: setEmployeeFilter,
              options: employeeOptions,
              searchable: true
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
            router.get(route('hr.recruitment.candidate-onboarding.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              employee_id: employeeFilter !== '_empty_' ? employeeFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={candidateOnboarding?.data || []}
          from={candidateOnboarding?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-candidate-onboarding',
            create: 'create-candidate-onboarding',
            edit: 'edit-candidate-onboarding',
            delete: 'delete-candidate-onboarding'
          }}
        />

        <Pagination
          from={candidateOnboarding?.from || 0}
          to={candidateOnboarding?.to || 0}
          total={candidateOnboarding?.total || 0}
          links={candidateOnboarding?.links}
          entityName={t("onboarding records")}
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
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              required: true,
              options: employeeSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true,
              onChange: (value, setFieldValue) => {
                // Clear buddy employee if same as selected employee
                const currentBuddy = currentItem?.buddy_employee_id || '';
                if (currentBuddy === value) {
                  setFieldValue('buddy_employee_id', '');
                }
              }
            },
            {
              name: 'checklist_id',
              label: t('Onboarding Checklist'),
              type: 'select',
              required: true,
              options: checklistOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            {
              name: 'start_date',
              label: t('Start Date'),
              type: 'date',
              required: true
            },
            {
              name: 'buddy_employee_id',
              label: t('Buddy Employee'),
              type: 'select',
              required: true,
              options: buddyEmployeeOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true,
              render: (field, formData, handleChange) => {
                const filteredOptions = buddyEmployees.filter(emp => 
                  emp.id.toString() !== formData.employee_id
                ).map(emp => ({
                  value: emp.id.toString(),
                  label: emp.name
                }));
                return (
                  <select
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                    value={formData[field.name] || ''}
                    onChange={(e) => handleChange(field.name, e.target.value)}
                  >
                    <option value="" selected disabled>{t('Select Buddy')}</option>
                    {filteredOptions.map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}
                  </select>
                );
              }
            }
          ]
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Start New Onboarding')
            : formMode === 'edit'
              ? t('Edit Onboarding')
              : t('View Onboarding')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? currentItem.employee?.name : ''}
        entityName="onboarding record"
      />

      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={handleStatusUpdate}
        formConfig={{
          fields: [
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              required: true,
              options: [
                { value: 'Pending', label: t('Pending') },
                { value: 'In Progress', label: t('In Progress') },
                { value: 'Completed', label: t('Completed') }
              ]
            }
          ]
        }}
        initialData={currentItem ? { status: currentItem.status } : {}}
        title={t('Update Onboarding Status')}
        mode="edit"
      />
    </PageTemplate>
  );
}