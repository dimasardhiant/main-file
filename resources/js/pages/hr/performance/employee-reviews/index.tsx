// pages/hr/performance/employee-reviews/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Plus, ClipboardList } from 'lucide-react';
import { Badge } from '@/components/ui/badge';

export default function EmployeeReviews() {
  const { t } = useTranslation();
  const { auth, reviews, employees, reviewCycles, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedReviewer, setSelectedReviewer] = useState(pageFilters.reviewer_id || '');
  const [selectedReviewCycle, setSelectedReviewCycle] = useState(pageFilters.review_cycle_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedStatus !== 'all' || 
           searchTerm !== '' || 
           selectedEmployee !== '' || 
           selectedReviewer !== '' || 
           selectedReviewCycle !== '' ||
           dateFrom !== '' ||
           dateTo !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedStatus !== 'all' ? 1 : 0) + 
           (searchTerm ? 1 : 0) + 
           (selectedEmployee ? 1 : 0) + 
           (selectedReviewer ? 1 : 0) + 
           (selectedReviewCycle ? 1 : 0) +
           (dateFrom ? 1 : 0) +
           (dateTo ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.performance.employee-reviews.index'), { 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      reviewer_id: selectedReviewer || undefined,
      review_cycle_id: selectedReviewCycle || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.performance.employee-reviews.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      reviewer_id: selectedReviewer || undefined,
      review_cycle_id: selectedReviewCycle || undefined,
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
        router.visit(route('hr.performance.employee-reviews.show', item.id));
        break;
      case 'conduct':
        router.visit(route('hr.performance.employee-reviews.conduct', item.id));
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'update-status':
        handleUpdateStatus(item);
        break;
    }
  };
  
  const handleAddNew = () => {
    router.visit(route('hr.performance.employee-reviews.create'));
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting employee review...'));
    
    router.delete(route('hr.performance.employee-reviews.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete review: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleUpdateStatus = (review: any) => {
    // Determine next status based on current status
    let nextStatus = 'in_progress';
    if (review.status === 'scheduled') {
      nextStatus = 'in_progress';
    } else if (review.status === 'in_progress') {
      nextStatus = 'completed';
    } else {
      nextStatus = 'scheduled';
    }
    
    toast.loading(t('Updating review status to {{status}}...', { status: t(nextStatus) }));
    
    router.put(route('hr.performance.employee-reviews.update-status', review.id), { status: nextStatus }, {
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
          toast.error(t('Failed to update review status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedReviewer('');
    setSelectedReviewCycle('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.performance.employee-reviews.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Schedule Review" button if user has permission
  if (hasPermission(permissions, 'create-employee-reviews')) {
    pageActions.push({
      label: t('Schedule Review'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Performance'), href: route('hr.performance.indicator-categories.index') },
    { title: t('Employee Reviews') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'employee.full_name', 
      label: t('Employee'),
      render: (value: string, row: any) => {
        const firstName = row.employee?.name || '';
        const employeeId = row.employee?.employee?.employee_id || '';
        return `${firstName} `;
      }
    },
    { 
      key: 'reviewer.full_name', 
      label: t('Reviewer'),
      render: (value: string, row: any) => {
        const firstName = row.reviewer?.name || '';
        const employeeId = row.reviewer?.employee?.employee_id || '';
        return `${firstName}`;
      }
    },
    { 
      key: 'review_cycle.name', 
      label: t('Review Cycle'),
      render: (value: string, row: any) => row.review_cycle?.name || '-'
    },
    { 
      key: 'review_date', 
      label: t('Review Date'),
      sortable: true,
      render: (value: string) => value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'
    },
    { 
      key: 'overall_rating', 
      label: t('Rating'),
      render: (value: number) => value ? value.toFixed(1) : '-'
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value: string) => {
        let statusClass = '';
        let statusText = '';
        
        switch(value) {
          case 'scheduled':
            statusClass = 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20';
            statusText = t('Scheduled');
            break;
          case 'in_progress':
            statusClass = 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20';
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
          <Badge variant="outline" className={statusClass}>
            {statusText}
          </Badge>
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
      requiredPermission: 'view-employee-reviews'
    },
    { 
      label: t('Conduct Review'), 
      icon: 'ClipboardList', 
      action: 'conduct', 
      className: 'text-green-500',
      requiredPermission: 'edit-employee-reviews',
      condition: (item: any) => item.status !== 'completed'
    },
    { 
      label: t('Update Status'), 
      icon: 'RefreshCw', 
      action: 'update-status', 
      className: 'text-amber-500',
      requiredPermission: 'edit-employee-reviews'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-employee-reviews',
      condition: (item: any) => item.status !== 'completed'
    }
  ];

  // Prepare filter options
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'scheduled', label: t('Scheduled') },
    { value: 'in_progress', label: t('In Progress') },
    { value: 'completed', label: t('Completed') }
  ];

  // Prepare employee options
  const employeeOptions = [
    { value: '', label: t('Select Employee'), disabled: true },
    ...(employees || []).map((employee: any) => ({
      value: employee.id.toString(),
      label: `${employee.name}  (${employee.employee_id})`
    }))
  ];

  // Prepare review cycle options
  const reviewCycleOptions = [
    { value: '', label: t('Select Review Cycle'), disabled: true },
    ...(reviewCycles || []).map((cycle: any) => ({
      value: cycle.id.toString(),
      label: cycle.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Employee Reviews")} 
      url="/hr/performance/employee-reviews"
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
              name: 'reviewer_id',
              label: t('Reviewer'),
              type: 'select',
              value: selectedReviewer,
              onChange: setSelectedReviewer,
              options: employeeOptions,
              searchable: true
            },
            {
              name: 'review_cycle_id',
              label: t('Review Cycle'),
              type: 'select',
              value: selectedReviewCycle,
              onChange: setSelectedReviewCycle,
              options: reviewCycleOptions,
              searchable: true
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
              label: t('From Date'),
              type: 'date',
              value: dateFrom,
              onChange: setDateFrom
            },
            {
              name: 'date_to',
              label: t('To Date'),
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
            router.get(route('hr.performance.employee-reviews.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              reviewer_id: selectedReviewer || undefined,
              review_cycle_id: selectedReviewCycle || undefined,
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
          data={reviews?.data || []}
          from={reviews?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-employee-reviews',
            create: 'create-employee-reviews',
            edit: 'edit-employee-reviews',
            delete: 'delete-employee-reviews'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={reviews?.from || 0}
          to={reviews?.to || 0}
          total={reviews?.total || 0}
          links={reviews?.links}
          entityName={t("employee reviews")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={`${currentItem?.employee?.first_name || ''} ${currentItem?.employee?.last_name || ''}'s review`}
        entityName="employee review"
      />
    </PageTemplate>
  );
}