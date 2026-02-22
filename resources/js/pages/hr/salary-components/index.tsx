// pages/hr/salary-components/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function SalaryComponents() {
  const { t } = useTranslation();
  const { auth, salaryComponents, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedType, setSelectedType] = useState(pageFilters.type || 'all');
  const [selectedCalculationType, setSelectedCalculationType] = useState(pageFilters.calculation_type || 'all');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  // Check if any filters are active
  const hasActiveFilters = () => {
    return searchTerm !== '' || selectedType !== 'all' || selectedCalculationType !== 'all' || selectedStatus !== 'all';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (searchTerm ? 1 : 0) + (selectedType !== 'all' ? 1 : 0) + (selectedCalculationType !== 'all' ? 1 : 0) + (selectedStatus !== 'all' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.salary-components.index'), {
      page: 1,
      search: searchTerm || undefined,
      type: selectedType !== 'all' ? selectedType : undefined,
      calculation_type: selectedCalculationType !== 'all' ? selectedCalculationType : undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.salary-components.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      type: selectedType !== 'all' ? selectedType : undefined,
      calculation_type: selectedCalculationType !== 'all' ? selectedCalculationType : undefined,
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
      case 'toggle-status':
        handleToggleStatus(item);
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
      toast.loading(t('Creating salary component...'));

      router.post(route('hr.salary-components.store'), formData, {
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
            toast.error(`Failed to create salary component: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating salary component...'));

      router.put(route('hr.salary-components.update', currentItem.id), formData, {
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
            toast.error(`Failed to update salary component: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting salary component...'));

    router.delete(route('hr.salary-components.destroy', currentItem.id), {
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
          toast.error(`Failed to delete salary component: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleToggleStatus = (component: any) => {
    const newStatus = component.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} salary component...`);

    router.put(route('hr.salary-components.toggle-status', component.id), {}, {
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
          toast.error(`Failed to update salary component status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedType('all');
    setSelectedCalculationType('all');
    setSelectedStatus('all');
    setShowFilters(false);

    router.get(route('hr.salary-components.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];

  // Add the "Add New Component" button if user has permission
  if (hasPermission(permissions, 'create-salary-components')) {
    pageActions.push({
      label: t('Add Component'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Payroll Management'), href: route('hr.salary-components.index') },
    { title: t('Salary Components') }
  ];

  // Define table columns
  const columns = [
    {
      key: 'name',
      label: t('Component Name'),
      sortable: true
    },
    {
      key: 'type',
      label: t('Type'),
      render: (value: string) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'earning'
          ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
          : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
          }`}>
          {value === 'earning' ? t('Earning') : t('Deduction')}
        </span>
      )
    },
    {
      key: 'calculation_type',
      label: t('Calculation'),
      render: (value: string) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'fixed'
          ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'
          : 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20'
          }`}>
          {value === 'fixed' ? t('Fixed') : t('Percentage')}
        </span>
      )
    },
    {
      key: 'amount',
      label: t('Amount/Percentage'),
      render: (value: any, row: any) => (
        <span className="font-mono">
          {row.calculation_type === 'fixed' 
            ? window.appSettings?.formatCurrency(row.default_amount) 
            : `${row.percentage_of_basic}%`
          }
        </span>
      )
    },
    // {
    //   key: 'is_taxable',
    //   label: t('Taxable'),
    //   render: (value: boolean) => (
    //     <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value
    //       ? 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20'
    //       : 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
    //       }`}>
    //       {value ? t('Yes') : t('No')}
    //     </span>
    //   )
    // },
    // {
    //   key: 'is_mandatory',
    //   label: t('Mandatory'),
    //   render: (value: boolean) => (
    //     <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value
    //       ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
    //       : 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
    //       }`}>
    //       {value ? t('Yes') : t('No')}
    //     </span>
    //   )
    // },
    {
      key: 'status',
      label: t('Status'),
      render: (value: string) => {
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${value === 'active'
            ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
            : 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20'
            }`}>
            {value === 'active' ? t('Active') : t('Inactive')}
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
      requiredPermission: 'view-salary-components'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-salary-components'
    },
    {
      label: t('Toggle Status'),
      icon: 'Lock',
      action: 'toggle-status',
      className: 'text-amber-500',
      requiredPermission: 'edit-salary-components'
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-salary-components'
    }
  ];

  // Prepare options for filters
  const typeOptions = [
    { value: 'all', label: t('All Types') , disabled : true},
    { value: 'earning', label: t('Earning') },
    { value: 'deduction', label: t('Deduction') }
  ];

  const calculationTypeOptions = [
    { value: 'all', label: t('All Calculations') , disabled : true},
    { value: 'fixed', label: t('Fixed Amount') },
    { value: 'percentage', label: t('Percentage') }
  ];

  const statusOptions = [
    { value: 'all', label: t('All Statuses') , disabled : true },
    { value: 'active', label: t('Active') },
    { value: 'inactive', label: t('Inactive') }
  ];

  return (
    <PageTemplate
      title={t("Salary Components")}
      url="/hr/salary-components"
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
              name: 'type',
              label: t('Type'),
              type: 'select',
              value: selectedType,
              onChange: setSelectedType,
              options: typeOptions
            },
            {
              name: 'calculation_type',
              label: t('Calculation Type'),
              type: 'select',
              value: selectedCalculationType,
              onChange: setSelectedCalculationType,
              options: calculationTypeOptions
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
            router.get(route('hr.salary-components.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              type: selectedType !== 'all' ? selectedType : undefined,
              calculation_type: selectedCalculationType !== 'all' ? selectedCalculationType : undefined,
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
          data={salaryComponents?.data || []}
          from={salaryComponents?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-salary-components',
            create: 'create-salary-components',
            edit: 'edit-salary-components',
            delete: 'delete-salary-components'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={salaryComponents?.from || 0}
          to={salaryComponents?.to || 0}
          total={salaryComponents?.total || 0}
          links={salaryComponents?.links}
          entityName={t("salary components")}
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
            { name: 'name', label: t('Component Name'), type: 'text', required: true },
            { name: 'description', label: t('Description'), type: 'textarea' },
            {
              name: 'type',
              label: t('Type'),
              type: 'select',
              required: true,
              options: [
                { value: 'earning', label: t('Earning') },
                { value: 'deduction', label: t('Deduction') }
              ]
            },
            {
              name: 'calculation_type',
              label: t('Calculation Type'),
              type: 'select',
              required: true,
              options: [
                { value: 'fixed', label: t('Fixed Amount') },
                { value: 'percentage', label: t('Percentage of Basic') }
              ]
            },
            { name: 'default_amount', label: t('Fixed Amount'), type: 'number', min: 0, step: 0.01 },
            { name: 'percentage_of_basic', label: t('Percentage of Basic'), type: 'number', min: 0, max: 100, step: 0.01 },
            // { name: 'is_taxable', label: t('Is Taxable'), type: 'checkbox', defaultValue: true },
            // { name: 'is_mandatory', label: t('Is Mandatory'), type: 'checkbox', defaultValue: false },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              options: [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' }
              ],
              defaultValue: 'active'
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add New Salary Component')
            : formMode === 'edit'
              ? t('Edit Salary Component')
              : t('View Salary Component')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="salary component"
      />
    </PageTemplate>
  );
}