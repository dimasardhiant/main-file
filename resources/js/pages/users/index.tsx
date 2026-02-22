// pages/users/index.tsx
import { useState, useEffect } from 'react';
import { usersConfig } from '@/config/crud/users';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Card } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Filter, Search, Plus, Eye, Edit, Trash2, KeyRound, Lock, Unlock, LayoutGrid, List, MoreHorizontal } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from 'react-i18next';

export default function Users() {
  const { t } = useTranslation();
  const { auth, users, roles, planLimits, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  const getInitials = useInitials();
  
  // State
  const [activeView, setActiveView] = useState('list');
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [selectedRole, setSelectedRole] = useState(pageFilters.role || 'all');
  const [showFilters, setShowFilters] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isResetPasswordModalOpen, setIsResetPasswordModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  // Check if any filters are active
  const hasActiveFilters = () => {
    return selectedRole !== 'all' || searchTerm !== '';
  };
  
  // Count active filters
  const activeFilterCount = () => {
    return (selectedRole !== 'all' ? 1 : 0) + (searchTerm ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    const params: any = { page: 1 };
    
    if (searchTerm) {
      params.search = searchTerm;
    }
    
    if (selectedRole !== 'all') {
      params.role = selectedRole;
    }
    
    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }
    
    router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
  };
  
  const handleRoleFilter = (value: string) => {
    setSelectedRole(value);
    
    const params: any = { page: 1 };
    
    if (searchTerm) {
      params.search = searchTerm;
    }
    
    if (value !== 'all') {
      params.role = value;
    }
    
    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }
    
    router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    const params: any = { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1 
    };
    
    // Add search and filters
    if (searchTerm) {
      params.search = searchTerm;
    }
    
    if (selectedRole !== 'all') {
      params.role = selectedRole;
    }
    
    // Add per_page if it exists
    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }
    
    router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
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
      case 'reset-password':
        setIsResetPasswordModalOpen(true);
        break;
      case 'toggle-status':
        handleToggleStatus(item);
        break;
      default:
        break;
    }
  };
  
  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };
  
  const handleFormSubmit = (formData: any) => {
    // Keep roles as single string value, not array
    if (formData.roles && Array.isArray(formData.roles)) {
      formData.roles = formData.roles[0];
    }
    
    if (formMode === 'create') {
      toast.loading(t('Creating user...'));
      
      router.post(route('users.store'), formData, {
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
            toast.error(t('Failed to create user: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating user...'));
      
      router.put(route("users.update", currentItem.id), formData, {
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
            toast.error(t('Failed to update user: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting user...'));
    
    router.delete(route("users.destroy", currentItem.id), {
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
          toast.error(t('Failed to delete user: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetPasswordConfirm = (data: { password: string, password_confirmation: string }) => {
    toast.loading(t('Resetting password...'));
    
    router.put(route('users.reset-password', currentItem.id), data, {
      onSuccess: (page) => {
        setIsResetPasswordModalOpen(false);
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
          toast.error(t('Failed to reset password: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleToggleStatus = (user: any) => {
    const newStatus = user.status === 'active' ? 'inactive' : 'active';
    toast.loading(`${newStatus === 'active' ? t('Activating') : t('Deactivating')} user...`);
    
    router.put(route('users.toggle-status', user.id), {}, {
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
          toast.error(t('Failed to update user status: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSelectedRole('all');
    setSearchTerm('');
    setShowFilters(false);
    
    router.get(route('users.index'), { 
      page: 1, 
      per_page: pageFilters.per_page 
    }, { preserveState: true, preserveScroll: true });
  };

  // Define page actions
  const pageActions = [];
  
  // Add the "Add New User" button if user has permission and within limits
  if (hasPermission(permissions, 'create-users')) {
    const canCreate = !planLimits || planLimits.can_create;
    pageActions.push({
      label: planLimits && !canCreate ? t('User Limit Reached ({{current}}/{{max}})', { current: planLimits.current_users, max: planLimits.max_users }) : t('Add User'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: canCreate ? 'default' : 'outline',
      onClick: canCreate ? () => handleAddNew() : () => toast.error(t('User limit exceeded. Your plan allows maximum {{max}} users. Please upgrade your plan.', { max: planLimits.max_users })),
      disabled: !canCreate
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Staff'), href: route('users.index') },
    { title: t('Users') }
  ];

  // Define table columns
  const columns = [
    { 
      key: 'name', 
      label: t('Name'), 
      sortable: true,
      render: (value: any, row: any) => {
        return (
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-white">
              {getInitials(row.name)}
            </div>
            <div>
              <div className="font-medium">{row.name}</div>
              <div className="text-sm text-muted-foreground">{row.email}</div>
            </div>
          </div>
        );
      }
    },
    { 
      key: 'roles', 
      label: t('Roles'),
      render: (value: any) => {
        if (!value || !value.length) return <span className="text-muted-foreground">No roles assigned</span>;
        
        return value.map((role: any) => {
          return <span key={role.id} className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 mr-1">{role.label || role.name}</span>;
        });
      }
    },
    { 
      key: 'created_at', 
      label: t('Joined'), 
      sortable: true,
      render: (value: string) => window.appSettings?.formatDateTimeSimple(value, false) || new Date(value).toLocaleDateString()
    }
  ];

  // Define table actions
  const actions = [
    { 
      label: t('View'), 
      icon: 'Eye', 
      action: 'view', 
      className: 'text-blue-500',
      requiredPermission: 'view-users'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-users'
    },
    { 
      label: t('Reset Password'), 
      icon: 'KeyRound', 
      action: 'reset-password', 
      className: 'text-blue-500',
      requiredPermission: 'reset-password-users'
    },
    { 
      label: t('Toggle Status'), 
      icon: 'Lock', 
      action: 'toggle-status', 
      className: 'text-amber-500',
      requiredPermission: 'toggle-status-users'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-users'
    }
  ];

  return (
    <PageTemplate 
      title={t("Users Management")} 
      url="/users"
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
              name: 'role',
              label: t('Role'),
              type: 'select',
              value: selectedRole,
              onChange: handleRoleFilter,
              options: [
                { value: 'all', label: t('All Roles') },
                ...(roles || []).map((role: any) => ({
                  value: role.id.toString(),
                  label: role.label || role.name
                }))
              ]
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
            const params: any = { page: 1, per_page: parseInt(value) };
            
            if (searchTerm) {
              params.search = searchTerm;
            }
            
            if (selectedRole !== 'all') {
              params.role = selectedRole;
            }
            
            router.get(route('users.index'), params, { preserveState: true, preserveScroll: true });
          }}
          showViewToggle={true}
          activeView={activeView}
          onViewChange={setActiveView}
        />
      </div>

      {/* Content section */}
      {activeView === 'list' ? (
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
          <CrudTable
            columns={columns}
            actions={actions}
            data={users?.data || []}
            from={users?.from || 1}
            onAction={handleAction}
            sortField={pageFilters.sort_field}
            sortDirection={pageFilters.sort_direction}
            onSort={handleSort}
            permissions={permissions}
            entityPermissions={{
              view: 'view-users',
              create: 'create-users',
              edit: 'edit-users',
              delete: 'delete-users'
            }}
          />

          {/* Pagination section */}
          <Pagination
            from={users?.from || 0}
            to={users?.to || 0}
            total={users?.total || 0}
            links={users?.links}
            entityName={t("users")}
            onPageChange={(url) => router.get(url)}
          />
        </div>
      ) : (
        <div>
          {/* Grid View */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {users?.data?.map((user: any) => (
              <Card key={user.id} className="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg shadow">
                {/* Header */}
                <div className="p-6">
                  <div className="flex items-start justify-between mb-4">
                    <div className="flex items-start space-x-4">
                      <div className="h-16 w-16 rounded-full bg-primary text-white flex items-center justify-center text-lg font-bold">
                        {getInitials(user.name)}
                      </div>
                      <div className="flex-1 min-w-0">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">{user.name}</h3>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">{user.email}</p>
                        <div className="flex items-center">
                          <div className={`h-2 w-2 rounded-full mr-2 ${
                            user.status === 'active' ? 'bg-green-500' : 'bg-gray-400'
                          }`}></div>
                          <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {user.status === 'active' ? t('Active') : t('Inactive')}
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    {/* Actions dropdown */}
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="12" cy="12" r="1"></circle>
                            <circle cx="12" cy="5" r="1"></circle>
                            <circle cx="12" cy="19" r="1"></circle>
                          </svg>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" className="w-48 z-50" sideOffset={5}>
                        {hasPermission(permissions, 'view-users') && (
                          <DropdownMenuItem onClick={() => handleAction('view', user)}>
                            <Eye className="h-4 w-4 mr-2" />
                            <span>{t("View User")}</span>
                          </DropdownMenuItem>
                        )}
                        {hasPermission(permissions, 'edit-users') && (
                          <DropdownMenuItem onClick={() => handleAction('reset-password', user)}>
                            <KeyRound className="h-4 w-4 mr-2" />
                            <span>{t("Reset Password")}</span>
                          </DropdownMenuItem>
                        )}
                        {hasPermission(permissions, 'edit-users') && (
                          <DropdownMenuItem onClick={() => handleAction('toggle-status', user)}>
                            {user.status === 'active' ? 
                              <Lock className="h-4 w-4 mr-2" /> : 
                              <Unlock className="h-4 w-4 mr-2" />
                            }
                            <span>{user.status === 'active' ? t("Disable User") : t("Enable User")}</span>
                          </DropdownMenuItem>
                        )}
                        <DropdownMenuSeparator />
                        {hasPermission(permissions, 'edit-users') && (
                          <DropdownMenuItem onClick={() => handleAction('edit', user)} className="text-amber-600">
                            <Edit className="h-4 w-4 mr-2" />
                            <span>{t("Edit")}</span>
                          </DropdownMenuItem>
                        )}
                        {hasPermission(permissions, 'delete-users') && (
                          <DropdownMenuItem onClick={() => handleAction('delete', user)} className="text-rose-600">
                            <Trash2 className="h-4 w-4 mr-2" />
                            <span>{t("Delete")}</span>
                          </DropdownMenuItem>
                        )}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                  
                  {/* Role info */}
                  <div className="border border-gray-200 dark:border-gray-700 rounded-md p-3 mb-4">
                    <div className="flex flex-wrap gap-1">
                      {user.roles && user.roles.length > 0 ? (
                        user.roles.map((role: any) => (
                          <span key={role.id} className="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-300 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-700/30">
                            {role.label || role.name}
                          </span>
                        ))
                      ) : (
                        <span className="text-muted-foreground text-xs dark:text-gray-400">{t("No role")}</span>
                      )}
                    </div>
                  </div>
                
                  {/* Joined date */}
                  <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    {t("Joined:")} {window.appSettings?.formatDateTimeSimple(user.created_at, false) || new Date(user.created_at).toLocaleDateString()}
                  </div>
                
                  {/* Action buttons */}
                  <div className="flex gap-2">
                    {hasPermission(permissions, 'edit-users') && (
                      <Button 
                        variant="outline" 
                        size="sm"
                        onClick={() => handleAction('edit', user)}
                        className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                      >
                        <Edit className="h-4 w-4 mr-2" />
                        {t("Edit")}
                      </Button>
                    )}
                    
                    {hasPermission(permissions, 'view-users') && (
                      <Button 
                        variant="outline" 
                        size="sm"
                        onClick={() => handleAction('view', user)}
                        className="flex-1 h-9 text-sm border-gray-300 dark:border-gray-600 dark:text-gray-200"
                      >
                        <Eye className="h-4 w-4 mr-2" />
                        {t("View")}
                      </Button>
                    )}
                    
                    {hasPermission(permissions, 'delete-users') && (
                      <Button 
                        variant="outline" 
                        size="sm"
                        onClick={() => handleAction('delete', user)}
                        className="flex-1 h-9 text-sm text-gray-700 border-gray-300 dark:border-gray-600 dark:text-gray-200"
                      >
                        <Trash2 className="h-4 w-4 mr-2" />
                        {t("Delete")}
                      </Button>
                    )}
                  </div>
                </div>
              </Card>
            ))}
          </div>
          
          {/* Pagination for grid view */}
          <div className="mt-6 bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
            <Pagination
              from={users?.from || 0}
              to={users?.to || 0}
              total={users?.total || 0}
              links={users?.links}
              entityName={t("users")}
              onPageChange={(url) => router.get(url)}
            />
          </div>
        </div>
      )}

      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            { name: 'name', label: t('Name'), type: 'text', required: true },
            { name: 'email', label: t('Email'), type: 'email', required: true },
            { 
              name: 'password', 
              label: t('Password'), 
              type: 'password',
              required: true,
              conditional: (mode) => mode === 'create'
            },
            { 
              name: 'password_confirmation', 
              label: t('Confirm Password'), 
              type: 'password',
              required: true,
              conditional: (mode) => mode === 'create'
            },
            { 
              name: 'roles', 
              label: t('Role'), 
              type: 'select', 
              options: roles ? roles.map((role: any) => ({
                value: role.id.toString(),
                label: role.label || role.name
              })) : [],
              required: true
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          roles: currentItem.roles && currentItem.roles.length > 0 ? currentItem.roles[0].id.toString() : ''
        } : null}
        title={
          formMode === 'create' 
            ? t('Add New User') 
            : formMode === 'edit' 
              ? t('Edit User') 
              : t('View User')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.name || ''}
        entityName="user"
      />

      {/* Reset Password Modal */}
      <CrudFormModal
        isOpen={isResetPasswordModalOpen}
        onClose={() => setIsResetPasswordModalOpen(false)}
        onSubmit={handleResetPasswordConfirm}
        formConfig={{
          fields: [
            { name: 'password', label: t('New Password'), type: 'password', required: true },
            { name: 'password_confirmation', label: t('Confirm Password'), type: 'password', required: true }
          ],
          modalSize: 'sm'
        }}
        initialData={{}}
        title={`Reset Password for ${currentItem?.name || 'User'}`}
        mode="edit"
      />
    </PageTemplate>
  );
}