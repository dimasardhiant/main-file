import { PageTemplate } from '@/components/page-template';
import { CrudTable } from '@/components/CrudTable';
import { planOrdersConfig } from '@/config/crud/plan-orders';
import { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Filter, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';

export default function PlanOrdersPage() {
  const { t } = useTranslation();
  const { flash, planOrders, filters: pageFilters = {}, auth, currencySymbol, globalSettings } = usePage().props as any;
  const permissions = auth?.permissions || [];

  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [filterValues, setFilterValues] = useState<Record<string, any>>({});
  const [showFilters, setShowFilters] = useState(false);
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [currentItem, setCurrentItem] = useState<any>(null);


  useEffect(() => {
    const initialFilters: Record<string, any> = {};
    planOrdersConfig.filters?.forEach(filter => {
      initialFilters[filter.key] = pageFilters[filter.key] || '';
    });
    setFilterValues(initialFilters);
  }, []);

  const handleAction = (action: string, item: any) => {
    if (action === 'approve') {
      if (!globalSettings?.is_demo) {
        toast.loading(t('Approving plan order...'));
      }

      router.post(route("plan-orders.approve", item.id), {}, {
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
            toast.error(t('Failed to approve plan order: {{errors}}', { errors: Object.values(errors).join(', ') }));
          }
        }
      });
    } else if (action === 'reject') {
      setCurrentItem(item);
      setIsRejectModalOpen(true);
    }
  };

  const handleRejectConfirm = (notes: string) => {
    if (!globalSettings?.is_demo) {
      toast.loading(t('Rejecting plan order...'));
    }
    
    router.post(route("plan-orders.reject", currentItem.id), { notes }, {
      onSuccess: (page) => {
        setIsRejectModalOpen(false);
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
          toast.error(t('Failed to reject plan order: {{errors}}', { errors: Object.values(errors).join(', ') }));
        }
      }
    });
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

    Object.entries(filterValues).forEach(([key, value]) => {
      if (value && value !== 'all') {
        params[key] = value;
      }
    });

    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route("plan-orders.index"), params, { preserveState: true, preserveScroll: true });
  };

  const handleFilterChange = (key: string, value: any) => {
    setFilterValues(prev => ({ ...prev, [key]: value }));

    const params: any = { page: 1 };

    if (searchTerm) {
      params.search = searchTerm;
    }

    const newFilters = { ...filterValues, [key]: value };
    Object.entries(newFilters).forEach(([k, v]) => {
      if (v && v !== 'all') {
        params[k] = v;
      }
    });

    if (pageFilters.per_page) {
      params.per_page = pageFilters.per_page;
    }

    router.get(route("plan-orders.index"), params, { preserveState: true, preserveScroll: true });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Plans'), href: route('plans.index') },
    { title: t('Plan Orders') }
  ];

  const hasActiveFilters = () => {
    return Object.entries(filterValues).some(([key, value]) => {
      return value && value !== '';
    }) || searchTerm !== '';
  };

  const filteredActions = planOrdersConfig.table.actions?.map(action => ({
    ...action,
    label: t(action.label)
  }));

  return (
    <PageTemplate
      title={t('Plan Orders')}
      url="/plan-orders"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
        <SearchAndFilterBar
          searchTerm={searchTerm}
          onSearchChange={setSearchTerm}
          onSearch={handleSearch}
          filters={planOrdersConfig.filters?.map(filter => ({
            name: filter.key,
            label: t(filter.label),
            type: 'select',
            value: filterValues[filter.key] || '',
            onChange: (value) => handleFilterChange(filter.key, value),
            options: filter.options?.map(option => ({
              value: option.value,
              label: t(option.label)
            })) || []
          })) || []}
          showFilters={showFilters}
          setShowFilters={setShowFilters}
          hasActiveFilters={hasActiveFilters}
          activeFilterCount={() => {
            return Object.values(filterValues).filter(v => v && v !== '').length + (searchTerm ? 1 : 0);
          }}
          onResetFilters={() => {
            setSearchTerm('');
            setFilterValues({});
            router.get(route('plan-orders.index'), { page: 1 }, { preserveState: true, preserveScroll: true });
          }}
          onApplyFilters={applyFilters}
          currentPerPage={pageFilters.per_page?.toString() || "10"}
          onPerPageChange={(value) => {
            const params: any = { page: 1, per_page: parseInt(value) };

            if (searchTerm) {
              params.search = searchTerm;
            }

            Object.entries(filterValues).forEach(([key, val]) => {
              if (val && val !== '') {
                params[key] = val;
              }
            });

            router.get(route('plan-orders.index'), params, { preserveState: true, preserveScroll: true });
          }}
        />
      </div>

      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <CrudTable
          columns={planOrdersConfig.table.columns.map(col => ({
            ...col,
            label: t(col.label),
            render: col.key === 'original_price' || col.key === 'final_price' ? (value: any) => `${currencySymbol}${value}` : col.key === 'discount_amount' ? (value: any) => value > 0 ? `-${currencySymbol}${value}` : '-' : col.render
          }))}
          actions={filteredActions}
          data={planOrders?.data || []}
          from={planOrders?.from || 1}
          onAction={handleAction}
          permissions={permissions}
          entityPermissions={planOrdersConfig.entity.permissions}
        />

        <Pagination
          from={planOrders?.from || 0}
          to={planOrders?.to || 0}
          total={planOrders?.total || 0}
          links={planOrders?.links}
          entityName={t("plan orders")}
          onPageChange={(url) => {
            if (url) {
              const urlObj = new URL(url, window.location.origin);
              if (pageFilters.per_page) {
                urlObj.searchParams.set('per_page', pageFilters.per_page.toString());
              }
              router.get(urlObj.toString());
            }
          }}
        />
      </div>

      {/* Reject Modal */}
      <Dialog open={isRejectModalOpen} onOpenChange={setIsRejectModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{t('Reject Plan Order')}</DialogTitle>
          </DialogHeader>
          <form onSubmit={(e) => {
            e.preventDefault();
            const formData = new FormData(e.currentTarget);
            const notes = formData.get('notes') as string;
            handleRejectConfirm(notes);
          }}>
            <div className="space-y-4">
              <div>
                <Label htmlFor="notes">{t('Rejection Reason (Optional)')}</Label>
                <Textarea 
                  id="notes" 
                  name="notes" 
                  placeholder={t('Enter rejection reason...')} 
                  className="mt-1"
                />
              </div>
            </div>
            <DialogFooter className="mt-6">
              <Button type="button" variant="outline" onClick={() => setIsRejectModalOpen(false)}>
                {t('Cancel')}
              </Button>
              <Button type="submit" variant="destructive">
                {t('Reject')}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}