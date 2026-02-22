// pages/hr/training/types/index.tsx
import { useState, useEffect } from 'react';
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
import { Badge } from '@/components/ui/badge';

export default function TrainingTypes() {
  const { t } = useTranslation();
  const { auth, trainingTypes, branches, departments, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedBranch, setSelectedBranch] = useState(pageFilters.branch_id || '');
  const [selectedDepartment, setSelectedDepartment] = useState(pageFilters.department_id || '');
  const [showFilters, setShowFilters] = useState(false);
  
  // State for department assignment
  const [isDepartmentModalOpen, setIsDepartmentModalOpen] = useState(false);
  const [selectedTrainingType, setSelectedTrainingType] = useState<any>(null);
  const [filteredFilterDepartments, setFilteredFilterDepartments] = useState<any[]>([]);
  
  // Filter departments based on selected branch for filters
  useEffect(() => {
    if (selectedBranch === '' || selectedBranch === '_none_') {
      setFilteredFilterDepartments(departments || []);
      setSelectedDepartment(''); // Reset department selection
    } else {
      const depts = departments.filter((dept: any) => dept.branch_id.toString() === selectedBranch);
      setFilteredFilterDepartments(depts);
      // Reset department selection if current selection is not in filtered departments
      if (selectedDepartment && !depts.find(d => d.id.toString() === selectedDepartment)) {
        setSelectedDepartment('');
      }
    }
  }, [selectedBranch, departments, selectedDepartment]);
  

  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedBranch !== '' || selectedDepartment !== '' || searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedBranch !== '' ? 1 : 0) + (selectedDepartment !== '' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.training-types.index'), { 
      page: 1,
      search: searchTerm || undefined,
      branch_id: selectedBranch || undefined,
      department_id: selectedDepartment || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.training-types.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      branch_id: selectedBranch || undefined,
      department_id: selectedDepartment || undefined,
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
      case 'assign-departments':
        setSelectedTrainingType(item);
        setIsDepartmentModalOpen(true);
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
    const submitData = {
      ...formData
    };
    
    if (formMode === 'create') {
      toast.loading(t('Creating training type...'));

      router.post(route('hr.training-types.store'), submitData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Training type created successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to create training type: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating training type...'));

      router.put(route('hr.training-types.update', currentItem.id), submitData, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          toast.dismiss();
          if (page.props.flash.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash.error) {
            toast.error(t(page.props.flash.error));
          } else {
            toast.success(t('Training type updated successfully'));
          }
        },
        onError: (errors) => {
          toast.dismiss();
          if (typeof errors === 'string') {
            toast.error(errors);
          } else {
            toast.error(t(`Failed to update training type: ${Object.values(errors).join(', ')}`));
          }
        }
      });
    }
  };
  
  const handleDepartmentAssignment = (formData: any) => {
    toast.loading(t('Assigning departments...'));
    
    router.put(route('hr.training-types.assign-departments', selectedTrainingType.id), {
      department_ids: formData.department_ids
    }, {
      onSuccess: (page) => {
        setIsDepartmentModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else {
          toast.success(t('Departments assigned successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to assign departments: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting training type...'));
    
    router.delete(route('hr.training-types.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        } else {
          toast.success(t('Training type deleted successfully'));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error(t(`Failed to delete training type: ${Object.values(errors).join(', ')}`));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setSelectedBranch('');
    setSelectedDepartment('');
    setShowFilters(false);
    
    router.get(route('hr.training-types.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Add New Training Type" button if user has permission
  if (hasPermission(permissions, 'create-training-types')) {
    pageActions.push({
      label: t('Add Training Type'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.training-types.index') },
    { title: t('Training Management'), href: route('hr.training-types.index') },
    { title: t('Training Types') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'name', 
      label: t('Name'),
      sortable: true,
      render: (value) => value || '-'
    },
    { 
      key: 'description', 
      label: t('Description'),
      render: (value) => value || '-'
    },
    { 
      key: 'departments', 
      label: t('Departments'),
      render: (value) => {
        if (!value || value.length === 0) {
          return <span className="text-gray-500">{t('All Departments')}</span>;
        }
        
        return (
          <div className="flex flex-wrap gap-1">
            {value.map((dept: any) => (
              <Badge key={dept.id} variant="outline" className="flex flex-col items-start">
                <div className="font-medium">{dept.name}</div>
                <div className="text-xs text-gray-500">{dept.branch?.name || '-'}</div>
              </Badge>
            ))}
          </div>
        );
      }
    },
    { 
      key: 'training_programs_count', 
      label: t('Programs'),
      render: (_, row) => row.training_programs_count || '0'
    }
  ];

  // Define table actions
  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-training-types'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-training-types'
    },
    { 
      label: t('Assign Departments'), 
      icon: 'Users', 
      action: 'assign-departments', 
      className: 'text-green-500',
      requiredPermission: 'edit-training-types'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-training-types'
    }
  ];

  // Prepare branch options for filter
  const branchOptions = [
    { value: '_none_', label: t('All Branches') },
    ...(branches || []).map((branch: any) => ({
      value: branch.id.toString(),
      label: branch.name
    }))
  ];

  // Prepare department options for filter (filtered by selected branch)
  const departmentOptions = [
    { value: '_none_', label: t('All Departments') },
    ...filteredFilterDepartments.map((dept: any) => ({
      value: dept.id.toString(),
      label: dept.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Training Types")} 
      url="/hr/training/types"
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
              name: 'branch_id',
              label: t('Branch'),
              type: 'select',
              value: selectedBranch,
              onChange: setSelectedBranch,
              options: branchOptions,
              searchable:true,
            },
            {
              name: 'department_id',
              label: t('Department'),
              type: 'select',
              value: selectedDepartment,
              onChange: setSelectedDepartment,
              options: departmentOptions,
              searchable:true,
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
            router.get(route('hr.training-types.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              branch_id: selectedBranch || undefined,
              department_id: selectedDepartment || undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={trainingTypes?.data || []}
          from={trainingTypes?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-training-types',
            create: 'create-training-types',
            edit: 'edit-training-types',
            delete: 'delete-training-types'
          }}
        />

        {/* Pagination section */}
        <Pagination
          from={trainingTypes?.from || 0}
          to={trainingTypes?.to || 0}
          total={trainingTypes?.total || 0}
          links={trainingTypes?.links}
          entityName={t("training types")}
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
              label: t('Name'), 
              type: 'text',
              required: true
            },
            { 
              name: 'description', 
              label: t('Description'), 
              type: 'textarea'
            },
            { 
              name: 'branch_id', 
              label: t('Branch'), 
              type: 'select',
              required: true,
              searchable:true,
              options: branchOptions.filter(opt => opt.value !== '_none_')
            }
          ],
          modalSize: 'md'
        }}
        initialData={currentItem ? {
          ...currentItem
        } : null}
        title={
          formMode === 'create'
            ? t('Add New Training Type')
            : formMode === 'edit'
              ? t('Edit Training Type')
              : t('View Training Type')
        }
        mode={formMode}
      />

      {/* Department Assignment Modal */}
      <CrudFormModal
        isOpen={isDepartmentModalOpen}
        onClose={() => setIsDepartmentModalOpen(false)}
        onSubmit={handleDepartmentAssignment}
        formConfig={{
          fields: [
            { 
              name: 'department_ids', 
              label: t('Departments'), 
              type: 'multi-select',
              options: departments
                .filter((dept: any) => dept.branch_id === selectedTrainingType?.branch_id)
                .map((dept: any) => ({
                  value: dept.id.toString(),
                  label: dept.name
                })),
              helpText: t('Select departments for this training type')
            }
          ],
          modalSize: 'xl'
        }}
        initialData={{
          department_ids: selectedTrainingType?.departments?.map((dept: any) => dept.id.toString()) || []
        }}
        title={t('Assign Departments')}
        mode="edit"
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="training type"
      />
    </PageTemplate>
  );
}