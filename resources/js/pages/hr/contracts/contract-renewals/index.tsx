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
import { Plus, RefreshCw, User, Calendar, DollarSign, CheckCircle, XCircle, Clock, Play } from 'lucide-react';
import { format } from 'date-fns';

export default function ContractRenewals() {
  const { t } = useTranslation();
  const { auth, contractRenewals, contracts, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [contractFilter, setContractFilter] = useState(pageFilters.contract_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isApprovalModalOpen, setIsApprovalModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [approvalAction, setApprovalAction] = useState<'approve' | 'reject'>('approve');
  
  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || contractFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (contractFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.contracts.contract-renewals.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      contract_id: contractFilter !== '_empty_' ? contractFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.contracts.contract-renewals.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      contract_id: contractFilter !== '_empty_' ? contractFilter : undefined,
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
      case 'approve':
        setApprovalAction('approve');
        setIsApprovalModalOpen(true);
        break;
      case 'reject':
        setApprovalAction('reject');
        setIsApprovalModalOpen(true);
        break;
      case 'process':
        if (confirm(t('Are you sure you want to process this renewal? This will update the original contract.'))) {
          toast.loading(t('Processing renewal...'));
          
          router.put(route('hr.contracts.contract-renewals.process', item.id), {}, {
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
                toast.error(t(errors));
              } else {
                toast.error(t('Failed to process renewal: {{errors}}', { errors: Object.values(errors).join(', ') }));
              }
            }
          });
        }
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    // Convert comma-separated strings to arrays
    if (formData.new_allowances && typeof formData.new_allowances === 'string') {
      formData.new_allowances = formData.new_allowances.split(',').map((item: string) => {
        const parts = item.trim().split(':');
        return { name: parts[0]?.trim(), amount: parseFloat(parts[1]?.trim()) || 0 };
      }).filter(item => item.name);
    }
    if (formData.new_benefits && typeof formData.new_benefits === 'string') {
      formData.new_benefits = formData.new_benefits.split(',').map((item: string) => item.trim()).filter(Boolean);
    }

    if (formMode === 'create') {
      toast.loading(t('Creating contract renewal...'));

      router.post(route('hr.contracts.contract-renewals.store'), formData, {
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
            toast.error(t('Failed to create contract renewal: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating contract renewal...'));

      router.put(route('hr.contracts.contract-renewals.update', currentItem.id), formData, {
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
            toast.error(t('Failed to update contract renewal: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting contract renewal...'));

    router.delete(route('hr.contracts.contract-renewals.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete contract renewal: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleApprovalSubmit = (formData: any) => {
    const route_name = approvalAction === 'approve' 
      ? 'hr.contracts.contract-renewals.approve' 
      : 'hr.contracts.contract-renewals.reject';
    
    toast.loading(t(approvalAction === 'approve' ? 'Approving renewal...' : 'Rejecting renewal...'));
    
    router.put(route(route_name, currentItem.id), { 
      approval_notes: formData.approval_notes || undefined 
    }, {
      onSuccess: (page) => {
        setIsApprovalModalOpen(false);
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
          toast.error(t('Failed to process approval: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setContractFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.contracts.contract-renewals.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-contract-renewals')) {
    pageActions.push({
      label: t('Add Renewal'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Contract Management'), href: route('hr.contracts.contract-renewals.index') },
    { title: t('Contract Renewals') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Pending': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Approved': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Rejected': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Processed': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'Pending': return <Clock className="h-3 w-3" />;
      case 'Approved': return <CheckCircle className="h-3 w-3" />;
      case 'Rejected': return <XCircle className="h-3 w-3" />;
      case 'Processed': return <Play className="h-3 w-3" />;
      default: return <Clock className="h-3 w-3" />;
    }
  };

  const getTotalCompensation = (basicSalary: number, allowances: any[]) => {
    let total = basicSalary || 0;
    if (allowances && Array.isArray(allowances)) {
      allowances.forEach(allowance => {
        total += allowance?.amount || 0;
      });
    }
    return total;
  };

  const columns = [
    { 
      key: 'renewal_number', 
      label: t('Renewal #'), 
      sortable: true,
      render: (value, row) => (
        <div className="flex items-center gap-2">
          <RefreshCw className="h-4 w-4 text-gray-500" />
          <div>
            <div className="font-medium">{value}</div>
            <div className="text-xs text-gray-500">{row.contract?.contract_number}</div>
          </div>
        </div>
      )
    },
    { 
      key: 'contract.employee.name', 
      label: t('Employee'),
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <User className="h-4 w-4 text-gray-500" />
          {row.contract?.employee?.name || '-'}
        </div>
      )
    },
    { 
      key: 'current_end_date', 
      label: t('Renewal Period'),
      sortable: true,
      render: (value, row) => (
        <div>
          <div className="flex items-center gap-1">
            <Calendar className="h-4 w-4 text-gray-500" />
            <span className="text-sm">Current ends: {window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()}</span>
          </div>
          <div className="text-xs text-gray-500">
            New: {window.appSettings?.formatDateTimeSimple(row.new_start_date, false) || new Date(row.new_start_date).toLocaleDateString()} - {window.appSettings?.formatDateTimeSimple(row.new_end_date, false) || new Date(row.new_end_date).toLocaleDateString()}
          </div>
        </div>
      )
    },
    { 
      key: 'new_basic_salary', 
      label: t('New Compensation'),
      render: (value, row) => {
        const total = getTotalCompensation(value, row.new_allowances);
        return (
          <div className="flex items-center gap-1">
            <DollarSign className="h-4 w-4 text-gray-500" />
            <div>
              <div className="font-medium">{window.appSettings?.formatCurrency(total)}</div>
              <div className="text-xs text-gray-500">
                Base: {window.appSettings?.formatCurrency(value)}
                {row.new_allowances && Array.isArray(row.new_allowances) && row.new_allowances.length > 0 && (
                  <span> + {row.new_allowances.length} allowances</span>
                )}
              </div>
            </div>
          </div>
        );
      }
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
          {getStatusIcon(value)}
          {t(value)}
        </span>
      )
    },
    { 
      key: 'requester.name', 
      label: t('Requested By'),
      render: (_, row) => row.requester?.name || '-'
    },
    { 
      key: 'approved_at', 
      label: t('Approved'),
      render: (value, row) => {
        if (!value) return '-';
        return (
          <div>
            <div className="text-sm">{window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()}</div>
            <div className="text-xs text-gray-500">{row.approver?.name}</div>
          </div>
        );
      }
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-contract-renewals'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-contract-renewals',
      condition: (item: any) => item.status === 'Pending'
    },
    { 
      label: t('Approve'), 
      icon: 'CheckCircle', 
      action: 'approve', 
      className: 'text-green-500',
      requiredPermission: 'approve-contract-renewals',
      condition: (item: any) => item.status === 'Pending'
    },
    { 
      label: t('Reject'), 
      icon: 'XCircle', 
      action: 'reject', 
      className: 'text-red-500',
      requiredPermission: 'reject-contract-renewals',
      condition: (item: any) => item.status === 'Pending'
    },
    { 
      label: t('Process'), 
      icon: 'Play', 
      action: 'process', 
      className: 'text-purple-500',
      requiredPermission: 'edit-contract-renewals',
      condition: (item: any) => item.status === 'Approved'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-contract-renewals',
      condition: (item: any) => item.status !== 'Processed'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses'), disabled: true },
    { value: 'Pending', label: t('Pending') },
    { value: 'Approved', label: t('Approved') },
    { value: 'Rejected', label: t('Rejected') },
    { value: 'Processed', label: t('Processed') }
  ];

  const contractOptions = [
    { value: '_empty_', label: t('All Contracts'), disabled: true },
    ...(contracts || []).map((contract: any) => ({
      value: contract.id.toString(),
      label: `${contract.contract_number} - ${contract.employee?.name} (Expires: ${format(new Date(contract.end_date), 'MMM dd, yyyy')})`
    }))
  ];

  const contractSelectOptions = [
    { value: '_empty_', label: t('Select Contract') },
    ...(contracts || []).map((contract: any) => ({
      value: contract.id.toString(),
      label: `${contract.contract_number} - ${contract.employee?.name}`
    }))
  ];

  const employeeOptions = [
    { value: '_empty_', label: t('Select Requester') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Contract Renewals")} 
      url="/hr/contracts/contract-renewals"
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
              name: 'contract_id',
              label: t('Contract'),
              type: 'select',
              value: contractFilter,
              onChange: setContractFilter,
              options: contractOptions,
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
            router.get(route('hr.contracts.contract-renewals.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              contract_id: contractFilter !== '_empty_' ? contractFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={contractRenewals?.data || []}
          from={contractRenewals?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-contract-renewals',
            create: 'create-contract-renewals',
            edit: 'edit-contract-renewals',
            delete: 'delete-contract-renewals'
          }}
        />

        <Pagination
          from={contractRenewals?.from || 0}
          to={contractRenewals?.to || 0}
          total={contractRenewals?.total || 0}
          links={contractRenewals?.links}
          entityName={t("contract renewals")}
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
              name: 'contract_id', 
              label: t('Contract'), 
              type: 'select', 
              required: true,
              options: contractSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'new_start_date', 
              label: t('New Start Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'new_end_date', 
              label: t('New End Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'new_basic_salary', 
              label: t('New Basic Salary'), 
              type: 'number', 
              required: true,
              min: 0,
              step: 0.01
            },
            { 
              name: 'new_allowances', 
              label: t('New Allowances'), 
              type: 'text',
              helpText: t('Format: Name:Amount, Name:Amount (e.g., Transport:500, Meal:300)')
            },
            { 
              name: 'new_benefits', 
              label: t('New Benefits'), 
              type: 'text',
              helpText: t('Comma-separated list of benefits')
            },
            { 
              name: 'new_terms_conditions', 
              label: t('New Terms & Conditions'), 
              type: 'textarea',
              rows: 4
            },
            { 
              name: 'changes_summary', 
              label: t('Changes Summary'), 
              type: 'textarea',
              rows: 3,
              helpText: t('Summary of changes from current contract')
            },
            { 
              name: 'reason', 
              label: t('Reason for Renewal'), 
              type: 'textarea',
              rows: 2
            },
            { 
              name: 'requested_by', 
              label: t('Requested By'), 
              type: 'select', 
              required: true,
              options: employeeOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          new_allowances: currentItem.new_allowances && Array.isArray(currentItem.new_allowances) ? currentItem.new_allowances.map((a: any) => `${a.name}:${a.amount}`).join(', ') : '',
          new_benefits: currentItem.new_benefits && Array.isArray(currentItem.new_benefits) ? currentItem.new_benefits.join(', ') : ''
        } : null}
        title={
          formMode === 'create'
            ? t('Add Contract Renewal')
            : formMode === 'edit'
              ? t('Edit Contract Renewal')
              : t('View Contract Renewal')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.renewal_number || ''}
        entityName="contract renewal"
      />

      <CrudFormModal
        isOpen={isApprovalModalOpen}
        onClose={() => setIsApprovalModalOpen(false)}
        onSubmit={handleApprovalSubmit}
        formConfig={{
          fields: [
            {
              name: 'approval_notes',
              label: approvalAction === 'approve' ? t('Approval Notes (Optional)') : t('Rejection Reason'),
              type: 'textarea',
              required: approvalAction === 'reject',
              rows: 4
            }
          ]
        }}
        initialData={{}}
        title={approvalAction === 'approve' ? t('Approve Renewal') : t('Reject Renewal')}
        mode="edit"
        submitLabel={approvalAction === 'approve' ? t('Approve') : t('Reject')}
      />
    </PageTemplate>
  );
}