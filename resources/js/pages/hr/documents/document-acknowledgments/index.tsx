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
import { Plus, CheckCircle, Clock, AlertTriangle, User, FileText, Calendar, XCircle } from 'lucide-react';
import { format } from 'date-fns';

export default function DocumentAcknowledgments() {
  const { t } = useTranslation();
  const { auth, documentAcknowledgments, documents, users, filters: pageFilters = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [documentFilter, setDocumentFilter] = useState(pageFilters.document_id || '_empty_');
  const [userFilter, setUserFilter] = useState(pageFilters.user_id || '_empty_');
  const [statusFilter, setStatusFilter] = useState(pageFilters.status || '_empty_');
  const [showFilters, setShowFilters] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isAcknowledgeModalOpen, setIsAcknowledgeModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit' | 'view'>('create');
  
  const hasActiveFilters = () => {
    return documentFilter !== '_empty_' || userFilter !== '_empty_' || statusFilter !== '_empty_' || searchTerm !== '';
  };
  
  const activeFilterCount = () => {
    return (documentFilter !== '_empty_' ? 1 : 0) + (userFilter !== '_empty_' ? 1 : 0) + (statusFilter !== '_empty_' ? 1 : 0) + (searchTerm !== '' ? 1 : 0);
  };
  
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };
  
  const applyFilters = () => {
    router.get(route('hr.documents.document-acknowledgments.index'), { 
      page: 1,
      search: searchTerm || undefined,
      document_id: documentFilter !== '_empty_' ? documentFilter : undefined,
      user_id: userFilter !== '_empty_' ? userFilter : undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleSort = (field: string) => {
    const direction = pageFilters.sort_field === field && pageFilters.sort_direction === 'asc' ? 'desc' : 'asc';
    
    router.get(route('hr.documents.document-acknowledgments.index'), { 
      sort_field: field, 
      sort_direction: direction, 
      page: 1,
      search: searchTerm || undefined,
      document_id: documentFilter !== '_empty_' ? documentFilter : undefined,
      user_id: userFilter !== '_empty_' ? userFilter : undefined,
      status: statusFilter !== '_empty_' ? statusFilter : undefined,
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
      case 'acknowledge':
        setIsAcknowledgeModalOpen(true);
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
      toast.loading(t('Assigning document acknowledgment...'));

      router.post(route('hr.documents.document-acknowledgments.store'), formData, {
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
            toast.error(errors);
          } else {
            toast.error(`Failed to assign acknowledgment: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    } else if (formMode === 'edit') {
      toast.loading(t('Updating document acknowledgment...'));

      router.put(route('hr.documents.document-acknowledgments.update', currentItem.id), formData, {
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
            toast.error(errors);
          } else {
            toast.error(`Failed to update acknowledgment: ${Object.values(errors).join(', ')}`);
          }
        }
      });
    }
  };
  
  const handleDeleteConfirm = () => {
    toast.loading(t('Deleting document acknowledgment...'));

    router.delete(route('hr.documents.document-acknowledgments.destroy', currentItem.id), {
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
          toast.error(`Failed to delete acknowledgment: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };
  
  const handleResetFilters = () => {
    setSearchTerm('');
    setDocumentFilter('_empty_');
    setUserFilter('_empty_');
    setStatusFilter('_empty_');
    setShowFilters(false);
    
    router.get(route('hr.documents.document-acknowledgments.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };
  
  const handleAcknowledgeSubmit = (formData: any) => {
    toast.loading(t('Acknowledging document...'));

    router.put(route('hr.documents.document-acknowledgments.acknowledge', currentItem.id), {
      acknowledgment_note: formData.acknowledgment_note || 'Document acknowledged'
    }, {
      onSuccess: (page) => {
        setIsAcknowledgeModalOpen(false);
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
          toast.error(`Failed to acknowledge document: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const pageActions = [];
  
  if (hasPermission(permissions, 'create-document-acknowledgments')) {
    pageActions.push({
      label: t('Assign Document'),
      icon: <Plus className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleAddNew()
    });
  }

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Document Management'), href: route('hr.documents.document-acknowledgments.index') },
    { title: t('Acknowledgments') }
  ];

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Pending': return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
      case 'Acknowledged': return 'bg-green-50 text-green-700 ring-green-600/20';
      case 'Overdue': return 'bg-red-50 text-red-700 ring-red-600/10';
      case 'Exempted': return 'bg-gray-50 text-gray-600 ring-gray-500/10';
      default: return 'bg-gray-50 text-gray-600 ring-gray-500/10';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'Pending': return <Clock className="h-3 w-3" />;
      case 'Acknowledged': return <CheckCircle className="h-3 w-3" />;
      case 'Overdue': return <AlertTriangle className="h-3 w-3" />;
      case 'Exempted': return <XCircle className="h-3 w-3" />;
      default: return <Clock className="h-3 w-3" />;
    }
  };

  const getDaysInfo = (item: any) => {
    if (item.status === 'Acknowledged' || item.status === 'Exempted') return null;
    
    if (!item.due_date) return null;
    
    const today = new Date();
    const dueDate = new Date(item.due_date);
    const diffTime = dueDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
      return { type: 'overdue', days: Math.abs(diffDays), text: `${Math.abs(diffDays)} days overdue` };
    } else if (diffDays === 0) {
      return { type: 'today', days: 0, text: 'Due today' };
    } else {
      return { type: 'remaining', days: diffDays, text: `${diffDays} days remaining` };
    }
  };

  const columns = [
    { 
      key: 'document.title', 
      label: t('Document'), 
      sortable: true,
      render: (_, row) => (
        <div className="flex items-center gap-3">
          <div 
            className="w-10 h-10 rounded-lg flex items-center justify-center text-white"
            style={{ backgroundColor: row.document?.category?.color || '#3B82F6' }}
          >
            <FileText className="h-5 w-5" />
          </div>
          <div>
            <div className="font-medium">{row.document?.title || 'Unknown Document'}</div>
            <div className="text-xs text-gray-500">{row.document?.category?.name}</div>
          </div>
        </div>
      )
    },
    { 
      key: 'user.name', 
      label: t('Employee'),
      render: (_, row) => (
        <div className="flex items-center gap-2">
          <User className="h-4 w-4 text-gray-500" />
          {row.user?.name || 'Unknown User'}
        </div>
      )
    },
    { 
      key: 'status', 
      label: t('Status'),
      render: (value) => (
        <span className={`inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(value)}`}>
          {getStatusIcon(value)}
          {t(value)}
        </span>
      )
    },
    { 
      key: 'due_date', 
      label: t('Due Date'),
      sortable: true,
      render: (value, row) => {
        if (!value) return '-';
        const daysInfo = getDaysInfo(row);
        return (
          <div>
            <div className="flex items-center gap-1">
              <Calendar className="h-4 w-4 text-gray-500" />
              {value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'}
            </div>
            {daysInfo && (
              <div className={`text-xs ${
                daysInfo.type === 'overdue' ? 'text-red-600' : 
                daysInfo.type === 'today' ? 'text-orange-600' : 
                'text-gray-500'
              }`}>
                {daysInfo.text}
              </div>
            )}
          </div>
        );
      }
    },
    { 
      key: 'acknowledged_at', 
      label: t('Acknowledged'),
      sortable: true,
      render: (value, row) => {
        if (!value) return '-';
        return (
          <div>
            <div className="text-sm">{value ? (window.appSettings?.formatDateTimeSimple(value,false) || new Date(value).toLocaleString()) : '-'}</div>
          </div>
        );
      }
    },
    { 
      key: 'assigned_by.name', 
      label: t('Assigned By'),
      render: (_, row) => row.assigned_by?.name || '-'
    },
    { 
      key: 'assigned_at', 
      label: t('Assigned'),
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
      requiredPermission: 'view-document-acknowledgments'
    },
    { 
      label: t('Edit'), 
      icon: 'Edit', 
      action: 'edit', 
      className: 'text-amber-500',
      requiredPermission: 'edit-document-acknowledgments'
    },
    { 
      label: t('Acknowledge'), 
      icon: 'CheckCircle', 
      action: 'acknowledge', 
      className: 'text-green-500',
      requiredPermission: 'acknowledge-document-acknowledgments',
      condition: (item: any) => item.status === 'Pending' || item.status === 'Overdue'
    },
    { 
      label: t('Delete'), 
      icon: 'Trash2', 
      action: 'delete', 
      className: 'text-red-500',
      requiredPermission: 'delete-document-acknowledgments'
    }
  ];

  const documentOptions = [
    { value: '_empty_', label: t('All Documents'), disabled:true },
    ...(documents || []).map((doc: any) => ({
      value: doc.id.toString(),
      label: doc.title
    }))
  ];

  const userOptions = [
    { value: '_empty_', label: t('All Users'), disabled:true },
    ...(users || []).map((user: any) => ({
      value: user.id.toString(),
      label: user.name
    }))
  ];

  const statusOptions = [
    { value: '_empty_', label: t('All Statuses'), disabled:true },
    { value: 'Pending', label: t('Pending') },
    { value: 'Acknowledged', label: t('Acknowledged') },
    { value: 'Overdue', label: t('Overdue') },
    { value: 'Exempted', label: t('Exempted') }
  ];

  const documentSelectOptions = [
    { value: '_empty_', label: t('Select Document') },
    ...(documents || []).map((doc: any) => ({
      value: doc.id.toString(),
      label: doc.title
    }))
  ];

  const userSelectOptions = [
    { value: '_empty_', label: t('Select User') },
    ...(users || []).map((user: any) => ({
      value: user.id.toString(),
      label: user.name
    }))
  ];

  return (
    <PageTemplate 
      title={t("Acknowledgments")} 
      url="/hr/documents/document-acknowledgments"
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
              name: 'document_id',
              label: t('Document'),
              type: 'select',
              value: documentFilter,
              onChange: setDocumentFilter,
              options: documentOptions,
              searchable  : true
            },
            {
              name: 'user_id',
              label: t('User'),
              type: 'select',
              value: userFilter,
              onChange: setUserFilter,
              options: userOptions,
              searchable  : true
            },
            {
              name: 'status',
              label: t('Status'),
              type: 'select',
              value: statusFilter,
              onChange: setStatusFilter,
              options: statusOptions
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
            router.get(route('hr.documents.document-acknowledgments.index'), { 
              page: 1, 
              per_page: parseInt(value),
              search: searchTerm || undefined,
              document_id: documentFilter !== '_empty_' ? documentFilter : undefined,
              user_id: userFilter !== '_empty_' ? userFilter : undefined,
              status: statusFilter !== '_empty_' ? statusFilter : undefined
            }, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={columns}
          actions={actions}
          data={documentAcknowledgments?.data || []}
          from={documentAcknowledgments?.from || 1}
          onAction={handleAction}
          sortField={pageFilters.sort_field}
          sortDirection={pageFilters.sort_direction}
          onSort={handleSort}
          permissions={permissions}
          entityPermissions={{
            view: 'view-document-acknowledgments',
            create: 'create-document-acknowledgments',
            edit: 'edit-document-acknowledgments',
            delete: 'delete-document-acknowledgments'
          }}
        />

        <Pagination
          from={documentAcknowledgments?.from || 0}
          to={documentAcknowledgments?.to || 0}
          total={documentAcknowledgments?.total || 0}
          links={documentAcknowledgments?.links}
          entityName={t("acknowledgments")}
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
              name: 'document_id', 
              label: t('Document'), 
              type: 'select', 
              required: true,
              options: documentSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable  : true
            },
            { 
              name: 'user_id', 
              label: t('User'), 
              type: 'select', 
              required: true,
              options: userSelectOptions.filter(opt => opt.value !== '_empty_'),
              searchable  : true
            },
            { 
              name: 'due_date', 
              label: t('Due Date'), 
              type: 'date',
              helpText: formMode === 'create' ? t('Leave empty to set 7 days from today') : undefined
            },
            { 
              name: 'status', 
              label: t('Status'), 
              type: 'select',
              required: formMode === 'edit',
              options: statusOptions.filter(opt => opt.value !== '_empty_'),
              conditional: (mode) => mode === 'edit' || mode === 'view'
            },
            { 
              name: 'acknowledgment_note', 
              label: t('Note'), 
              type: 'textarea',
              rows: 3
            }
          ],
          modalSize: 'lg'
        }}
        initialData={currentItem ? {
          ...currentItem,
          document_id: currentItem.document_id?.toString(),
          user_id: currentItem.user_id?.toString(),
          due_date: currentItem.due_date ? window.appSettings.formatDateTimeSimple(currentItem.due_date, false) : currentItem.due_date
        } : null}
        title={
          formMode === 'create'
            ? t('Assign Document for Acknowledgment')
            : formMode === 'edit'
              ? t('Edit Document Acknowledgment')
              : t('View Document Acknowledgment')
        }
        mode={formMode}
      />

      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem ? `${currentItem.document?.title} - ${currentItem.user?.name}` : ''}
        entityName="acknowledgment"
      />
      
      <CrudFormModal
        isOpen={isAcknowledgeModalOpen}
        onClose={() => setIsAcknowledgeModalOpen(false)}
        onSubmit={handleAcknowledgeSubmit}
        formConfig={{
          fields: [
            {
              name: 'acknowledgment_note',
              label: t('Acknowledgment Note'),
              type: 'textarea',
              rows: 3,
              placeholder: t('Enter acknowledgment note (optional)')
            }
          ]
        }}
        initialData={{ acknowledgment_note: '' }}
        title={t('Acknowledge Document')}
        mode="edit"
      />
    </PageTemplate>
  );
}