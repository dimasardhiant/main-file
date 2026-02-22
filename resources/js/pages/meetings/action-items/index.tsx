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
import { Plus, CheckSquare, User, Calendar, AlertTriangle, Clock } from 'lucide-react';
import { format } from 'date-fns';

export default function ActionItems() {
  const { t } = useTranslation();
  const { auth, actionItems, meetings, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [priorityFilter, setPriorityFilter] = useState(pageFilters.priority || '_empty_');
  const [assigneeFilter, setAssigneeFilter] = useState(pageFilters.assigned_to || '_empty_');
  const [meetingFilter, setMeetingFilter] = useState(pageFilters.meeting_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isProgressModalOpen, setIsProgressModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || priorityFilter !== '_empty_' || assigneeFilter !== '_empty_' || meetingFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (priorityFilter !== '_empty_' ? 1 : 0) + (assigneeFilter !== '_empty_' ? 1 : 0) + (meetingFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('meetings.action-items.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      priority: priorityFilter !== '_empty_' ? priorityFilter : undefined,
      assigned_to: assigneeFilter !== '_empty_' ? assigneeFilter : undefined,
      meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('meetings.action-items.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      priority: priorityFilter !== '_empty_' ? priorityFilter : undefined,
      assigned_to: assigneeFilter !== '_empty_' ? assigneeFilter : undefined,
      meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined,
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
      toast.loading(t('Creating action item...'));
      
      router.post(route('meetings.action-items.store'), formData, {
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
            toast.error(`Failed to create action item: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating action item...'));
      
      router.put(route('meetings.action-items.update', currentItem.id), formData, {
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
            toast.error(`Failed to update action item: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting action item...'));
    
    router.delete(route('meetings.action-items.destroy', currentItem.id), {
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
          toast.error(`Failed to delete action item: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleProgressUpdate = (formData: any) => {
    toast.loading(t('Updating progress...'));
    
    router.put(route('meetings.action-items.update-progress', currentItem.id), {
      progress_percentage: formData.progress_percentage,
      notes: formData.notes
    }, {
      onSuccess: (page) => {
        setIsProgressModalOpen(false);
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
          toast.error(`Failed to update progress: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setPriorityFilter('_empty_');
    setAssigneeFilter('_empty_');
    setMeetingFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('meetings.action-items.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-action-items')) {
    pageActions.push({
      label: t('Add Action Item'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Meetings'), href: route('meetings.action-items.index') },
    { title: t('Action Items') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Not Started': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      case 'In Progress': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Completed': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Overdue': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'Low': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Medium': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'High': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
      case 'Critical': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getDaysRemaining = (dueDate: string, status: string) => {
    if (status === 'Completed') return null;
    const today = new Date();
    const due = new Date(dueDate);
    const diffTime = due.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
  };

  const columns = [
    { 
      key: 'title', 
      label: t('Action Item'), 
      sortable: true,
      render: (value, row) => (
        <div>
          <div className="font-medium">{value}</div>
          <div className="text-xs text-gray-500">{row.meeting?.title}</div>
        </div>
      )
    },
    { 
      key: 'assignee.name', 
      label: t('Assigned To'),
      render: (_, row) => (
        <div className="flex items-center gap-1">
          <User className="h-4 w-4 text-gray-500" />
          {row.assignee?.name || '-'}
        </div>
      )
    },
    { 
      key: 'due_date', 
      label: t('Due Date'),
      sortable: true,
      render: (value, row) => {
        const daysRemaining = getDaysRemaining(value, row.status);
        return (
          <div>
            <div className="flex items-center gap-1">
              <Calendar className="h-4 w-4 text-gray-500" />
              {window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()}
            </div>
            {daysRemaining !== null && (
              <div className={`text-xs ${daysRemaining < 0 ? 'text-red-600' : daysRemaining <= 3 ? 'text-orange-600' : 'text-gray-500'}`}>
                {daysRemaining < 0 ? `${Math.abs(daysRemaining)} days overdue` : 
                 daysRemaining === 0 ? 'Due today' : 
                 `${daysRemaining} days remaining`}
              </div>
            )}
          </div>
        );
      }
    },
    { 
      key: 'priority', 
      label: t('Priority'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getPriorityColor(value)}`}>
          {value === 'Critical' && <AlertTriangle className="h-3 w-3 mr-1" />}
          {t(value)}
        </span>
      )
    },
    { 
      key: 'progress_percentage', 
      label: t('Progress'),
      render: (value, row) => (
        <div className="w-full">
          <div className="flex items-center justify-between mb-1">
            <span className="text-xs text-gray-600">{value}%</span>
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(row.status)}`}>
              {row.status === 'Completed' && <CheckSquare className="h-3 w-3 mr-1" />}
              {row.status === 'Overdue' && <Clock className="h-3 w-3 mr-1" />}
              {t(row.status)}
            </span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-2">
            <div 
              className={`h-2 rounded-full ${
                row.status === 'Completed' ? 'bg-green-500' : 
                row.status === 'Overdue' ? 'bg-red-500' : 
                'bg-blue-500'
              }`}
              style={{ width: `${value}%` }}
            ></div>
          </div>
        </div>
      )
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-action-items'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-action-items'
    },
    { 
      label: t('Update Progress'), 
      icon: 'TrendingUp', 
      action: 'update-progress', 
      className: 'text-green-500',
      requiredPermission: 'edit-action-items'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-action-items'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') , disabled: true},
    { value: 'Not Started', label: t('Not Started') },
    { value: 'In Progress', label: t('In Progress') },
    { value: 'Completed', label: t('Completed') },
    { value: 'Overdue', label: t('Overdue') }
  ];

  const priorityOptions = [
    { value: '_empty_', label: t('All Priorities') , disabled: true },
    { value: 'Low', label: t('Low') },
    { value: 'Medium', label: t('Medium') },
    { value: 'High', label: t('High') },
    { value: 'Critical', label: t('Critical') }
  ];

  const assigneeOptions = [
    { value: '_empty_', label: t('All Assignees')  , disabled: true},
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  const meetingOptions = [
    { value: '_empty_', label: t('All Meetings') , disabled: true},
    ...(meetings || []).map((meeting: any) => ({
      value: meeting.id.toString(),
      label: `${meeting.title} - ${format(new Date(meeting.meeting_date), 'MMM dd, yyyy')}`
    }))
  ];

  const meetingSelectOptions = [
    { value: '_empty_', label: t('Select Meeting') },
    ...(meetings || []).map((meeting: any) => ({
      value: meeting.id.toString(),
      label: `${meeting.title} - ${format(new Date(meeting.meeting_date), 'MMM dd, yyyy')}`
    }))
  ];

  const employeeSelectOptions = [
    { value: '_empty_', label: t('Select Assignee') },
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Action Items")} 
      url="/meetings/action-items"
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
              name: 'priority',
              label: t('Priority'),
              type: 'select',
              value: priorityFilter,
              onChange: setPriorityFilter,
              options: priorityOptions
            },
            {
              name: 'assigned_to',
              label: t('Assignee'),
              type: 'select',
              value: assigneeFilter,
              onChange: setAssigneeFilter,
              options: assigneeOptions,
              searchable  : true
            },
            {
              name: 'meeting_id',
              label: t('Meeting'),
              type: 'select',
              value: meetingFilter,
              onChange: setMeetingFilter,
              options: meetingOptions,
              searchable  : true
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
            router.get(route('meetings.action-items.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              priority: priorityFilter !== '_empty_' ? priorityFilter : undefined,
              assigned_to: assigneeFilter !== '_empty_' ? assigneeFilter : undefined,
              meeting_id: meetingFilter !== '_empty_' ? meetingFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={actionItems?.data || []}
          from={actionItems?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-action-items',
            create: 'create-action-items',
            edit: 'edit-action-items',
            delete: 'delete-action-items'
          }}
        />

        <Pagination
          from={actionItems?.from || 0}
          to={actionItems?.to || 0}
          total={actionItems?.total || 0}
          links={actionItems?.links}
          entityName={t("action items")}
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
              name: 'meeting_id', 
              label: t('Meeting'), 
              type: 'select', 
              required: true,
              options: meetingSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'title', 
              label: t('Action Item Title'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea',
              rows: 3
            },
            { 
              name: 'assigned_to', 
              label: t('Assign To'), 
              type: 'select', 
              required: true,
              options: employeeSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'due_date', 
              label: t('Due Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'priority', 
              label: t('Priority'), 
              type: 'select', 
              required: true,
              options: priorityOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'progress_percentage', 
              label: t('Progress (%)'), 
              type: 'number',
              min: 0,
              max: 100,
              helpText: t('Current completion percentage')
            },
            { 
              name: 'notes', 
              label: t('Notes'), 
              type: 'textarea',
              rows: 2
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          due_date: currentItem.due_date ? window.appSettings.formatDateTimeSimple(currentItem.due_date, false) : currentItem.due_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add Action Item')
            : formMode === 'edit'
              ? t('Edit Action Item')
              : t('View Action Item')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.title || ''}
        entityName="action item"
      />

      <CrudFormModal
        isOpen={isProgressModalOpen}
        onClose={() => setIsProgressModalOpen(false)}
        onSubmit={handleProgressUpdate}
        formConfig={{
          fields: [
            {
              name: 'progress_percentage',
              label: t('Progress Percentage'),
              type: 'number',
              required: true,
              min: 0,
              max: 100,
              helpText: t('Enter completion percentage (0-100)')
            },
            {
              name: 'notes',
              label: t('Progress Notes'),
              type: 'textarea',
              rows: 3,
              helpText: t('Optional notes about the progress')
            }
          ],
          modalSize: 'md'
        }}
        initialData={{
          progress_percentage: currentItem?.progress_percentage || 0,
          notes: currentItem?.notes || ''
        }}
        title={t('Update Progress')}
        mode="edit"
        submitButtonText={t('Update Progress')}
      />
    </PageTemplate>
  );
}