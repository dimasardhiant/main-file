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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function Interviews() {
  const { t } = useTranslation();
  const { auth, interviews, candidates, interviewTypes, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [candidateFilter, setCandidateFilter] = useState(pageFilters.candidate_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
  const [selectedStatus, setSelectedStatus] = useState('');
  const [availableRounds, setAvailableRounds] = useState([]);
  const [selectedCandidate, setSelectedCandidate] = useState('');
  
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
    router.get(route('hr.recruitment.interviews.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      candidate_id: candidateFilter !== '_empty_' ? candidateFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.recruitment.interviews.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      candidate_id: candidateFilter !== '_empty_' ? candidateFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = async (action: string, item: any) => {
    setCurrentItem(item);
    
    if ((action === 'edit' || action === 'view') && item.candidate_id) {
      setSelectedCandidate(item.candidate_id.toString());
      await handleCandidateChange(item.candidate_id.toString());
    }
    
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
      case 'update-status':
        setCurrentItem(item);
        setSelectedStatus(item.status);
        setIsStatusModalOpen(true);
        break;
    }
  };
  
  const handleCandidateChange = async (candidateId: string, clearRounds = true) => {
    if (clearRounds) {
      setAvailableRounds([]);
    }
    
    if (candidateId && candidateId !== '_empty_') {
      try {
        const response = await fetch(route('hr.recruitment.interviews.rounds-by-candidate', candidateId), {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const data = await response.json();
        setAvailableRounds(data || []);
      } catch (error) {
        console.error('Error fetching rounds:', error);
        setAvailableRounds([]);
      }
    } else {
      setAvailableRounds([]);
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setAvailableRounds([]);
    setSelectedCandidate('');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    // Ensure candidate_id is included from selectedCandidate state
    if (selectedCandidate) {
      formData.candidate_id = selectedCandidate;
    }
    
    if (formMode === 'create') {
      toast.loading(t('Scheduling interview...'));

      router.post(route('hr.recruitment.interviews.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          setSelectedCandidate('');
          setAvailableRounds([]);
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
            toast.error(`Failed to schedule interview: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating interview...'));

      router.put(route('hr.recruitment.interviews.update', currentItem.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          setSelectedCandidate('');
          setAvailableRounds([]);
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
            toast.error(`Failed to update interview: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting interview...'));

    router.delete(route('hr.recruitment.interviews.destroy', currentItem.id), {
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
          toast.error(`Failed to delete interview: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleUpdateStatus = (formData: any) => {
    if (!formData.status) return;
    
    toast.loading(t('Updating status...'));
    
    router.put(route('hr.recruitment.interviews.update-status', currentItem.id), { status: formData.status }, {
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
    
    router.get(route('hr.recruitment.interviews.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-interviews')) {
    pageActions.push({
      label: t('Schedule Interview'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.interviews.index') },
    { title: t('Interviews') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Scheduled': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Completed': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Cancelled': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'No-show': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
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
      key: 'round.name', 
      label: t('Round'),
      render: (_, row) => row.round?.name || '-'
    },
    { 
      key: 'interview_type.name', 
      label: t('Type'),
      render: (_, row) => row.interview_type?.name || '-'
    },
    { 
      key: 'scheduled_date', 
      label: t('Date & Time'),
      sortable: false,
      render: (_, row) => (
        <div>
          <div className="font-medium">{window.appSettings?.formatDateTimeSimple(row.scheduled_date, false) || new Date(row.scheduled_date).toLocaleDateString()}</div>
          <div className="text-xs text-gray-500">{window.appSettings.formatTime(row.scheduled_time)} ({row.duration} min)</div>
        </div>
      )
    },
    { 
      key: 'location', 
      label: t('Location'),
      render: (value, row) => {
        if (row.meeting_link) {
          return <span className="text-blue-600">{t('Online')}</span>;
        }
        return value || '-';
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
      key: 'feedback_submitted', 
      label: t('Feedback'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
          value 
            ? 'bg-green-50 text-green-700 ring-green-600/20' 
            : 'bg-gray-50 text-gray-600 ring-gray-500/10'
        }`}>
          {value ? t('Submitted') : t('Pending')}
        </span>
      )
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-interviews'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-interviews'
    },
    { 
      label: t('Update Status'), 
      icon: 'RefreshCw', 
      action: 'update-status', 
      className: 'text-green-500',
      requiredPermission: 'edit-interviews'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-interviews'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') },
    { value: 'Scheduled', label: t('Scheduled') },
    { value: 'Completed', label: t('Completed') },
    { value: 'Cancelled', label: t('Cancelled') },
    { value: 'No-show', label: t('No-show') }
  ];

  const candidateOptions = [
    { value: '_empty_', label: t('All Candidates') , disable: true },
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

  const interviewTypeOptions = [
    { value: '_empty_', label: t('Select Interview Type') },
    ...(interviewTypes || []).map((type: any) => ({
      value: type.id.toString(),
      label: type.name
    }))
  ];

  const employeeOptions = (employees || []).map((emp: any) => ({
    value: emp.id.toString(),
    label: emp.name || `${emp.first_name} ${emp.last_name}` || emp.employee_id
  }));

  return (
    <PageTemplate 
      title={t("Interviews")} 
      url="/hr/recruitment/interviews"
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
            router.get(route('hr.recruitment.interviews.index'), { 
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
          data={interviews?.data || []}
          from={interviews?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-interviews',
            create: 'create-interviews',
            edit: 'edit-interviews',
            delete: 'delete-interviews'
          }}
        />

        <Pagination
          from={interviews?.from || 0}
          to={interviews?.to || 0}
          total={interviews?.total || 0}
          links={interviews?.links}
          entityName={t("interviews")}
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
              label: t('Candidate'), 
              type: 'select', 
              required: false,
              options: candidateSelectOptions.filter(opt => opt.value !== '_empty_'),
              render: (field: any, formData: any, handleChange: any) => {
                const currentValue = selectedCandidate || formData[field.name] || '';
                return (
                  <Select
                    value={currentValue}
                    onValueChange={(value) => {
                      setSelectedCandidate(value);
                      handleChange(field.name, value);
                      // Clear round selection when candidate changes
                      setAvailableRounds([]);
                      handleChange('round_id', '');
                      handleCandidateChange(value);
                    }}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('Select Candidate')} />
                    </SelectTrigger>
                    <SelectContent className="z-[60000]" searchable={true}>
                      {candidateSelectOptions.filter(opt => opt.value !== '_empty_').map(option => (
                        <SelectItem key={option.value} value={String(option.value)}>
                          {option.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                );
              }
            },
            { 
              name: 'round_id', 
              label: t('Interview Round'), 
              type: 'select', 
              required: true,
              searchable: true,
              key: `round-${selectedCandidate}`,
              options: availableRounds.map((round: any) => ({
                value: round.id.toString(),
                label: round.name
              }))
            },
            { 
              name: 'interview_type_id', 
              label: t('Interview Type'), 
              type: 'select', 
              required: true,
              options: interviewTypeOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'scheduled_date', 
              label: t('Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'scheduled_time', 
              label: t('Time'), 
              type: 'time', 
              required: true 
            },
            { 
              name: 'duration', 
              label: t('Duration (minutes)'), 
              type: 'number', 
              required: true,
              min: 15,
              max: 480
            },
            { 
              name: 'location', 
              label: t('Location'), 
              type: 'text' 
            },
            { 
              name: 'meeting_link', 
              label: t('Meeting Link'), 
              type: 'text',
              placeholder: 'https://meet.google.com/xxx-xxxx-xxx'
            },
            { 
              name: 'interviewers', 
              label: t('Interviewers'), 
              type: 'multi-select', 
              required: true,
              options: employeeOptions
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          candidate_id: currentItem.candidate_id?.toString(),
          scheduled_date: currentItem.scheduled_date ? window.appSettings.formatDateTimeSimple(currentItem.scheduled_date, false) : currentItem.scheduled_date
        } : null}
        title={
          formMode === 'create'
            ? t('Schedule New Interview')
            : formMode === 'edit'
              ? t('Edit Interview')
              : t('View Interview')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.candidate?.first_name} ${currentItem.candidate?.last_name} - ${currentItem.round?.name}` : ''}
        entityName="interview"
      />

      {/* Status Update Modal */}
      <CrudFormModal
        isOpen={isStatusModalOpen}
        onClose={() => setIsStatusModalOpen(false)}
        onSubmit={handleUpdateStatus}
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
        title={t('Update Interview Status')}
        mode="edit"
        submitLabel={t('Update Status')}
      />
    </PageTemplate>
  );
}