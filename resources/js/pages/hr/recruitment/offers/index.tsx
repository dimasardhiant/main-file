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
import axios from 'axios';

export default function Offers() {
  const { t } = useTranslation();
  const { auth, offers, candidates, departments, employees, jobPostings, currentUser, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [candidateFilter, setCandidateFilter] = useState(pageFilters.candidate_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || candidateFilter !== '_empty_' || searchTerm !== '';
  };

  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (candidateFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.recruitment.offers.index'), {
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      candidate_id: candidateFilter !== '_empty_' ? candidateFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

    router.get(route('hr.recruitment.offers.index'), {
      sort_field: field,
      sort_direction: direction,
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      candidate_id: candidateFilter !== '_empty_' ? candidateFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);

    switch (action) {
      case 'view':
        router.get(route('hr.recruitment.offers.show', item.id));
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
    if (formMode === 'create') {
      toast.loading(t('Creating offer...'));

      router.post(route('hr.recruitment.offers.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Offer created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to create offer: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating offer...'));

      router.put(route('hr.recruitment.offers.update', currentItem.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Offer updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to update offer: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };

  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting offer...'));

    router.delete(route('hr.recruitment.offers.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Offer deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to delete offer: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleStatusUpdate = (formData: any) => {
    toast.loading(t('Updating status...'));

    router.put(route('hr.recruitment.offers.update-status', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsStatusModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Status updated successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to update status: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setCandidateFilter('_empty_');
    setShowFilters(false);

    router.get(route('hr.recruitment.offers.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];

  if (hasPermission(permissions, 'create-offers')) {
    pageActions.push({
      label: t('Create Offer'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.offers.index') },
    { title: t('Offers') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Draft': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      case 'Sent': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Accepted': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Negotiating': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Declined': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Expired': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const columns = [
    {
      key: 'candidate.full_name',
      label: t('Candidate'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.candidate?.first_name} {row.candidate?.last_name}</div>
          <div className="text-xs text-gray-500">{row.job?.title}</div>
        </div>
      )
    },
    {
      key: 'salary',
      label: t('Salary'),
      render: (value, row) => (
        <div>
          <div className="font-medium">{window.appSettings?.formatCurrency(value)}</div>
        </div>
      )
    },
    {
      key: 'start_date',
      label: t('Start Date'),
      sortable: false,
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    },
    {
      key: 'expiration_date',
      label: t('Expires'),
      sortable: false,
      render: (value) => {
        if (!value) return '-';
        const date = new Date(value);
        const isExpired = date < new Date();
        return (
          <div className={isExpired ? 'text-red-600' : ''}>
            {window.appSettings?.formatDateTimeSimple(date, false)}
            {isExpired && <div className="text-xs">Expired</div>}
          </div>
        );
      }
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
      key: 'offer_date',
      label: t('Offer Date'),
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
      requiredPermission: 'view-offers'
    },
    {
      label: t('Edit'),
      icon: 'Edit',
      action: 'edit',
      className: 'text-amber-500',
      requiredPermission: 'edit-offers',
      condition: (item: any) => !['Accepted', 'Declined'].includes(item.status)
    },
    {
      label: t('Update Status'),
      icon: 'RefreshCw',
      action: 'update-status',
      className: 'text-green-500',
      requiredPermission: 'edit-offers',
      condition: (item: any) => !['Accepted', 'Declined'].includes(item.status)
    },
    {
      label: t('Delete'),
      icon: 'Trash2',
      action: 'delete',
      className: 'text-red-500',
      requiredPermission: 'delete-offers'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses'), disabled: true },
    { value: 'Draft', label: t('Draft') },
    { value: 'Sent', label: t('Sent') },
    { value: 'Accepted', label: t('Accepted') },
    { value: 'Negotiating', label: t('Negotiating') },
    { value: 'Declined', label: t('Declined') },
    { value: 'Expired', label: t('Expired') }
  ];

  const candidateOptions = [
    { value: '_empty_', label: t('All Candidates'), disabled: true },
    ...(candidates || []).map((candidate: any) => ({
      value: candidate.id.toString(),
      label: `${candidate.first_name} ${candidate.last_name}`
    }))
  ];

  const candidateSelectOptions = [
    { value: '_empty_', label: t('Select Candidate') },
    ...(candidates || []).map((candidate: any) => ({
      value: candidate.id.toString(),
      label: `${candidate.first_name} ${candidate.last_name}`
    }))
  ];

  const departmentOptions = [
    ...(departments || []).map((dept: any) => ({
      value: dept.id.toString(),
      label: `${dept.name} - ${dept.branch?.name || 'No Branch'}`
    }))
  ];

  const employeeOptions = [
    { value: '_empty_', label: t('Select Approver') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} - ${auth?.user?.name || 'Company'}`
    }))
  ];

  return (
    <PageTemplate
      title={t("Offers")}
      url="/hr/recruitment/offers"
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
              name: 'candidate_id',
              label: t('Candidate'),
              type: 'select',
              value: candidateFilter,
              onChange: setCandidateFilter,
              options: candidateOptions,
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
            router.get(route('hr.recruitment.offers.index'), {
              page: 1,
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              candidate_id: candidateFilter !== '_empty_' ? candidateFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={offers?.data || []}
          from={offers?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-offers',
            create: 'create-offers',
            edit: 'edit-offers',
            delete: 'delete-offers'
          }}
        />

        <Pagination
          from={offers?.from || 0}
          to={offers?.to || 0}
          total={offers?.total || 0}
          links={offers?.links}
          entityName={t("offers")}
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
              name: 'candidate_id',
              type: 'dependent-dropdown',
              dependentConfig: [
                {
                  name: 'candidate_id',
                  label: t('Candidate'),
                  required: true,
                  searchable: true,
                  options: candidateSelectOptions.filter(opt => opt.value !== '_empty_')
                },
                {
                  name: 'position',
                  label: t('Position'),
                  required: true,
                  apiEndpoint: '/hr/recruitment/offers/candidate/{candidate_id}/job',
                  showCurrentValue: true,
                  searchable: true
                },
                {
                  name: 'department_id',
                  label: t('Department'),
                  required: false,
                  apiEndpoint: '/hr/recruitment/offers/job/{position}/departments',
                  searchable: false,
                  disabled: true,
                  selectFirstOption: true
                }
              ]
            },

            {
              name: 'salary',
              label: t('Salary'),
              type: 'number',
              required: true,
              min: 0,
              step: 0.01
            },
            {
              name: 'start_date',
              label: t('Start Date'),
              type: 'date',
              required: true
            },
            {
              name: 'expiration_date',
              label: t('Expiration Date'),
              type: 'date',
              required: true
            },
            {
              name: 'approved_by',
              label: t('Approved By'),
              type: 'select',
              required: true,
              options: employeeOptions.filter(opt => opt.value !== '_empty_'),
              defaultValue: currentUser?.id?.toString()
            },
            {
              name: 'benefits',
              label: t('Benefits'),
              type: 'textarea'
            }
          ],
          modalSize: 'xl'
        }}
        initialData={formMode === 'create' ? {} : formMode === 'view' ? {
          ...currentItem,
          candidate_id: currentItem?.candidate ? `${currentItem.candidate.first_name} ${currentItem.candidate.last_name}` : currentItem?.candidate_id,
          start_date: currentItem?.start_date ? new Date(currentItem.start_date).toISOString().split('T')[0] : currentItem?.start_date,
          expiration_date: currentItem?.expiration_date ? new Date(currentItem.expiration_date).toISOString().split('T')[0] : currentItem?.expiration_date
        } : {
          ...currentItem,
          start_date: currentItem?.start_date ? new Date(currentItem.start_date).toISOString().split('T')[0] : currentItem?.start_date,
          expiration_date: currentItem?.expiration_date ? new Date(currentItem.expiration_date).toISOString().split('T')[0] : currentItem?.expiration_date
        }}
        title={
          formMode === 'create'
            ? t('Create New Offer')
            : formMode === 'edit'
              ? t('Edit Offer')
              : t('View Offer')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.candidate?.first_name} ${currentItem.candidate?.last_name} - ${currentItem.position}` : ''}
        entityName="offer"
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
                { value: 'Draft', label: t('Draft') },
                { value: 'Sent', label: t('Sent') },
                { value: 'Accepted', label: t('Accepted') },
                { value: 'Negotiating', label: t('Negotiating') },
                { value: 'Declined', label: t('Declined') },
                { value: 'Expired', label: t('Expired') }
              ]
            },

          ]
        }}
        initialData={currentItem ? { status: currentItem.status } : {}}
        title={t('Update Offer Status')}
        mode="edit"
      />
    </PageTemplate>
  );
}