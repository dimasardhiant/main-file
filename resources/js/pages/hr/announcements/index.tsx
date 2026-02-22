// pages/hr/announcements/index.tsx
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
import { Plus, BarChart, Layout } from 'lucide-react';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import MediaPicker from '@/components/MediaPicker';

export default function Announcements() {
  const { t } = useTranslation();
  const { auth, announcements, departments, branches, categories, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedCategory, setSelectedCategory] = useState(pageFilters.category || '');
  const [selectedDepartment, setSelectedDepartment] = useState(pageFilters.department_id || '');
  const [selectedBranch, setSelectedBranch] = useState(pageFilters.branch_id || '');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || '');
  const [selectedPriority, setSelectedPriority] = useState(pageFilters.priority || '');
  const [isFeatured, setIsFeatured] = useState(pageFilters.featured === 'true');
  const [dateFrom, setDateFrom] = useState(pageFilters.date_from || '');
  const [dateTo, setDateTo] = useState(pageFilters.date_to || '');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedCategory !== '' || 
           selectedDepartment !== '' ||
           selectedBranch !== '' ||
           selectedStatus !== '' ||
           selectedPriority !== '' ||
           isFeatured ||
           dateFrom !== '' || 
           dateTo !== '' || 
           searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedCategory !== '' ? 1 : 0) + 
           (selectedDepartment !== '' ? 1 : 0) +
           (selectedBranch !== '' ? 1 : 0) +
           (selectedStatus !== '' ? 1 : 0) +
           (selectedPriority !== '' ? 1 : 0) +
           (isFeatured ? 1 : 0) +
           (dateFrom !== '' ? 1 : 0) + 
           (dateTo !== '' ? 1 : 0) + 
           (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.announcements.index'), { 
      page: 1,
      search: searchTerm || undefined,
      category: selectedCategory || undefined,
      department_id: selectedDepartment || undefined,
      branch_id: selectedBranch || undefined,
      status: selectedStatus || undefined,
      priority: selectedPriority || undefined,
      featured: isFeatured ? 'true' : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.announcements.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      category: selectedCategory || undefined,
      department_id: selectedDepartment || undefined,
      branch_id: selectedBranch || undefined,
      status: selectedStatus || undefined,
      priority: selectedPriority || undefined,
      featured: isFeatured ? 'true' : undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAction = (action: string, item: any) => {
    setCurrentItem(item);
    
    switch (action) {
      case 'view':
        router.get(route('hr.announcements.show', item.id));
        break;
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
      case 'statistics':
        router.visit(route('hr.announcements.statistics', item.id));
        break;
      case 'download-attachment':
        window.open(route('hr.announcements.download-attachment', item.id), '_blank');
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleViewDashboard = () => {
    router.get(route('hr.announcements.dashboard'));
  };
  
  const handleFormSubmit = (formData: any) => {
    const data = formData;
    
    if (formMode === 'create') {
      toast.loading(t('Creating announcement...'));

      router.post(route('hr.announcements.store'), data, {
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
            toast.error(t('Failed to create announcement: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating announcement...'));
      
      router.put(route('hr.announcements.update', currentItem.id), data, {
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
            toast.error(t('Failed to update announcement: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting announcement...'));
    
    router.delete(route('hr.announcements.destroy', currentItem.id), {
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
          toast.error(t('Failed to delete announcement: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedCategory('');
    setSelectedDepartment('');
    setSelectedBranch('');
    setSelectedStatus('');
    setSelectedPriority('');
    setIsFeatured(false);
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.announcements.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Dashboard" button
  pageActions.push({
    label: t('Dashboard View'),
    icon: <Layout className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleViewDashboard
  });
  
  // Add the "Add New Announcement" button if user has permission
  if (hasPermission(permissions, 'create-announcements')) {
    pageActions.push({
      label: t('Add Announcement'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.announcements.index') },
    { title: t('Announcements') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'title', 
      label: t('Title'),
      sortable: true,
      render: (value, row) => {
        const badges = [];
        
        if (row.is_featured) {
          badges.push(
            <Badge key="featured" variant="secondary" className="bg-purple-50 text-purple-700 hover:bg-purple-50 ml-2">
              {t('Featured')}
            </Badge>
          );
        }
        
        if (row.is_high_priority) {
          badges.push(
            <Badge key="priority" variant="secondary" className="bg-red-50 text-red-700 hover:bg-red-50 ml-2">
              {t('High Priority')}
            </Badge>
          );
        }
        
        return (
          <div className="flex items-center flex-wrap gap-1">
            <span className="font-medium">{value}</span>
            {badges}
          </div>
        );
      }
    },
    { 
      key: 'category', 
      label: t('Category'),
      render: (value) => {
        const categoryClasses = {
          'company news': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'policy updates': 'bg-purple-50 text-purple-700 ring-purple-600/20',
          'events': 'bg-green-50 text-green-700 ring-green-600/20',
          'HR': 'bg-amber-50 text-amber-700 ring-amber-600/20',
          'IT updates': 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
        };
        
        const categoryClass = categoryClasses[value] || 'bg-gray-50 text-gray-700 ring-gray-600/20';
        
        return (
          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${categoryClass}`}>
            {value.charAt(0).toUpperCase() + value.slice(1)}
          </span>
        );
      }
    },
    { 
      key: 'date_range', 
      label: t('Date Range'),
      sortable: false,
      render: (_, row) => {
        
        const startDate = row.start_date ? (window.appSettings?.formatDateTimeSimple(row.start_date,false) || new Date(row.start_date).toLocaleString()) : '-'
        const endDate = row.end_date ? (window.appSettings?.formatDateTimeSimple(row.end_date,false) || new Date(row.end_date).toLocaleString()) : '-'
        
        // Determine status based on dates
        const today = new Date();
        const start = new Date(row.start_date);
        const end = row.end_date ? new Date(row.end_date) : null;
        
        let status; 
        let statusClass;
        
        if (start > today) {
          status = t('Upcoming');
          statusClass = 'bg-blue-50 text-blue-700 ring-blue-600/20';
        } else if (end && end < today) {
          status = t('Expired');
          statusClass = 'bg-gray-50 text-gray-700 ring-gray-600/20';
        } else {
          status = t('Active');
          statusClass = 'bg-green-50 text-green-700 ring-green-600/20';
        }
        
        return (
          <div>
            <div>{startDate} - {endDate}</div>
            <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusClass} mt-1`}>
              {status}
            </span>
          </div>
        );
      }
    },
    { 
      key: 'audience', 
      label: t('Audience'),
      render: (_, row) => {
        if (row.is_company_wide) {
          return (
            <Badge variant="outline" className="bg-blue-50 text-blue-700 hover:bg-blue-50">
              {t('Company-wide')}
            </Badge>
          );
        }
        
        const departmentCount = row.departments?.length || 0;
        const branchCount = row.branches?.length || 0;
        
        return (
          <div className="space-y-1">
            {departmentCount > 0 && (
              <Badge variant="outline" className="bg-green-50 text-green-700 hover:bg-green-50">
                {t('{{count}} Departments', { count: departmentCount })}
              </Badge>
            )}
            {branchCount > 0 && (
              <Badge variant="outline" className="bg-amber-50 text-amber-700 hover:bg-amber-50">
                {t('{{count}} Branches', { count: branchCount })}
              </Badge>
            )}
          </div>
        );
      }
    },
    { 
      key: 'attachments', 
      label: t('Attachments'),
      render: (value, row) => value && value.trim() !== '' ? (
        <Button
          variant="outline"
          size="sm"
          className="flex items-center text-blue-500"
          onClick={(e) => {
            e.stopPropagation();
            handleAction('download-attachment', row);
          }}
        >
          {t('Download')}
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
      requiredPermission: 'view-announcements'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-announcements'
    },
    { 
      label: t('Statistics'), 
      icon: 'BarChart', 
      action: 'statistics', 
      className: 'text-indigo-500',
      requiredPermission: 'view-announcements'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-announcements'
    }
  ];

  // Prepare category options for filter
  const categoryOptions = [
    { value: '', label: t('All Categories') },
    ...(categories || []).map((category: string) => ({
      value: category,
      label: category.charAt(0).toUpperCase() + category.slice(1)
    }))
  ];

  // Prepare department options for filter
  const departmentOptions = [
    { value: '', label: t('All Departments') },
    ...(departments || []).map((dept: any) => ({
      value: dept.id.toString(),
      label: dept.name
    }))
  ];

  // Prepare branch options for filter
  const branchOptions = [
    { value: '', label: t('All Branches') },
    ...(branches || []).map((branch: any) => ({
      value: branch.id.toString(),
      label: branch.name
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: '', label: t('All Statuses') },
    { value: 'active', label: t('Active') },
    { value: 'upcoming', label: t('Upcoming') },
    { value: 'expired', label: t('Expired') }
  ];

  // Prepare priority options for filter
  const priorityOptions = [
    { value: '', label: t('All Priorities') },
    { value: 'high', label: t('High Priority') },
    { value: 'normal', label: t('Normal Priority') }
  ];

  // Prepare category options for form
  const categoryFormOptions = [
    { value: 'company news', label: t('Company News') },
    { value: 'policy updates', label: t('Policy Updates') },
    { value: 'events', label: t('Events') },
    { value: 'HR', label: t('HR') },
    { value: 'IT updates', label: t('IT Updates') }
  ];

  return (
    <PageTemplate 
      title={t("Announcements")} 
      url="/hr/announcements"
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
              name: 'category',
              label: t('Category'),
              type: 'select',
              value: selectedCategory,
              onChange: setSelectedCategory,
              options: categoryOptions
            },
            {
              name: 'department_id',
              label: t('Department'),
              type: 'select',
              value: selectedDepartment,
              onChange: setSelectedDepartment,
              options: departmentOptions
            },
            {
              name: 'branch_id',
              label: t('Branch'),
              type: 'select',
              value: selectedBranch,
              onChange: setSelectedBranch,
              options: branchOptions
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
              name: 'priority',
              label: t('Priority'),
              type: 'select',
              value: selectedPriority,
              onChange: setSelectedPriority,
              options: priorityOptions
            },
            {
              name: 'featured',
              label: t('Featured Only'),
              type: 'checkbox',
              value: isFeatured,
              onChange: setIsFeatured
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
            router.get(route('hr.announcements.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              category: selectedCategory || undefined,
              department_id: selectedDepartment || undefined,
              branch_id: selectedBranch || undefined,
              status: selectedStatus || undefined,
              priority: selectedPriority || undefined,
              featured: isFeatured ? 'true' : undefined,
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
          data={announcements?.data || []}
          from={announcements?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-announcements',
            create: 'create-announcements',
            edit: 'edit-announcements',
            delete: 'delete-announcements'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={announcements?.from || 0}
          to={announcements?.to || 0}
          total={announcements?.total || 0}
          links={announcements?.links}
          entityName={t("announcements")}
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
              name: 'title', 
              label: t('Title'), 
              type: 'text',
              required: true
            },
            { 
              name: 'category', 
              label: t('Category'), 
              type: 'select',
              required: true,
              options: categoryFormOptions
            },
            { 
              name: 'description', 
              label: t('Short Description'), 
              type: 'textarea',
              helpText: t('Brief summary of the announcement')
            },
            { 
              name: 'content', 
              label: t('Content'), 
              type: 'custom',
              required: true,
              render: (field, formData, handleChange) => (
                <RichTextEditor
                  value={formData[field.name] || ''}
                  onChange={(value) => handleChange(field.name, value)}
                  placeholder={t('Enter announcement content...')}
                />
              )
            },
            { 
              name: 'start_date', 
              label: t('Start Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'end_date', 
              label: t('End Date'), 
              type: 'date',
              helpText: t('Leave empty for indefinite announcements')
            },
            { 
              name: 'attachments', 
              label: t('Attachments'), 
              type: 'custom',
              render: (field, formData, handleChange) => (
                <MediaPicker
                  value={String(formData[field.name] || '')}
                  onChange={(url) => handleChange(field.name, url)}
                  placeholder={t('Select attachment file...')}
                />
              ),
              helpText: t('Upload PDF, Word or image file (max 5MB)')
            },
            { 
              name: 'is_featured', 
              label: t('Featured Announcement'), 
              type: 'checkbox',
              helpText: t('Featured announcements are highlighted on the dashboard')
            },
            { 
              name: 'is_high_priority', 
              label: t('High Priority'), 
              type: 'checkbox',
              helpText: t('High priority announcements are shown at the top of the list')
            },
            { 
              name: 'is_company_wide', 
              label: t('Company-wide Announcement'), 
              type: 'checkbox',
              defaultValue: true,
              helpText: t('If unchecked, you must select specific departments or branches')
            },
            {
              name: 'branch_department_selection',
              type: 'dependent-dropdown',
              showWhen: (formData) => !formData.is_company_wide,
              dependentConfig: [
                {
                  name: 'branch_ids',
                  label: t('Target Branches'),
                  multiple: true,
                  options: branchOptions.filter(opt => opt.value !== ''),
                  helpText: t('Select branches that should receive this announcement')
                },
                {
                  name: 'department_ids',
                  label: t('Target Departments'),
                  multiple: true,
                  apiEndpoint: '/hr/announcements/get-departments/{branch_ids}',
                  helpText: t('Select departments that should receive this announcement')
                }
              ]
            }
          ],
          modalSize: 'xl'
        }}
        initialData={currentItem ? {
          ...currentItem,
          department_ids: currentItem.departments?.[0]?.id?.toString() || '',
          branch_ids: currentItem.branches?.[0]?.id?.toString() || '',
          is_company_wide: currentItem.is_company_wide ?? true
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Announcement')
            : formMode === 'edit'
              ? t('Edit Announcement')
              : t('View Announcement')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.title || ''}
        entityName="announcement"
      />
    </PageTemplate>
  );
}