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

export default function Candidates() {
  const { t } = useTranslation();
  const { auth, candidates, jobPostings, sources, employees, filters: pageFilters = {}, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [jobFilter, setJobFilter] = useState(pageFilters.job_id || '_empty_');
  const [sourceFilter, setSourceFilter] = useState(pageFilters.source_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [selectedStatus, setSelectedStatus] = useState('');

  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || jobFilter !== '_empty_' || sourceFilter !== '_empty_' || searchTerm !== '';
  };

  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (jobFilter !== '_empty_' ? 1 : 0) + (sourceFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.recruitment.candidates.index'), {
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      job_id: jobFilter !== '_empty_' ? jobFilter : undefined,
      source_id: sourceFilter !== '_empty_' ? sourceFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.recruitment.candidates.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      job_id: jobFilter !== '_empty_' ? jobFilter : undefined,
      source_id: sourceFilter !== '_empty_' ? sourceFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);

    switch (action) {
      case 'view':
        router.get(route('hr.recruitment.candidates.show', item.id));
        break;
      // Need to Remove - case 'edit':
      //   setFormMode('edit');
      //   setIsFormModalOpen(true);
      //   break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'update-status':
        setCurrentItem(item);
        setSelectedStatus(item.status);
        setIsStatusModalOpen(true);
        break;
      case 'convert-to-employee':
        if (!globalSettings?.is_demo) {
          toast.loading(t('Loading conversion form...'));
        }
        router.get(route('hr.recruitment.candidates.convert-to-employee', item.id), {}, {
          onSuccess: (page) => {
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
              toast.error(t('Failed to load conversion form: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
          }
        });
        break;
    }
  };

  // Need to Remove - const handleAddNew = () => {
  //   setCurrentItem(null);
  //   setFormMode('create');
  //   setIsFormModalOpen(true);
  // };

  // Need to Remove - const handleFormSubmit = (formData: any) => {
  //   if (formMode === 'create') {
  //     toast.loading(t('Creating candidate...'));

  //     router.post(route('hr.recruitment.candidates.store'), formData, {
  //       onSuccess: (page) => {
  //         setIsFormModalOpen(false);
  //         toast.dismiss();
  //         if (page.props.flash.success) {
  //           toast.success(t(page.props.flash.success));
  //         } else if (page.props.flash.error) {
  //           toast.error(t(page.props.flash.error));
  //         }
  //       },
  //       onError: (errors) => {
  //         toast.dismiss();
  //         if (typeof errors === 'string') {
  //           toast.error(t(errors));
  //         } else {
  //           toast.error(t('Failed to create candidate: {{errors}}', { errors: Object.values(errors).join(', ') }));
  //         }
  //       }
  //     });
  //   } else if (formMode === 'edit') {
  //     toast.loading(t('Updating candidate...'));

  //     router.put(route('hr.recruitment.candidates.update', currentItem.id), formData, {
  //       onSuccess: (page) => {
  //         setIsFormModalOpen(false);
  //         toast.dismiss();
  //         if (page.props.flash.success) {
  //           toast.success(t(page.props.flash.success));
  //         } else if (page.props.flash.error) {
  //           toast.error(t(page.props.flash.error));
  //         }
  //       },
  //       onError: (errors) => {
  //         toast.dismiss();
  //         if (typeof errors === 'string') {
  //           toast.error(t(errors));
  //         } else {
  //           toast.error(t('Failed to update candidate: {{errors}}', { errors: Object.values(errors).join(', ') }));
  //         }
  //       }
  //     });
  //   }
  // };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting candidate...'));

    router.delete(route('hr.recruitment.candidates.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete candidate: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  const handleStatusUpdate = (formData: any) => {
    if (!formData.status) return;

    toast.loading(t('Updating status...'));

    router.put(route('hr.recruitment.candidates.update-status', currentItem.id), { status: formData.status }, {
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
    setJobFilter('_empty_');
    setSourceFilter('_empty_');
    setShowFilters(false);

    router.get(route('hr.recruitment.candidates.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];

  // Need to Remove - if (hasPermission(permissions, 'create-candidates')) {
  //   pageActions.push({
  //     label: t('Add Candidate'),
  //     icon: <Plus className="h-4 w-4 mr-2" />,
  //     variant: 'default',
  //     onClick: () => handleAddNew()
  //   });
  // }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.candidates.index') },
    { title: t('Candidates') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'New': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Screening': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Interview': return 'bg-purple-50 text-purple-700 ring-purple-600/20';
      case 'Offer': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      case 'Hired': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Rejected': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const columns = [
    {
      key: 'full_name',
      label: t('Name'),
      sortable: true,
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.first_name} {row.last_name}</div>
          <div className="text-xs text-gray-500">{row.email}</div>
        </div>
      )
    },
    {
      key: 'job.title',
      label: t('Job'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.job?.title || '-'}</div>
          <div className="text-xs text-gray-500">{row.job?.job_code || ''}</div>
        </div>
      )
    },
    {
      key: 'source.name',
      label: t('Source'),
      render: (_, row) => row.source?.name || '-'
    },
    {
      key: 'experience_years',
      label: t('Experience'),
      render: (value) => `${value} ${t('years')}`
    },
    {
      key: 'expected_salary',
      label: t('Expected Salary'),
      render: (value) => value ? window.appSettings?.formatCurrency(value) : '-'
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
      key: 'is_employee',
      label: t('Employee Converted'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${value
          ? 'bg-green-50 text-green-700 ring-green-600/20'
          : 'bg-gray-50 text-gray-600 ring-gray-500/10'
          }`}>
          {value ? t('Yes') : t('No')}
        </span>
      )
    },
    {
      key: 'application_date',
      label: t('Applied'),
      sortable: false,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  const actions = [
    {
      label: t('View'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-candidates'
    },
    // Need to Remove - { 
    //   label: t('Edit'), 
    //   icon: 'Edit', 
    //   action: 'edit', 
    //   className: 'text-amber-500',
    //   requiredPermission: 'edit-candidates'
    // },
    {
      label: t('Update Status'),
      icon: 'RefreshCw',
      action: 'update-status',
      className: 'text-green-500',
      requiredPermission: 'edit-candidates',
      condition: (item: any) => !['Hired', 'Rejected'].includes(item.status)
    },
    {
      label: t('Convert to Employee'),
      icon: 'UserPlus',
      action: 'convert-to-employee',
      className: 'text-purple-500',
      requiredPermission: 'create-employees',
      condition: (item: any) => item.status === 'Hired' && !item.is_employee
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-candidates'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') },
    { value: 'New', label: t('New') },
    { value: 'Screening', label: t('Screening') },
    { value: 'Interview', label: t('Interview') },
    { value: 'Offer', label: t('Offer') },
    { value: 'Hired', label: t('Hired') },
    { value: 'Rejected', label: t('Rejected') }
  ];

  const jobOptions = [
    { value: '_empty_', label: t('All Jobs') },
    ...(jobPostings || []).map((job: any) => ({
      value: job.id.toString(),
      label: `${job.job_code} - ${job.title}`
    }))
  ];

  const sourceOptions = [
    { value: '_empty_', label: t('All Sources') },
    ...(sources || []).map((source: any) => ({
      value: source.id.toString(),
      label: source.name
    }))
  ];

  const jobPostingOptions = [
    { value: '_empty_', label: t('Select Job') },
    ...(jobPostings || []).map((job: any) => ({
      value: job.id.toString(),
      label: `${job.job_code} - ${job.title}`
    }))
  ];

  const candidateSourceOptions = [
    { value: '_empty_', label: t('Select Source') },
    ...(sources || []).map((source: any) => ({
      value: source.id.toString(),
      label: source.name
    }))
  ];

  const employeeOptions = [
    { value: 'none', label: t('Select Employee') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate
      title={t("Candidates")}
      url="/hr/recruitment/candidates"
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
            },
            {
              name: 'job_id',
              label: t('Job'),
              type: 'select',
              value: jobFilter,
              onChange: setJobFilter,
              options: jobOptions,
              searchable: true
            },
            {
              name: 'source_id',
              label: t('Source'),
              type: 'select',
              value: sourceFilter,
              onChange: setSourceFilter,
              options: sourceOptions,
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
            router.get(route('hr.recruitment.candidates.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              job_id: jobFilter !== '_empty_' ? jobFilter : undefined,
              source_id: sourceFilter !== '_empty_' ? sourceFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={candidates?.data || []}
          from={candidates?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-candidates',
            create: 'create-candidates',
            edit: 'edit-candidates',
            delete: 'delete-candidates'
          }}
        />

        <Pagination
          from={candidates?.from || 0}
          to={candidates?.to || 0}
          total={candidates?.total || 0}
          links={candidates?.links}
          entityName={t("candidates")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      {/* Need to Remove - Form Modal */}
      {/* <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { 
              name: 'job_id', 
              label: t('Job'), 
              type: 'select', 
              required: true,
              options: jobPostingOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'source_id', 
              label: t('Source'), 
              type: 'select', 
              required: true,
              options: candidateSourceOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'first_name', 
              label: t('First Name'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'last_name', 
              label: t('Last Name'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'email', 
              label: t('Email'), 
              type: 'email', 
              required: true 
            },
            { 
              name: 'phone', 
              label: t('Phone'), 
              type: 'text' 
            },
            { 
              name: 'current_company', 
              label: t('Current Company'), 
              type: 'text' 
            },
            { 
              name: 'current_position', 
              label: t('Current Position'), 
              type: 'text' 
            },
            { 
              name: 'experience_years', 
              label: t('Experience (Years)'), 
              type: 'number', 
              required: true,
              min: 0
            },
            { 
              name: 'current_salary', 
              label: t('Current Salary'), 
              type: 'number',
              min: 0,
              step: 0.01
            },
            { 
              name: 'expected_salary', 
              label: t('Expected Salary'), 
              type: 'number',
              min: 0,
              step: 0.01
            },
            { 
              name: 'notice_period', 
              label: t('Notice Period'), 
              type: 'text' 
            },
            { 
              name: 'application_date', 
              label: t('Application Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'referral_employee_id', 
              label: t('Referred By'), 
              type: 'select',
              options: employeeOptions
            },
            { 
              name: 'skills', 
              label: t('Skills'), 
              type: 'textarea' 
            },
            { 
              name: 'education', 
              label: t('Education'), 
              type: 'textarea' 
            },
            { 
              name: 'portfolio_url', 
              label: t('Portfolio URL'), 
              type: 'text' 
            },
            { 
              name: 'linkedin_url', 
              label: t('LinkedIn URL'), 
              type: 'text' 
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add New Candidate')
            : formMode === 'edit'
              ? t('Edit Candidate')
              : t('View Candidate')
        }
        mode={formMode}
      /> */}

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.first_name} ${currentItem.last_name}` : ''}
        entityName="candidate"
      />

      {/* Status Update Modal */}
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
              options: statusOptions.filter(opt => opt.value !== '_empty_')
            }
          ]
        }}
        initialData={{ status: selectedStatus }}
        title={t('Update Candidate Status')}
        mode="edit"
        submitLabel={t('Update Status')}
      />
    </PageTemplate>
  );
}