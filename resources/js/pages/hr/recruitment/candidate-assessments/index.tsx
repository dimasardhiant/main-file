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

export default function CandidateAssessments() {
  const { t } = useTranslation();
  const { auth, assessments, candidates, employees, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [candidateFilter, setCandidateFilter] = useState(pageFilters.candidate_id || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
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
    router.get(route('hr.recruitment.candidate-assessments.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      candidate_id: candidateFilter !== '_empty_' ? candidateFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.recruitment.candidate-assessments.index'), { 
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
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    if (formMode === 'create') {
      toast.loading(t('Creating assessment...'));

      router.post(route('hr.recruitment.candidate-assessments.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Assessment created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to create assessment: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating assessment...'));

      router.put(route('hr.recruitment.candidate-assessments.update', currentItem.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Assessment updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(`Failed to update assessment: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting assessment...'));

    router.delete(route('hr.recruitment.candidate-assessments.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Assessment deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(`Failed to delete assessment: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setCandidateFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.recruitment.candidate-assessments.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-candidate-assessments')) {
    pageActions.push({
      label: t('Add Assessment'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.candidate-assessments.index') },
    { title: t('Candidate Assessments') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Pass': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Fail': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Pending': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
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
        </div>
      )
    },
    { 
      key: 'assessment_name', 
      label: t('Assessment'),
      sortable: true,
      render: (value) => <div className="font-medium">{value}</div>
    },
    { 
      key: 'score', 
      label: t('Score'),
      render: (_, row) => {
        if (!row.score || !row.max_score) return '-';
        const percentage = Math.round((row.score / row.max_score) * 100);
        return (
          <div>
            <div className="font-medium">{row.score}/{row.max_score}</div>
            <div className="text-xs text-gray-500">{percentage}%</div>
          </div>
        );
      }
    },
    { 
      key: 'pass_fail_status', 
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
          {t(value)}
        </span>
      )
    },
    { 
      key: 'conductor.name', 
      label: t('Conducted By'),
      render: (_, row) => row.conductor?.name || '-'
    },
    { 
      key: 'assessment_date', 
      label: t('Date'),
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
      requiredPermission: 'view-candidate-assessments'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-candidate-assessments'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-candidate-assessments'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses')  ,disabled: true},
    { value: 'Pass', label: t('Pass') },
    { value: 'Fail', label: t('Fail') },
    { value: 'Pending', label: t('Pending') }
  ];

  const candidateOptions = [
    { value: '_empty_', label: t('All Candidates') , disabled: true},
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

  const employeeOptions = [
    ...(employees || []).map((emp: any) => ({
      value: emp.id.toString(),
      label: emp.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Candidate Assessments")} 
      url="/hr/recruitment/candidate-assessments"
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
            router.get(route('hr.recruitment.candidate-assessments.index'), { 
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
          data={assessments?.data || []}
          from={assessments?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-candidate-assessments',
            create: 'create-candidate-assessments',
            edit: 'edit-candidate-assessments',
            delete: 'delete-candidate-assessments'
          }}
        />

        <Pagination
          from={assessments?.from || 0}
          to={assessments?.to || 0}
          total={assessments?.total || 0}
          links={assessments?.links}
          entityName={t("assessments")}
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
              required: true,
              options: candidateSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable: true
            },
            { 
              name: 'assessment_name', 
              label: t('Assessment Name'), 
              type: 'text', 
              required: true 
            },
            { 
              name: 'score', 
              label: t('Score'), 
              type: 'number',
              min: 0
            },
            { 
              name: 'max_score', 
              label: t('Max Score'), 
              type: 'number',
              min: 1
            },
            { 
              name: 'pass_fail_status', 
              label: t('Status'), 
              type: 'select', 
              required: true,
              options: statusOptions.filter(opt => opt.value !== '_empty_')
            },
            { 
              name: 'conducted_by', 
              label: t('Conducted By'), 
              type: 'select', 
              required: true,
              options: employeeOptions,
              placeholder: t('Select Employee'),
              searchable: true
            },
            { 
              name: 'assessment_date', 
              label: t('Assessment Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'comments', 
              label: t('Comments'), 
              type: 'textarea' 
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          assessment_date: currentItem.assessment_date ? window.appSettings.formatDateTimeSimple(currentItem.assessment_date, false) : currentItem.assessment_date
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Assessment')
            : formMode === 'edit'
              ? t('Edit Assessment')
              : t('View Assessment')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.candidate?.first_name} ${currentItem.candidate?.last_name} - ${currentItem.assessment_name}` : ''}
        entityName="assessment"
      />
    </PageTemplate>
  );
}