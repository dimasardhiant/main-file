// pages/hr/holidays/index.tsx
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
import { Plus, Calendar, FileText, Download } from 'lucide-react';
import { format, differenceInDays, addDays } from 'date-fns';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function Holidays() {
  const { t } = useTranslation();
  const { auth, holidays, branches, categories, years, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedCategory, setSelectedCategory] = useState(pageFilters.category || '');
  const [selectedBranch, setSelectedBranch] = useState(pageFilters.branch_id || '');
  const [selectedYear, setSelectedYear] = useState(pageFilters.year || new Date().getFullYear().toString());
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
           selectedBranch !== '' ||
           selectedYear !== new Date().getFullYear().toString() ||
           dateFrom !== '' || 
           dateTo !== '' || 
           searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedCategory !== '' ? 1 : 0) + 
           (selectedBranch !== '' ? 1 : 0) +
           (selectedYear !== new Date().getFullYear().toString() ? 1 : 0) +
           (dateFrom !== '' ? 1 : 0) + 
           (dateTo !== '' ? 1 : 0) + 
           (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.holidays.index'), { 
      page: 1,
      search: searchTerm || undefined,
      category: selectedCategory || undefined,
      branch_id: selectedBranch || undefined,
      year: selectedYear || undefined,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.holidays.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      category: selectedCategory || undefined,
      branch_id: selectedBranch || undefined,
      year: selectedYear || undefined,
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
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    if (formMode === 'create') {
      toast.loading(t('Creating holiday...'));

      router.post(route('hr.holidays.store'), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Holiday created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to create holiday: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating holiday...'));

      router.put(route('hr.holidays.update', currentItem.id), formData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Holiday updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to update holiday: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting holiday...'));
    
    router.delete(route('hr.holidays.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Holiday deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to delete holiday: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedCategory('');
    setSelectedBranch('');
    setSelectedYear(new Date().getFullYear().toString());
    setDateFrom('');
    setDateTo('');
    setShowFilters(false);
    
    router.get(route('hr.holidays.index'), {
      page: 1,
      year: new Date().getFullYear().toString(),
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleViewCalendar = () => {
    router.visit(route('hr.holidays.calendar'), {
      method: 'get',
      data: {
        year: selectedYear || new Date().getFullYear().toString(),
        category: selectedCategory || undefined,
        branch_id: selectedBranch || undefined
      }
    });
  };

  const handleExportPdf = () => {
    const params = new URLSearchParams({
      year: selectedYear || new Date().getFullYear().toString(),
      ...(selectedCategory && { category: selectedCategory }),
      ...(selectedBranch && { branch_id: selectedBranch })
    });
    
    window.open(`${route('hr.holidays.export.pdf')}?${params.toString()}`, '_blank');
  };

  const handleExportIcal = () => {
    const params = new URLSearchParams({
      year: selectedYear || new Date().getFullYear().toString(),
      ...(selectedCategory && { category: selectedCategory }),
      ...(selectedBranch && { branch_id: selectedBranch })
    });
    
    window.open(`${route('hr.holidays.export.ical')}?${params.toString()}`, '_blank');
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "View Calendar" button
  pageActions.push({
    label: t('Calendar View'),
    icon: <Calendar className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleViewCalendar
  });
  
  // Add export buttons
  pageActions.push({
    label: t('Export PDF'),
    icon: <FileText className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleExportPdf
  });
  
  pageActions.push({
    label: t('Export iCal'),
    icon: <Download className="h-4 w-4 mr-2" />,
    variant: 'outline',
    onClick: handleExportIcal
  });
  
  // Add the "Add New Holiday" button if user has permission
  if (hasPermission(permissions, 'create-holidays')) {
    pageActions.push({
      label: t('Add Holiday'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.holidays.index') },
    { title: t('Holidays') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'name', 
      label: t('Holiday Name'),
      sortable: true,
      render: (value) => value || '-'
    },
    { 
      key: 'date', 
      label: t('Date'),
      sortable: true,
      render: (_, row) => {
        if (row.end_date && row.start_date !== row.end_date) {
          return (
            <div>
              <div>{window.appSettings?.formatDateTimeSimple(row.start_date, false) || new Date(row.start_date).toLocaleDateString()}</div>
              <div className="text-xs text-gray-500">to</div>
              <div>{window.appSettings?.formatDateTimeSimple(row.end_date, false) || new Date(row.end_date).toLocaleDateString()}</div>
              <div className="text-xs text-gray-500">
                ({differenceInDays(new Date(row.end_date), new Date(row.start_date)) + 1} days)
              </div>
            </div>
          );
        }
        return window.appSettings?.formatDateTimeSimple(row.start_date, false) || new Date(row.start_date).toLocaleDateString();
      }
    },
    { 
      key: 'category', 
      label: t('Category'),
      render: (value) => {
        const categoryClasses = {
          'national': 'bg-blue-50 text-blue-700 ring-blue-600/20',
          'religious': 'bg-purple-50 text-purple-700 ring-purple-600/20',
          'company-specific': 'bg-green-50 text-green-700 ring-green-600/20',
          'regional': 'bg-amber-50 text-amber-700 ring-amber-600/20'
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
      key: 'branches', 
      label: t('Branches'),
      render: (_, row) => {
        if (!row.branches || row.branches.length === 0) return '-';
        
        if (row.branches.length <= 2) {
          return (
            <div className="flex flex-wrap gap-1">
              {row.branches.map((branch: any) => (
                <Badge key={branch.id} variant="outline">{branch.name}</Badge>
              ))}
            </div>
          );
        }
        
        return (
          <div className="flex flex-wrap gap-1">
            <Badge variant="outline">{row.branches[0].name}</Badge>
            <Badge variant="outline">+{row.branches.length - 1} more</Badge>
          </div>
        );
      }
    },
    { 
      key: 'type', 
      label: t('Type'),
      render: (_, row) => {
        const badges = [];
        
        if (row.is_recurring) {
          badges.push(
            <Badge key="recurring" variant="secondary" className="bg-indigo-50 text-indigo-700 hover:bg-indigo-50">
              {t('Recurring')}
            </Badge>
          );
        }
        
        if (row.is_half_day) {
          badges.push(
            <Badge key="half-day" variant="secondary" className="bg-orange-50 text-orange-700 hover:bg-orange-50">
              {t('Half Day')}
            </Badge>
          );
        }
        
        if (row.is_paid) {
          badges.push(
            <Badge key="paid" variant="secondary" className="bg-green-50 text-green-700 hover:bg-green-50">
              {t('Paid')}
            </Badge>
          );
        } else {
          badges.push(
            <Badge key="unpaid" variant="secondary" className="bg-red-50 text-red-700 hover:bg-red-50">
              {t('Unpaid')}
            </Badge>
          );
        }
        
        return (
          <div className="flex flex-wrap gap-1">
            {badges}
          </div>
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
      requiredPermission: 'view-holidays'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-holidays'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-holidays'
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

  // Prepare branch options for filter
  const branchOptions = [
    { value: '', label: t('All Branches') },
    ...(branches || []).map((branch: any) => ({
      value: branch.id.toString(),
      label: branch.name
    }))
  ];

  // Prepare year options for filter
  const yearOptions = [
    ...(years || []).map((year: number) => ({
      value: year.toString(),
      label: year.toString()
    }))
  ];

  // Prepare category options for form
  const categoryFormOptions = [
    { value: 'national', label: t('National') },
    { value: 'religious', label: t('Religious') },
    { value: 'company-specific', label: t('Company Specific') },
    { value: 'regional', label: t('Regional') }
  ];

  return (
    <PageTemplate 
      title={t("Holidays")} 
      url="/hr/holidays"
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
              name: 'branch_id',
              label: t('Branch'),
              type: 'select',
              value: selectedBranch,
              onChange: setSelectedBranch,
              options: branchOptions
            },
            {
              name: 'year',
              label: t('Year'),
              type: 'select',
              value: selectedYear,
              onChange: setSelectedYear,
              options: yearOptions
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
            router.get(route('hr.holidays.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              category: selectedCategory || undefined,
              branch_id: selectedBranch || undefined,
              year: selectedYear || undefined,
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
          data={holidays?.data || []}
          from={holidays?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-holidays',
            create: 'create-holidays',
            edit: 'edit-holidays',
            delete: 'delete-holidays'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={holidays?.from || 0}
          to={holidays?.to || 0}
          total={holidays?.total || 0}
          links={holidays?.links}
          entityName={t("holidays")}
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
              name: 'name', 
              label: t('Holiday Name'), 
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
              name: 'start_date', 
              label: t('Start Date'), 
              type: 'date', 
              required: true 
            },
            { 
              name: 'end_date', 
              label: t('End Date'), 
              type: 'date',
              helpText: t('Leave empty for single-day holiday')
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea' 
            },
            { 
              name: 'is_paid', 
              label: t('Paid Holiday'), 
              type: 'checkbox',
              defaultValue: true
            },
            { 
              name: 'is_half_day', 
              label: t('Half Day'), 
              type: 'checkbox'
            },
            { 
              name: 'branch_ids', 
              label: t('Applicable Branches'), 
              type: 'multi-select',
              required: true,
              options: branchOptions.filter(opt => opt.value !== '')
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          start_date: currentItem.start_date ? currentItem.start_date.split('T')[0] : '',
          end_date: currentItem.end_date ? currentItem.end_date.split('T')[0] : '',
          branch_ids: currentItem.branches?.map((branch: any) => branch.id.toString())
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Holiday')
            : formMode === 'edit'
              ? t('Edit Holiday')
              : t('View Holiday')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="holiday"
      />
    </PageTemplate>
  );
}