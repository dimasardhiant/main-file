// pages/hr/document-types/index.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Plus, Check, X } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';

export default function DocumentTypes() {
    const { t } = useTranslation();
    const { auth, documentTypes, filters: pageFilters = {} } = usePage().props as any;
    const permissions = auth?.permissions || [];

    // State
    const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
    const [selectedRequired, setSelectedRequired] = useState(pageFilters.required || 'all');
    const [showFilters, setShowFilters] = useState(false);
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState<any>(null);
    const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');

    // Check if any filters are active
    const hasActiveFilters = () => {
        return searchTerm !== '' || selectedRequired !== 'all';
    };

    // Count active filters
    const activeFilterCount = () => {
        return (searchTerm ? 1 : 0) + (selectedRequired !== 'all' ? 1 : 0);
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(route('hr.document-types.index'), { 
            page: 1,
            search: searchTerm || undefined,
            required: selectedRequired !== 'all' ? selectedRequired : undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleSort = (field: string) => {
        const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';

        router.get(route('hr.document-types.index'), { 
            sort_field: field, 
            sort_direction: direction, 
            page: 1,
            search: searchTerm || undefined,
            required: selectedRequired !== 'all' ? selectedRequired : undefined,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    const handleAction = (action: string, item: any) => {
        // Prepare the item for the form modal
        const preparedItem = { ...item };
        
        // Convert is_required to boolean for proper display
        if (action === 'view' || action === 'edit') {
            preparedItem.is_required = item.is_required === true || item.is_required === 1 || item.is_required === '1' || item.is_required === 'true';
        }
        
        setCurrentItem(preparedItem);

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
        // Ensure is_required is a proper boolean (1 or 0)
        formData.is_required = formData.is_required === true || formData.is_required === 'true' || formData.is_required === '1' ? 1 : 0;

        if (formMode === 'create') {
            toast.loading(t('Creating document type...'));

            router.post(route('hr.document-types.store'), formData, {
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
                        toast.error(t('Failed to create document type: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        } else if (formMode === 'edit') {
            toast.loading(t('Updating document type...'));

            router.put(route('hr.document-types.update', currentItem.id), formData, {
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
                        toast.error(t('Failed to update document type: {{errors}}', { errors: Object.values(errors).join(', ') }));
                    }
                }
            });
        }
    };

    const handleDeleteConfirm = () => {
        toast.loading(t('Deleting document type...'));

        router.delete(route('hr.document-types.destroy', currentItem.id), {
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
                    toast.error(t('Failed to delete document type: {{errors}}', { errors: Object.values(errors).join(', ') }));
                }
            }
        });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setSelectedRequired('all');
        setShowFilters(false);

        router.get(route('hr.document-types.index'), {
            page: 1,
            per_page: pageFilters.per_page
        }, { preserveState: true, preserveScroll: true });
    };

    // Define page actions
    const pageActions = [];

    // Add the "Add New Document Type" button if user has permission
    if (hasPermission(permissions, 'create-document-types')) {
        pageActions.push({
            label: t('Add Document Type'),
            icon: <Plus className="h-4 w-4 mr-2" />,
            variant: 'default',
            onClick: () => handleAddNew()
        });
    }

    const breadcrumbs = [
        { title: t('Dashboard'), href: route('dashboard') },
        { title: t('HR Management'), href: route('hr.document-types.index') },
        { title: t('Document Types') }
    ];

    // Define table columns
    const columns = [
        {
            key: 'name',
            label: t('Name'),
            sortable: true
        },
        {
            key: 'description',
            label: t('Description'),
            render: (value: string) => value || '-'
        },
        {
            key: 'is_required',
            label: t('Required'),
            render: (value: any) => {
                // Convert any value to a proper boolean
                let isRequired = false;

                if (value === 1 || value === true || value === '1' || value === 'true') {
                    isRequired = true;
                }

                return (
                    <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${isRequired
                            ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                            : 'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                        }`}>
                        {isRequired ? <Check className="h-3 w-3 mr-1" /> : <X className="h-3 w-3 mr-1" />}
                        {isRequired ? t('Required') : t('Optional')}
                    </span>
                );
            }
        },
        {
            key: 'created_at',
            label: t('Created At'),
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
            requiredPermission: 'view-document-types'
        },
        {
            label: t('Edit'),
            icon: 'Edit',
            action: 'edit',
            className: 'text-amber-500',
            requiredPermission: 'edit-document-types'
        },
        {
            label: t('Delete'),
            icon: 'Trash2',
            action: 'delete',
            className: 'text-red-500',
            requiredPermission: 'delete-document-types'
        }
    ];

    // Prepare required options for filter
    const requiredOptions = [
        { value: 'all', label: t('All') },
        { value: 'yes', label: t('Required') },
        { value: 'no', label: t('Optional') }
    ];

    // Get form fields based on mode
    const getFormFields = () => {
        const fields = [
            { name: 'name', label: t('Document Type Name'), type: 'text', required: true },
            { name: 'description', label: t('Description'), type: 'textarea' }
        ];

        // Add is_required field only for create and edit modes
        if (formMode !== 'view') {
            fields.push({
                name: 'is_required',
                label: t('Required'),
                type: 'switch',
                description: t('Is this document required for all employees?')
            });
        }

        return fields;
    };

    return (
        <PageTemplate
            title={t("Document Types")}
            url="/hr/document-types"
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
                            name: 'required',
                            label: t('Required'),
                            type: 'select',
                            value: selectedRequired,
                            onChange: setSelectedRequired,
                            options: requiredOptions
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
                        router.get(route('hr.document-types.index'), { 
                            page: 1, 
                            per_page: parseInt(value),
                            search: searchTerm || undefined,
                            required: selectedRequired !== 'all' ? selectedRequired : undefined
                        }, { preserveState: true, preserveScroll: true });
                    }}
                />
            </div>

            {/* Content section */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
                <CrudTable
                    columns={columns}
                    actions={actions}
                    data={documentTypes?.data || []}
                    from={documentTypes?.from || 1}
                    onAction={handleAction}
                    sortField={pageFilters.sort_field}
                    sortDirection={pageFilters.sort_direction}
                    onSort={handleSort}
                    permissions={permissions}
                    entityPermissions={{
                        view: 'view-document-types',
                        create: 'create-document-types',
                        edit: 'edit-document-types',
                        delete: 'delete-document-types'
                    }}
                />

                {/* Pagination section */}
                <Pagination
                    from={documentTypes?.from || 0}
                    to={documentTypes?.to || 0}
                    total={documentTypes?.total || 0}
                    links={documentTypes?.links}
                    entityName={t("document types")}
                    onPageChange={(url) => router.get(url)}
                />
            </div>

            {/* Form Modal */}
            <CrudFormModal
                isOpen={isFormModalOpen}
                onClose={() => setIsFormModalOpen(false)}
                onSubmit={handleFormSubmit}
                formConfig={{
                    fields: getFormFields(),
                    modalSize: 'lg'
                }}
                initialData={currentItem}
                title={
                    formMode === 'create'
                        ? t('Add New Document Type')
                        : formMode === 'edit'
                            ? t('Edit Document Type')
                            : t('View Document Type')
                }
                mode={formMode}
            />

            {/* Delete Modal */}
            <CrudDeleteModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                itemName={currentItem?.name || ''}
                entityName="document type"
            />
        </PageTemplate>
    );
}