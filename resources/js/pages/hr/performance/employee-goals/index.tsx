// pages/hr/performance/employee-goals/index.tsx
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
import { Progress } from '@/components/ui/progress';

export default function EmployeeGoals() {
  const { t } = useTranslation();
  const { auth, goals, employees, goalTypes, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedGoalType, setSelectedGoalType] = useState(pageFilters.goal_type_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isProgressModalOpen, setIsProgressModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [progressValue, setProgressValue] = useState(0);

  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedStatus !== 'all' || searchTerm !== '' || selectedEmployee !== '' || selectedGoalType !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (selectedStatus !== 'all' ? 1 : 0) +
      (searchTerm ? 1 : 0) +
      (selectedEmployee ? 1 : 0) +
      (selectedGoalType ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.performance.employee-goals.index'), {
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      goal_type_id: selectedGoalType || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.performance.employee-goals.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      goal_type_id: selectedGoalType || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
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
      case 'update-progress':
        setProgressValue(item.progress || 0);
        setIsProgressModalOpen(true);
        break;
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleFormSubmit = (formData: any) => {
    if (formMode === 'create') {
      toast.loading(t('Creating employee goal...'));

      router.post(route('hr.performance.employee-goals.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Employee goal created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to create goal: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating employee goal...'));

      router.put(route('hr.performance.employee-goals.update', currentItem.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Employee goal updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to update goal: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const handleProgressSubmit = (formData: any) => {
    toast.loading(t('Updating goal progress...'));

    router.put(route('hr.performance.employee-goals.update-progress', currentItem.id), { progress: formData.progress }, {
      onSuccess: (page) => {
        setIsProgressModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Goal progress updated successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to update progress: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting employee goal...'));

    router.delete(route('hr.performance.employee-goals.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Employee goal deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to delete goal: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedGoalType('');
    setSelectedStatus('all');
    setShowFilters(false);

    router.get(route('hr.performance.employee-goals.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Goal" button if user has permission
  if (hasPermission(permissions, 'create-employee-goals')) {
    pageActions.push({
      label: t('Add Goal'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Performance'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Employee Goals') }
  ];

  // Define table columns
  const columns = [
    {
      key: 'title',
      label: t('Title'),
      sortable: true
    },
    {
      key: 'employee',
      label: t('Employee'),
      render: (value: any, row: any) => {
        return row.employee?.name || '-';
      }
    },
    {
      key: 'goal_type.name',
      label: t('Goal Type'),
      render: (value: string, row: any) => row.goal_type?.name || '-'
    },
    {
      key: 'start_date',
      label: t('Start Date'),
      sortable: true,
      render: (value: string) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'end_date',
      label: t('End Date'),
      sortable: true,
      render: (value: string) => value ? (window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleString()) : '-'
    },
    {
      key: 'progress',
      label: t('Progress'),
      render: (value: number) => (
        <div className="w-full">
          <Progress value={value} className="h-2" />
          <div className="text-xs text-right mt-1">{value}%</div>
        </div>
      )
    },
    {
      key: 'status',
      label: t('Status'),
      render: (value: string) => {
        let statusClass = '';
        let statusText = '';

        switch (value) {
          case 'not_started':
            statusClass = 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20';
            statusText = t('Not Started');
            break;
          case 'in_progress':
            statusClass = 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20';
            statusText = t('In Progress');
            break;
          case 'completed':
            statusClass = 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20';
            statusText = t('Completed');
            break;
          default:
            statusClass = 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20';
            statusText = value;
        }

        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${statusClass}`}>
            {statusText}
          </span>
        );
      }
    }
  ];

  // Define table actions
  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-employee-goals'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-employee-goals'
    },
    {
      label: t('Update Progress'),
      icon: 'BarChart',
      action: 'update-progress',
      className: 'text-green-500',
      requiredPermission: 'edit-employee-goals'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-employee-goals'
    }
  ];

  // Prepare filter options
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'not_started', label: t('Not Started') },
    { value: 'in_progress', label: t('In Progress') },
    { value: 'completed', label: t('Completed') }
  ];

  // Prepare employee options
  const employeeOptions = [
    { value: '', label: t('All Employees') },
    ...(employees || []).map((employee: any) => ({
      value: employee.id.toString(),
      label: `${employee.name} (${employee.employee_id})`
    }))
  ];

  // Prepare goal type options
  const goalTypeOptions = [
    { value: '', label: t('All Goal Types') },
    ...(goalTypes || []).map((goalType: any) => ({
      value: goalType.id.toString(),
      label: goalType.name
    }))
  ];

  return (
    <PageTemplate
      title={t("Employee Goals")}
      url="/hr/performance/employee-goals"
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
            ...(hasPermission(permissions, 'manage-any-employee-goals') ? [{
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: employeeOptions,
              searchable: true
            }] : []),

            {
              name: 'goal_type_id',
              label: t('Goal Type'),
              type: 'select',
              value: selectedGoalType,
              onChange: setSelectedGoalType,
              options: goalTypeOptions,
              searchable: true
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: selectedStatus,
              onChange: setSelectedStatus,
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
            router.get(route('hr.performance.employee-goals.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              goal_type_id: selectedGoalType || undefined,
              status: selectedStatus !== 'all' ? selectedStatus : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={goals?.data || []}
          from={goals?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-employee-goals',
            create: 'create-employee-goals',
            edit: 'edit-employee-goals',
            delete: 'delete-employee-goals'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={goals?.from || 0}
          to={goals?.to || 0}
          total={goals?.total || 0}
          links={goals?.links}
          entityName={t("employee goals")}
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
            formMode === 'view' ? {
              name: 'employee_name',
              label: t('Employee'),
              type: 'text',
              readOnly: true
            } : {
              name: 'employee_id',
              label: t('Employee'),
              type: 'select',
              required: true,
              searchable: true,
              options: employees.map((employee: any) => ({
                value: employee.id,
                label: `${employee.name} (${employee.employee_id})`
              }))
            },
            {
              name: 'goal_type_id',
              label: t('Goal Type'),
              type: 'select',
              searchable: true,
              required: true,
              options: goalTypes.map((goalType: any) => ({
                value: goalType.id.toString(),
                label: goalType.name
              }))
            },
            { name: 'title', label: t('Goal Title'), type: 'text', required: true },
            { name: 'description', label: t('Description'), type: 'textarea' },
            { name: 'start_date', label: t('Start Date'), type: 'date', required: true },
            { name: 'end_date', label: t('End Date'), type: 'date', required: true },
            { name: 'target', label: t('Target'), type: 'text', placeholder: 'e.g., Complete 5 projects, Achieve 95% accuracy, etc.' },
            {
              name: 'progress',
              label: t('Progress (%)'),
              type: 'number',
              min: 0,
              max: 100,
              defaultValue: 0
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              options: [
                { value: 'not_started', label: t('Not Started') },
                { value: 'in_progress', label: t('In Progress') },
                { value: 'completed', label: t('Completed') }
              ],
              defaultValue: 'not_started'
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          employee_name: currentItem.employee?.name,
          start_date: currentItem.start_date ? window.appSettings.formatDateTimeSimple(currentItem.start_date, false) : currentItem.start_date,
          end_date: currentItem.end_date ? window.appSettings.formatDateTimeSimple(currentItem.end_date, false) : currentItem.end_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Employee Goal')
            : formMode === 'edit'
              ? t('Edit Employee Goal')
              : t('View Employee Goal')
        }
        mode={formMode}
      />

      {/* Progress Update Modal */}
      <CrudFormModal
        isOpen={isProgressModalOpen}
        onClose={() => setIsProgressModalOpen(false)}
        onSubmit={handleProgressSubmit}
        formConfig={{
          fields: [
            {
              name: 'progress',
              label: t('Progress (%)'),
              type: 'number',
              min: 0,
              max: 100,
              step: 5,
              required: true
            }
          ],
          modalSize: 'sm'
        }}
        initialData={{ progress: progressValue }}
        title={t('Update Goal Progress')}
        mode="edit"
        customContent={
          <div className="mb-4 text-center">
            <div className="text-3xl font-bold">{progressValue}%</div>
            <Progress value={progressValue} className="h-2 mt-2" />
          </div>
        }
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.title || ''}
        entityName="employee goal"
      />
    </PageTemplate>
  );
}