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

export default function InterviewFeedback() {
  const { t } = useTranslation();
  const { auth, interviewFeedback, interviews, interviewers, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [recommendationFilter, setRecommendationFilter] = useState(pageFilters.recommendation || '_empty_');
  const [interviewerFilter, setInterviewerFilter] = useState(pageFilters.interviewer_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  const [availableInterviewers, setAvailableInterviewers] = useState([]);
  const [selectedInterview, setSelectedInterview] = useState('');

  const handleInterviewChange = async (interviewId: string, clearInterviewers = true) => {
    if (clearInterviewers) {
      setAvailableInterviewers([]);
    }
    
    if (interviewId && interviewId !== '_empty_') {
      try {
        const response = await fetch(route('hr.recruitment.interview-feedback.get-interviewers', interviewId), {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const data = await response.json();
        setAvailableInterviewers(data || []);
      } catch (error) {
        console.error('Error fetching interviewers:', error);
        setAvailableInterviewers([]);
      }
    } else {
      setAvailableInterviewers([]);
    }
  };
  
  const hasActiveFilters = () => {
    return recommendationFilter !== '_empty_' || interviewerFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (recommendationFilter !== '_empty_' ? 1 : 0) + (interviewerFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.recruitment.interview-feedback.index'), { 
      page: 1,
      search: searchTerm || undefined,
      recommendation: recommendationFilter !== '_empty_' ? recommendationFilter : undefined,
      interviewer_id: interviewerFilter !== '_empty_' ? interviewerFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.recruitment.interview-feedback.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      recommendation: recommendationFilter !== '_empty_' ? recommendationFilter : undefined,
      interviewer_id: interviewerFilter !== '_empty_' ? interviewerFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = async (action: string, item: any) => {
    setCurrentItem(item);
    
    if ((action === 'edit' || action === 'view') && item.interview_id) {
      setSelectedInterview(item.interview_id.toString());
      await handleInterviewChange(item.interview_id.toString());
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
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setAvailableInterviewers([]);
    setSelectedInterview('');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    // Ensure interview_id is included from selectedInterview state
    if (selectedInterview) {
      formData.interview_id = selectedInterview;
    }
    
    // Convert interviewer_id array to comma-separated string if it's an array
    if (Array.isArray(formData.interviewer_id) && formData.interviewer_id.length > 0) {
      formData.interviewer_id = formData.interviewer_id.join(',');
    } else if (!formData.interviewer_id) {
      formData.interviewer_id = null;
    }
    
    if (formMode === 'create') {
      toast.loading(t('Submitting interview feedback...'));

      router.post(route('hr.recruitment.interview-feedback.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          setSelectedInterview('');
          setAvailableInterviewers([]);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Interview feedback submitted successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to submit interview feedback: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating interview feedback...'));

      router.put(route('hr.recruitment.interview-feedback.update', currentItem.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          setSelectedInterview('');
          setAvailableInterviewers([]);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Interview feedback updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to update interview feedback: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting interview feedback...'));

    router.delete(route('hr.recruitment.interview-feedback.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Interview feedback deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to delete interview feedback: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setRecommendationFilter('_empty_');
    setInterviewerFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.recruitment.interview-feedback.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-interview-feedback')) {
    pageActions.push({
      label: t('Add Feedback'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.interview-feedback.index') },
    { title: t('Interview Feedback') }
  ];

  const getRecommendationColor = (recommendation: string) => {
    switch (recommendation) {
      case 'Strong Hire': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Hire': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
      case 'Maybe': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Reject': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Strong Reject': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const columns = [
    { 
      key: 'interview.candidate.full_name', 
      label: t('Candidate'),
      render: (_, row) => (
        <div>
          <div className="font-medium">{row.interview?.candidate?.first_name} {row.interview?.candidate?.last_name}</div>
          <div className="text-xs text-gray-500">{row.interview?.job?.title}</div>
        </div>
      )
    },
    { 
      key: 'interview.round.name', 
      label: t('Round'),
      render: (_, row) => row.interview?.round?.name || '-'
    },
    { 
      key: 'interviewer_names', 
      label: t('Interviewer'),
      render: (_, row) => row.interviewer_names || '-'
    },
    { 
      key: 'overall_rating', 
      label: t('Overall Rating'),
      render: (value) => {
        if (!value) return '-';
        return (
          <div className="flex items-center">
            <span className="font-medium">{value}/5</span>
            <div className="ml-2 flex">
              {[1, 2, 3, 4, 5].map((star) => (
                <span key={star} className={`text-sm ${star <= value ? 'text-yellow-400' : 'text-gray-300'}`}>
                  ★
                </span>
              ))}
            </div>
          </div>
        );
      }
    },
    { 
      key: 'recommendation', 
      label: t('Recommendation'),
      render: (value) => {
        if (!value) return '-';
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getRecommendationColor(value)}`}>
            {t(value)}
          </span>
        );
      }
    },
    { 
      key: 'created_at', 
      label: t('Submitted'),
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
      requiredPermission: 'view-interview-feedback'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-interview-feedback'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-interview-feedback'
    }
  ];

  const recommendationOptions = [
    { value: '_empty_', label: t('All Recommendations') },
    { value: 'Strong Hire', label: t('Strong Hire') },
    { value: 'Hire', label: t('Hire') },
    { value: 'Maybe', label: t('Maybe') },
    { value: 'Reject', label: t('Reject') },
    { value: 'Strong Reject', label: t('Strong Reject') }
  ];

  const interviewerOptions = [
    { value: '_empty_', label: t('All Interviewers'), disabled: true },
    ...(interviewers || []).map((interviewer: any) => ({
      value: interviewer.id.toString(),
      label: interviewer.name
    }))
  ];

  const interviewOptions = [
    { value: '_empty_', label: t('Select Interview') },
    ...(interviews || []).map((interview: any) => ({
      value: interview.id.toString(),
      label: `${interview.candidate?.first_name} ${interview.candidate?.last_name} - ${interview.job?.title} (${interview.round?.name || 'No Round'})`
    }))
  ];

  const interviewerSelectOptions = [
    { value: '_empty_', label: t('Select Interviewer') },
    ...(interviewers || []).map((interviewer: any) => ({
      value: interviewer.id.toString(),
      label: interviewer.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Interview Feedback")} 
      url="/hr/recruitment/interview-feedback"
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
              name: 'recommendation',
              label: t('Recommendation'),
              type: 'select',
              value: recommendationFilter,
              onChange: setRecommendationFilter,
              options: recommendationOptions
            },
            {
              name: 'interviewer_id',
              label: t('Interviewer'),
              type: 'select',
              value: interviewerFilter,
              onChange: setInterviewerFilter,
              options: interviewerOptions,
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
            router.get(route('hr.recruitment.interview-feedback.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              recommendation: recommendationFilter !== '_empty_' ? recommendationFilter : undefined,
              interviewer_id: interviewerFilter !== '_empty_' ? interviewerFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={interviewFeedback?.data || []}
          from={interviewFeedback?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-interview-feedback',
            create: 'create-interview-feedback',
            edit: 'edit-interview-feedback',
            delete: 'delete-interview-feedback'
          }}
        />

        <Pagination
          from={interviewFeedback?.from || 0}
          to={interviewFeedback?.to || 0}
          total={interviewFeedback?.total || 0}
          links={interviewFeedback?.links}
          entityName={t("interview feedback")}
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
              name: 'interview_id', 
              label: t('Interview'), 
              type: 'select', 
              required: false,
              options: interviewOptions.filter(opt => opt.value !== '_empty_'),
              render: (field: any, formData: any, handleChange: any) => {
                const currentValue = selectedInterview || formData[field.name] || '';
                return (
                  <Select
                    value={currentValue}
                    onValueChange={(value) => {
                      setSelectedInterview(value);
                      handleChange(field.name, value);
                      // Clear interviewer selection when interview changes
                      setAvailableInterviewers([]);
                      handleChange('interviewer_id', []);
                      handleInterviewChange(value);
                    }}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder={t('Select Interview')} />
                    </SelectTrigger>
                    <SelectContent className="z-[60000]" searchable={true}>
                      {interviewOptions.filter(opt => opt.value !== '_empty_').map(option => (
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
              name: 'interviewer_id', 
              label: t('Interviewer'), 
              type: 'multi-select', 
              searchable: true,
              key: `interviewer-${selectedInterview}`,
              options: availableInterviewers.map((interviewer: any) => ({
                value: interviewer.id.toString(),
                label: interviewer.name
              }))
            },
            { 
              name: 'technical_rating', 
              label: t('Technical Rating (1-5)'), 
              type: 'number',
              min: 1,
              max: 5
            },
            { 
              name: 'communication_rating', 
              label: t('Communication Rating (1-5)'), 
              type: 'number',
              min: 1,
              max: 5
            },
            { 
              name: 'cultural_fit_rating', 
              label: t('Cultural Fit Rating (1-5)'), 
              type: 'number',
              min: 1,
              max: 5
            },
            { 
              name: 'overall_rating', 
              label: t('Overall Rating (1-5)'), 
              type: 'number',
              min: 1,
              max: 5
            },
            { 
              name: 'recommendation', 
              label: t('Recommendation'), 
              type: 'select',
              options: recommendationOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'strengths', 
              label: t('Strengths'), 
              type: 'textarea' 
            },
            { 
              name: 'weaknesses', 
              label: t('Weaknesses'), 
              type: 'textarea' 
            },
            { 
              name: 'comments', 
              label: t('Comments'), 
              type: 'textarea' 
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          interviewer_id: currentItem.interviewer_id ? currentItem.interviewer_id.split(',') : [],
          interview_id: currentItem.interview_id?.toString()
        } : null}
        title={
          formMode === 'create'
            ? t('Add Interview Feedback')
            : formMode === 'edit'
              ? t('Edit Interview Feedback')
              : t('View Interview Feedback')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.interview?.candidate?.first_name} ${currentItem.interview?.candidate?.last_name} - ${currentItem.interviewer?.name}` : ''}
        entityName="interview feedback"
      />
    </PageTemplate>
  );
}