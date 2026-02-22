import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import React from 'react';
import { Plus } from 'lucide-react';

export default function JobPostings() {
  const { t } = useTranslation();
  const { auth, jobPostings, filters: pageFilters = {}, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [publishedFilter, setPublishedFilter] = useState(pageFilters.is_published || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  
  const hasActiveFilters = () => {
    return statusFilter !== '_empty_' || publishedFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (statusFilter !== '_empty_' ? 1 : 0) + (publishedFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.recruitment.job-postings.index'), { 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      is_published: publishedFilter !== '_empty_' ? publishedFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.recruitment.job-postings.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      is_published: publishedFilter !== '_empty_' ? publishedFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = (action: string, item: any) => {
    switch (action) {
      case 'view':
        router.get(route('hr.recruitment.job-postings.show', item.id));
        break;
      case 'edit':
        router.get(route('hr.recruitment.job-postings.edit', item.id));
        break;
      case 'delete':
        setCurrentItem(item);
        setIsDeleteModalOpen(true);
        break;
      case 'publish':
        if (!globalSettings?.is_demo) {
          toast.loading(t('Publishing job posting...'));
        }
        router.put(route('hr.recruitment.job-postings.publish', item.id), {}, {
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
              toast.error(t('Failed to publish job posting: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
          }
        });
        break;
      case 'unpublish':
        if (!globalSettings?.is_demo) {
          toast.loading(t('Unpublishing job posting...'));
        }
        router.put(route('hr.recruitment.job-postings.unpublish', item.id), {}, {
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
              toast.error(t('Failed to unpublish job posting: {{errors}}', { errors: Object.values(errors).join(', ') }));
            }
          }
        });
        break;
    }
  };
  
  const handleAddNew = () => {
    router.get(route('hr.recruitment.job-postings.create'));
  };
  
  const handleFormSubmit = (formData: any) => {
    // This function is no longer needed as we use separate pages
  };
  
  
  const handleDeleteConfirm = () => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Deleting job posting...'));
    }

    router.delete(route('hr.recruitment.job-postings.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
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
          toast.error(t('Failed to delete job posting: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setStatusFilter('_empty_');
    setPublishedFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.recruitment.job-postings.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-job-postings')) {
    pageActions.push({
      label: t('Add Job Posting'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Recruitment'), href: route('hr.recruitment.job-postings.index') },
    { title: t('Job Postings') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Draft': return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
      case 'Published': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Closed': return 'bg-red-50 text-red-700 ring-red-600/10';
      default: return 'bg-yellow-50 text-yellow-700 ring-yellow-600/20';
    }
  };

  const columns = [
    { 
      key: 'job_code', 
      label: t('Code'), 
      sortable: true,
      render: (value) => <div className="font-mono text-sm">{value}</div>
    },
    { 
      key: 'title', 
      label: t('Title'), 
      sortable: true,
      render: (value, row) => (
        <div>
          <div className="font-medium">{value}</div>
          {row.is_featured && (
            <span className="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
              {t('Featured')}
            </span>
          )}
        </div>
      )
    },
    { 
      key: 'job_type.name', 
      label: t('Type'),
      render: (_, row) => row.job_type?.name || '-'
    },
    { 
      key: 'location.name', 
      label: t('Location'),
      render: (_, row) => row.location?.name || '-'
    },
    { 
      key: 'min_salary', 
      label: t('Salary Range'),
      render: (_, row) => {
        if (row.min_salary && row.max_salary) {
          return `${window.appSettings?.formatCurrency(row.min_salary)} - ${window.appSettings?.formatCurrency(row.max_salary)}`;
        } else if (row.min_salary) {
          return `${window.appSettings?.formatCurrency(row.min_salary)}+`;
        }
        return '-';
      }
    },
    { 
      key: 'candidates_count', 
      label: t('Applications'),
      render: (value) => <div className="text-center">{value || 0}</div>
    },
    { 
      key: 'is_published', 
      label: t('Published'),
      render: (value) => (
        <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${
          value 
            ? 'bg-green-50 text-green-700 ring-green-600/20' 
            : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
        }`}>
          {value ? t('Yes') : t('No')}
        </span>
      )
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
      key: 'application_deadline', 
      label: t('Deadline'),
      render: (value) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-job-postings'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-job-postings'
    },
    { 
      label: t('Publish'), 
      icon: 'Upload', 
      action: 'publish', 
      className: 'text-green-500',
      requiredPermission: 'publish-job-postings',
      condition: (item) => !item.is_published
    },
    { 
      label: t('Unpublish'), 
      icon: 'Download', 
      action: 'unpublish', 
      className: 'text-orange-500',
      requiredPermission: 'publish-job-postings',
      condition: (item) => item.is_published
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-job-postings'
    }
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses') },
    { value: 'Draft', label: t('Draft') },
    { value: 'Published', label: t('Published') },
    { value: 'Closed', label: t('Closed') }
  ];

  const publishedOptions = [
    { value: '_empty_', label: t('All') },
    { value: 'true', label: t('Published') },
    { value: 'false', label: t('Draft') }
  ];

  return (
    <PageTemplate 
      title={t("Job Postings")} 
      url="/hr/recruitment/job-postings"
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
              name: 'is_published',
              label: t('Published'),
              type: 'select',
              value: publishedFilter,
              onChange: setPublishedFilter,
              options: publishedOptions
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
            router.get(route('hr.recruitment.job-postings.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined,
              is_published: publishedFilter !== '_empty_' ? publishedFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={jobPostings?.data || []}
          from={jobPostings?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-job-postings',
            create: 'create-job-postings',
            edit: 'edit-job-postings',
            delete: 'delete-job-postings'
          }}
        />

        <Pagination
          from={jobPostings?.from || 0}
          to={jobPostings?.to || 0}
          total={jobPostings?.total || 0}
          links={jobPostings?.links}
          entityName={t("job postings")}
          onPageChange={(url) => router.get(url)}
        />
      </div>

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.title || ''}
        entityName="job posting"
      />
    </PageTemplate>
  );
}