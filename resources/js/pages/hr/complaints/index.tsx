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

export default function Complaints() {
  const { t } = useTranslation();
  const { auth, complaints, complainants, againstEmployees, hrPersonnel, complaintTypes, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedEmployee, setSelectedEmployee] = useState(pageFilters.employee_id || '');
  const [selectedAgainstEmployee, setSelectedAgainstEmployee] = useState(pageFilters.against_employee_id || '');
  const [selectedComplaintType, setSelectedComplaintType] = useState(pageFilters.complaint_type || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [isAssignModalOpen, setIsAssignModalOpen] = useState(false);
  const [isResolveModalOpen, setIsResolveModalOpen] = useState(false);
  const [isFollowUpModalOpen, setIsFollowUpModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedEmployee !== '' || 
           selectedAgainstEmployee !== '' ||
           selectedComplaintType !== '' || 
           selectedStatus !== 'all' || 
           dateFrom !== '' || 
           dateTo !== '' || 
           searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedEmployee !== '' ? 1 : 0) + 
           (selectedAgainstEmployee !== '' ? 1 : 0) +
           (selectedComplaintType !== '' ? 1 : 0) + 
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
    router.get(route('hr.complaints.index'), { 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      against_employee_id: selectedAgainstEmployee || undefined,
      complaint_type: selectedComplaintType || undefined,
      status: selectedStatus !== 'all' ? selectedStatus : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.complaints.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      employee_id: selectedEmployee || undefined,
      against_employee_id: selectedAgainstEmployee || undefined,
      complaint_type: selectedComplaintType || undefined,
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
      case 'assign':
        setIsAssignModalOpen(true);
        break;
      case 'resolve':
        setIsResolveModalOpen(true);
        break;
      case 'follow-up':
        setIsFollowUpModalOpen(true);
        break;
      case 'download-document':
        window.open(route('hr.complaints.download-document', item.id), '_blank');
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem({ employee_id: auth?.user?.id?.toString() });
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    const data = formData;
    
    if (formMode === 'create') {
      toast.loading(t('Creating complaint...'));

      router.post(route('hr.complaints.store'), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Complaint created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to create complaint: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating complaint...'));
      
      router.put(route('hr.complaints.update', currentItem.id), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Complaint updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to update complaint: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    }
  };
  
  const handleStatusChange = (formData: any) => {
    toast.loading(t('Updating complaint status...'));
    
    router.put(route('hr.complaints.change-status', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsStatusModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Complaint status updated successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to update complaint status: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleAssign = (formData: any) => {
    toast.loading(t('Assigning complaint...'));
    
    // Convert '_none_' to empty string
    if (formData.assigned_to === '_none_') {
      formData.assigned_to = '';
    }
    
    router.put(route('hr.complaints.assign', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsAssignModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Complaint assigned successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to assign complaint: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleResolve = (formData: any) => {
    toast.loading(t('Resolving complaint...'));
    
    router.put(route('hr.complaints.resolve', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsResolveModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Complaint resolved successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to resolve complaint: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleFollowUp = (formData: any) => {
    toast.loading(t('Updating follow-up information...'));
    
    router.put(route('hr.complaints.follow-up', currentItem.id), formData, {
      onSuccess: (page) => {
        setIsFollowUpModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Follow-up information updated successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to update follow-up information: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting complaint...'));
    
    router.delete(route('hr.complaints.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Complaint deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to delete complaint: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedEmployee('');
    setSelectedAgainstEmployee('');
    setSelectedComplaintType('');
    setSelectedStatus('all');
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.complaints.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Add New Complaint" button if user has permission
  if (hasPermission(permissions, 'create-complaints')) {
    pageActions.push({
      label: t('Add Complaint'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.complaints.index') },
    { title: t('Complaints') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'employee.name', 
      label: t('Complainant'), 
      render: (_, row) => {
        if (row.is_anonymous) {
          return (
            <div>
              <div className="font-medium">{t('Anonymous')}</div>
              <span className="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">
                {t('Anonymous')}
              </span>
            </div>
          );
        }
        return (
          <div>
            <div className="font-medium">{row.employee?.name || '-'}</div>
            <div className="text-xs text-gray-500">{row.employee?.employee_id || ''}</div>
          </div>
        );
      }
    },
    { 
      key: 'against_employee.name', 
      label: t('Against'),
      render: (_, row) => {
        if (!row.against_employee_id) return '-';
        return (
          <div>
            <div className="font-medium">{row.against_employee?.name || '-'}</div>
            <div className="text-xs text-gray-500">{row.against_employee?.employee_id || ''}</div>
          </div>
        );
      }
    },
    { 
      key: 'complaint_type', 
      label: t('Type'),
      render: (value) => value || '-'
    },
    { 
      key: 'subject', 
      label: t('Subject'),
      render: (value) => value || '-'
    },
    { 
      key: 'complaint_date', 
      label: t('Date'),
      sortable: true,
      render: (value) => value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => {
        const statusClasses = {
          'submitted': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'under investigation': 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
          'resolved': 'bg-green-50 text-green-700 ring-green-600/20',
          'dismissed': 'bg-red-50 text-red-700 ring-red-600/20'
        };
        
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClasses[value] || ''}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    { 
      key: 'assigned_user.name', 
      label: t('Assigned To'),
      render: (_, row) => row.assigned_user?.name || '-'
    },
    { 
      key: 'documents', 
      label: t('Documents'),
      render: (value, row) => value && value.trim() !== '' ? (
        <Button
          variant="outline"
          size="sm"
          className="flex items-center text-blue-500"
          onClick={(e) => {
            e.stopPropagation();
            handleAction('download-document', row);
          }}
        >
          {t('View Document')}
        </Button>
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
      requiredPermission: 'view-complaints'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-complaints'
    },
    { 
      label: t('Change Status'), 
      icon: 'RefreshCw', 
      action: 'change-status', 
      className: 'text-green-500',
      requiredPermission: 'edit-complaints'
    },
    { 
      label: t('Assign'), 
      icon: 'UserPlus', 
      action: 'assign', 
      className: 'text-purple-500',
      requiredPermission: 'assign-complaints',
      showWhen: (item) => item.status !== 'resolved' && item.status !== 'dismissed'
    },
    { 
      label: t('Resolve'), 
      icon: 'CheckCircle', 
      action: 'resolve', 
      className: 'text-indigo-500',
      requiredPermission: 'resolve-complaints',
      showWhen: (item) => item.status !== 'resolved' && item.status !== 'dismissed'
    },
    { 
      label: t('Follow-up'), 
      icon: 'Calendar', 
      action: 'follow-up', 
      className: 'text-teal-500',
      requiredPermission: 'resolve-complaints',
      showWhen: (item) => item.status === 'resolved'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-complaints'
    }
  ];

  // Prepare complainant options for filter
  const complainantOptions = [
    { value: '', label: t('All Employees'), disabled: true },
    ...(complainants || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare against employee options for filter
  const againstEmployeeOptions = [
    { value: '', label: t('All Employees'), disabled: true },
    ...(againstEmployees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: `${emp.name} (${emp.employee_id})`
    }))
  ];

  // Prepare complaint type options for filter
  const complaintTypeOptions = [
    { value: '', label: t('All Types'), disabled: true },
    ...(complaintTypes || []).map((type: string) => ({
      value: type,
      label: type
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'submitted', label: t('Submitted') },
    { value: 'under investigation', label: t('Under Investigation') },
    { value: 'resolved', label: t('Resolved') },
    { value: 'dismissed', label: t('Dismissed') }
  ];

  // Prepare complaint type options for form
  const complaintTypeFormOptions = [
    { value: 'Harassment', label: t('Harassment') },
    { value: 'Discrimination', label: t('Discrimination') },
    { value: 'Workplace Conditions', label: t('Workplace Conditions') },
    { value: 'Bullying', label: t('Bullying') },
    { value: 'Unfair Treatment', label: t('Unfair Treatment') },
    { value: 'Compensation Issues', label: t('Compensation Issues') },
    { value: 'Work Schedule', label: t('Work Schedule') },
    { value: 'Safety Concerns', label: t('Safety Concerns') },
    { value: 'Ethics Violation', label: t('Ethics Violation') },
    { value: 'Management Issues', label: t('Management Issues') }
  ];

  return (
    <PageTemplate 
      title={t("Complaints")} 
      url="/hr/complaints"
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
              label: t('Complainant'),
              type: 'select',
              value: selectedEmployee,
              onChange: setSelectedEmployee,
              options: complainantOptions,
              searchable : true
            },
            {
              name: 'against_employee_id',
              label: t('Against'),
              type: 'select',
              value: selectedAgainstEmployee,
              onChange: setSelectedAgainstEmployee,
              options: againstEmployeeOptions,
              searchable: true
            },
            {
              name: 'complaint_type',
              label: t('Type'),
              type: 'select',
              value: selectedComplaintType,
              onChange: setSelectedComplaintType,
              options: complaintTypeOptions,
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
            router.get(route('hr.complaints.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              employee_id: selectedEmployee || undefined,
              against_employee_id: selectedAgainstEmployee || undefined,
              complaint_type: selectedComplaintType || undefined,
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
          data={complaints?.data || []}
          from={complaints?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-complaints',
            create: 'create-complaints',
            edit: 'edit-complaints',
            delete: 'delete-complaints'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={complaints?.from || 0}
          to={complaints?.to || 0}
          total={complaints?.total || 0}
          links={complaints?.links}
          entityName={t("complaints")}
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
              label: t('Complainant'), 
              type: 'select', 
              required: true,
              options: complainantOptions.filter(opt => opt.value !== ''),
              searchable: true  
            },
            { 
              name: 'against_employee_id', 
              label: t('Against'), 
              type: 'select',
              options: [{ value: '_none_', label: t('Not Specified') }, ...againstEmployeeOptions.filter(opt => opt.value !== '')],
              searchable: true,
              conditional: (mode, formData) => {
                // Filter out the selected complainant from against employee options
                const filteredOptions = againstEmployeeOptions.filter(opt => opt.value !== formData.employee_id && opt.value !== '');
                return true;
              },
              render: (field, formData, handleChange) => {
                const filteredOptions = [{ value: '_none_', label: t('Not Specified') }, ...againstEmployeeOptions.filter(opt => opt.value !== formData.employee_id && opt.value !== '')];
                return (
                  <select
                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                    value={formData[field.name] || ''}
                    onChange={(e) => handleChange(field.name, e.target.value)}
                  >
                    {filteredOptions.map((opt) => (
                      <option key={opt.value} value={opt.value}>
                        {opt.label}
                      </option>
                    ))}
                  </select>
                );
              }
            },
            { 
              name: 'complaint_type', 
              label: t('Complaint Type'), 
              type: 'select', 
              required: true,
              options: complaintTypeFormOptions,
              searchable: true  
            },
            { 
              name: 'subject', 
              label: t('Subject'), 
              type: 'text',
              required: true
            },
            { 
              name: 'complaint_date', 
              label: t('Complaint Date'), 
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
              name: 'is_anonymous', 
              label: t('Submit Anonymously'), 
              type: 'checkbox'
            },
            ...(formMode === 'edit' ? [
              { 
                name: 'status', 
                label: t('Status'), 
                type: 'select',
                options: [
                  { value: 'submitted', label: 'Submitted' },
                  { value: 'under investigation', label: 'Under Investigation' },
                  { value: 'resolved', label: 'Resolved' },
                  { value: 'dismissed', label: 'Dismissed' }
                ]
              },
              { 
                name: 'assigned_to', 
                label: t('Assigned To'), 
                type: 'select',
                options: [{ value: '_none_', label: t('Not Assigned') }, ...hrPersonnel?.map((user: any) => ({
                  value: user.id.toString(),
                  label: user.name
                })) || []]
              },
              { 
                name: 'resolution_deadline', 
                label: t('Resolution Deadline'), 
                type: 'date'
              },
              { 
                name: 'investigation_notes', 
                label: t('Investigation Notes'), 
                type: 'textarea',
                showWhen: (formData) => ['under investigation', 'resolved', 'dismissed'].includes(formData.status)
              },
              { 
                name: 'resolution_action', 
                label: t('Resolution Action'), 
                type: 'textarea',
                showWhen: (formData) => ['resolved', 'dismissed'].includes(formData.status)
              },
              { 
                name: 'resolution_date', 
                label: t('Resolution Date'), 
                type: 'date',
                showWhen: (formData) => ['resolved', 'dismissed'].includes(formData.status)
              },
              { 
                name: 'follow_up_action', 
                label: t('Follow-up Action'), 
                type: 'textarea',
                showWhen: (formData) => formData.status === 'resolved'
              },
              { 
                name: 'follow_up_date', 
                label: t('Follow-up Date'), 
                type: 'date',
                showWhen: (formData) => formData.status === 'resolved'
              },
              { 
                name: 'feedback', 
                label: t('Feedback'), 
                type: 'textarea',
                showWhen: (formData) => formData.status === 'resolved'
              }
            ] : [])
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          complaint_date: currentItem.complaint_date ? window.appSettings.formatDateTimeSimple(currentItem.complaint_date, false) : currentItem.complaint_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Complaint')
            : formMode === 'edit'
              ? t('Edit Complaint')
              : t('View Complaint')
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
                { value: 'submitted', label: 'Submitted' },
                { value: 'under investigation', label: 'Under Investigation' },
                { value: 'resolved', label: 'Resolved' },
                { value: 'dismissed', label: 'Dismissed' }
              ],
              defaultValue: currentItem?.status
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Change Complaint Status')}
        mode="edit"
      />

      {/* Assign Modal */}
      <CrudFormModal
        isOpen={isAssignModalOpen}
        onClose={() => setIsAssignModalOpen(false)}
        onSubmit={handleAssign}
        formConfig={{
          fields: [
            { 
              name: 'assigned_to', 
              label: t('Assign To'), 
              type: 'select',
              required: true,
              options: hrPersonnel?.map((user: any) => ({
                value: user.id.toString(),
                label: user.name + ' (' + user.type + ')'
              })) || [], 
              defaultValue: currentItem?.assigned_to
            },
            { 
              name: 'resolution_deadline', 
              label: t('Resolution Deadline'), 
              type: 'date',
              defaultValue: currentItem?.resolution_deadline
            }
          ],
          modalSize: 'sm'
        }}
        initialData={currentItem}
        title={t('Assign Complaint')}
        mode="edit"
      />

      {/* Resolve Modal */}
      <CrudFormModal
        isOpen={isResolveModalOpen}
        onClose={() => setIsResolveModalOpen(false)}
        onSubmit={handleResolve}
        formConfig={{
          fields: [
            { 
              name: 'status', 
              label: t('Resolution Type'), 
              type: 'select',
              required: true,
              options: [
                { value: 'resolved', label: 'Resolved' },
                { value: 'dismissed', label: 'Dismissed' }
              ],
              defaultValue: 'resolved'
            },
            { 
              name: 'investigation_notes', 
              label: t('Investigation Notes'), 
              type: 'textarea',
              required: true
            },
            { 
              name: 'resolution_action', 
              label: t('Resolution Action'), 
              type: 'textarea',
              required: true
            },
            { 
              name: 'resolution_date', 
              label: t('Resolution Date'), 
              type: 'date',
              required: true,
              defaultValue: new Date().toISOString().split('T')[0]
            },
            { 
              name: 'follow_up_action', 
              label: t('Follow-up Action'), 
              type: 'textarea',
              showWhen: (formData) => formData.status === 'resolved'
            },
            { 
              name: 'follow_up_date', 
              label: t('Follow-up Date'), 
              type: 'date',
              showWhen: (formData) => formData.status === 'resolved' && formData.follow_up_action
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem}
        title={t('Resolve Complaint')}
        mode="edit"
      />

      {/* Follow-up Modal */}
      <CrudFormModal
        isOpen={isFollowUpModalOpen}
        onClose={() => setIsFollowUpModalOpen(false)}
        onSubmit={handleFollowUp}
        formConfig={{
          fields: [
            { 
              name: 'follow_up_action', 
              label: t('Follow-up Action'), 
              type: 'textarea',
              required: true,
              defaultValue: currentItem?.follow_up_action
            },
            { 
              name: 'follow_up_date', 
              label: t('Follow-up Date'), 
              type: 'date',
              required: true,
              defaultValue: currentItem?.follow_up_date || new Date().toISOString().split('T')[0]
            },
            { 
              name: 'feedback', 
              label: t('Feedback'), 
              type: 'textarea',
              defaultValue: currentItem?.feedback
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem}
        title={t('Update Follow-up Information')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.subject || ''}
        entityName="complaint"
      />
    </PageTemplate>
  );
}