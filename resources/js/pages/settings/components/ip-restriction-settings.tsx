import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Plus } from 'lucide-react';
import { CrudDeleteModal } from '@/components/CrudDeleteModal';
import { CrudFormModal } from '@/components/CrudFormModal';
import { CrudTable } from '@/components/CrudTable';
import { SettingsSection } from '@/components/settings-section';
import { toast } from '@/components/custom-toast';
import { router, usePage } from '@inertiajs/react';

interface IpRestriction {
  id: number;
  ip_address: string;
  created_at: string;
}

export default function IpRestrictionSettings() {
  const { t } = useTranslation();
  const { ipRestrictions = [], auth = {} } = usePage().props as any;
  const permissions = auth?.permissions || [];
  
  const [searchTerm, setSearchTerm] = useState('');
  const [isFormModalOpen, setIsFormModalOpen] = useState(false);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<IpRestriction | null>(null);
  const [formMode, setFormMode] = useState<'create' | 'edit'>('create');



  const handleAction = (action: string, item: IpRestriction) => {
    setCurrentItem(item);

    switch (action) {
      case 'edit':
        setFormMode('edit');
        setIsFormModalOpen(true);
        break;
      case 'delete':
        setIsDeleteModalOpen(true);
        break;
    }
  };

  const handleFormSubmit = (formData: any) => {
    const data = { ip_address: formData.ip_address };

    if (formMode === 'create') {
      router.post(route('ip-restrictions.store'), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          if (page.props.flash?.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash?.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to create IP address: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (formMode === 'edit') {
      router.put(route('ip-restrictions.update', currentItem?.id), data, {
        onSuccess: (page) => {
          setIsFormModalOpen(false);
          if (page.props.flash?.success) {
            toast.success(t(page.props.flash.success));
          } else if (page.props.flash?.error) {
            toast.error(t(page.props.flash.error));
          }
        },
        onError: (errors) => {
          if (typeof errors === 'string') {
            toast.error(t(errors));
          } else {
            toast.error(t('Failed to update IP address: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    }
  };

  const handleAddNew = () => {
    setCurrentItem(null);
    setFormMode('create');
    setIsFormModalOpen(true);
  };

  const handleDeleteConfirm = () => {
    if (!currentItem) return;

    router.delete(route('ip-restrictions.destroy', currentItem.id), {
      onSuccess: (page) => {
        setIsDeleteModalOpen(false);
        if (page.props.flash?.success) {
          toast.success(t(page.props.flash.success));
        } else if (page.props.flash?.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        if (typeof errors === 'string') {
          toast.error(t(errors));
        } else {
          toast.error(t('Failed to delete IP address: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
  };

  return (
    <SettingsSection
      title={t("IP Restriction Settings")}
      description={t("Manage allowed IP addresses for company access")}
      action={
        <Button 
          onClick={handleAddNew}
          disabled={!(auth?.permissions || []).find(p => p === 'create-ip-restriction')}
          size="sm"
        >
          <Plus className="h-4 w-4 mr-2" />
          {t('Add IP Address')}
        </Button>
      }
    >
      <Card>
        <CardContent className="p-0">
          <div className="max-h-96 overflow-y-auto">
            <CrudTable
              columns={[
                {
                  key: 'ip_address',
                  label: t('IP Address'),
                  sortable: true,
                  render: (value) => <span className="font-mono">{value}</span>
                }
              ]}
              actions={[
                {
                  label: t('Edit'),
                  icon: 'Edit',
                  action: 'edit',
                  className: 'text-amber-500',
                  requiredPermission: 'edit-ip-restriction'
                },
                {
                  label: t('Delete'),
                  icon: 'Trash2',
                  action: 'delete',
                  className: 'text-red-500',
                  requiredPermission: 'delete-ip-restriction'
                }
              ]}
              data={ipRestrictions || []}
              from={1}
              onAction={handleAction}
              permissions={permissions}
              entityPermissions={{
                edit: 'edit-ip-restriction',
                delete: 'delete-ip-restriction'
              }}
            />
          </div>
        </CardContent>
      </Card>
      {/* Form Modal */}
      <CrudFormModal
        isOpen={isFormModalOpen}
        onClose={() => setIsFormModalOpen(false)}
        onSubmit={handleFormSubmit}
        formConfig={{
          fields: [
            {
              name: 'ip_address',
              label: t('IP Address'),
              type: 'text',
              required: true,
              placeholder: '192.168.1.1'
            }
          ]
        }}
        initialData={currentItem}
        title={
          formMode === 'create'
            ? t('Add IP Address')
            : t('Edit IP Address')
        }
        mode={formMode}
      />

      {/* Delete Modal */}
      <CrudDeleteModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        itemName={currentItem?.ip_address || ''}
        entityName="IP address"
      />
    </SettingsSection>
  );
}