import { CrudConfig } from '@/types/crud';
import { columnRenderers } from '@/utils/columnRenderers';
import { t } from '@/utils/i18n';

export const planOrdersConfig: CrudConfig = {
  entity: {
    name: 'plan-orders',
    endpoint: route('plan-orders.index'),
    permissions: {
      view: 'view-plan-orders',
      create: 'create-plan-orders',
      edit: 'edit-plan-orders',
      delete: 'delete-plan-orders'
    }
  },
  modalSize: '4xl',
  description: t('Manage plan orders and subscription requests'),
  table: {
    columns: [
      { key: 'order_number', label: t('Order Number'), sortable: true },
      { 
        key: 'ordered_at', 
        label: t('Order Date'), 
        sortable: true, 
        render: (value) => `${window.appSettings.formatDateTime(value, false)}`
      },
      { 
        key: 'user.name', 
        label: t('User Name'), 
        sortable: false 
      },
      { 
        key: 'plan.name', 
        label: t('Plan Name'), 
        sortable: false 
      },
      { 
        key: 'original_price', 
        label: t('Original Price'), 
        render: (value) => `${window.appSettings.formatCurrency(value)}`
      },
      { 
        key: 'coupon_code', 
        label: t('Coupon Code'), 
        render: (value) => value || '-'
      },
      { 
        key: 'discount_amount', 
        label: t('Discount'), 
        render: (value) => value > 0 ? `-${window.appSettings.formatCurrency(value)}` : '-'
      },
      { 
        key: 'final_price', 
        label: t('Final Price'), 
        render: (value) => `${window.appSettings.formatCurrency(value)}`
      },
      { 
        key: 'status', 
        label: t('Status'), 
        render: columnRenderers.status({
          'pending': 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20',
          'approved': 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20',
          'rejected': 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20',
          'cancelled': 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20'
        })
      }
    ],
    actions: [
        { 
          label: t('Approve'), 
          icon: 'Check', 
          action: 'approve', 
          className: 'text-green-600',
          condition: (row: any) => row.status === 'pending',
          requiredPermission: 'approve-plan-orders'
        },
        { 
          label: t('Reject'), 
          icon: 'X', 
          action: 'reject', 
          className: 'text-red-600',
          condition: (row: any) => row.status === 'pending',
          requiredPermission: 'reject-plan-orders'
        }
    ]
  },
  search: {
    enabled: true,
    placeholder: t('Search orders...'),
    fields: ['order_number', 'user.name', 'plan.name', 'coupon_code']
  },
  filters: [
    {
      key: 'status',
      label: t('Status'),
      type: 'select',
      options: [
        { value: 'all', label: t('All Status') },
        { value: 'pending', label: t('Pending') },
        { value: 'approved', label: t('Approved') },
        { value: 'rejected', label: t('Rejected') }
      ]
    }
  ],
  form: {
    fields: []
  }
};