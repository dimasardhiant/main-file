import { PageWrapper } from '@/components/PageWrapper';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { ArrowLeft, Calendar, DollarSign, Users, TrendingUp } from 'lucide-react';
import { usePage, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useState, useEffect } from 'react';

interface CouponUsage {
  id: number;
  user_name: string;
  user_email: string;
  order_id: string;
  amount: number;
  discount_amount: number;
  used_at: string;
}

interface CouponData {
  id: number;
  name: string;
  code: string;
  type: string;
  discount_amount: number;
  minimum_spend?: number;
  maximum_spend?: number;
  use_limit_per_coupon?: number;
  use_limit_per_user?: number;
  used_count: number;
  expiry_date?: string;
  status: boolean;
  created_at: string;
  creator: {
    name: string;
    email: string;
  };
}

export default function CouponDetailsPage() {
  const { t } = useTranslation();
  const { coupon, usage_history } = usePage().props as { coupon: CouponData; usage_history: any };
  
  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Coupons'), href: route('coupons.index') },
    { title: coupon.name }
  ];

  const usageColumns = [
    { key: 'user_name', label: t('User'), sortable: true },
    { key: 'user_email', label: t('Email'), sortable: true },
    { key: 'order_id', label: t('Order ID'), sortable: true },
    { 
      key: 'amount', 
      label: t('Order Amount'), 
      render: (value: number) => window.appSettings?.formatCurrency(value) || `$${value.toFixed(2)}`
    },
    { 
      key: 'discount_amount', 
      label: t('Discount Applied'), 
      render: (value: number) => window.appSettings?.formatCurrency(value) || `$${value.toFixed(2)}`
    },
    { 
      key: 'used_at', 
      label: t('Used At'), 
      sortable: true,
      render: (value: string) => window.appSettings?.formatDateTime(value) || value
    }
  ];

  const formatDiscount = (type: string, amount: number) => {
    return type === 'percentage' 
      ? `${amount}%` 
      : (window.appSettings?.formatCurrency(amount) || `$${amount.toFixed(2)}`);
  };

  const getStatusBadge = (status: boolean) => {
    return status ? (
      <Badge className="bg-green-100 text-green-800">{t('Active')}</Badge>
    ) : (
      <Badge className="bg-red-100 text-red-800">{t('Inactive')}</Badge>
    );
  };

  const getTypeBadge = (type: string) => {
    const className = type === 'percentage' 
      ? 'bg-blue-100 text-blue-800' 
      : 'bg-green-100 text-green-800';
    const label = type === 'percentage' ? t('Percentage') : t('Flat Amount');
    return <Badge className={className}>{label}</Badge>;
  };

  return (
    <PageWrapper breadcrumbs={breadcrumbs}>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link href={route('coupons.index')}>
              <Button variant="outline" size="sm">
                <ArrowLeft className="h-4 w-4 mr-2" />
                {t('Back to Coupons')}
              </Button>
            </Link>
            <div>
              <h1 className="text-2xl font-bold">{coupon.name}</h1>
              <p className="text-gray-600">{t('Coupon Details & Usage History')}</p>
            </div>
          </div>
          {getStatusBadge(coupon.status)}
        </div>

        {/* Coupon Info Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Discount Value')}</p>
                  <h3 className="mt-2 text-2xl font-bold">{formatDiscount(coupon.type, coupon.discount_amount)}</h3>
                </div>
                <div className="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                  <DollarSign className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Times Used')}</p>
                  <h3 className="mt-2 text-2xl font-bold">{coupon.used_count}
                  {coupon.use_limit_per_coupon && ` / ${coupon.use_limit_per_coupon}`}</h3>
                </div>
                <div className="rounded-full bg-green-100 p-3 dark:bg-green-900">
                  <TrendingUp className="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('User Limit')}</p>
                  <h3 className="mt-2 text-2xl font-bold"> {coupon.use_limit_per_user || t('Unlimited')}</h3>
                </div>
                <div className="rounded-full bg-purple-100 p-3 dark:bg-purple-900">
                  <Users className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-muted-foreground">{t('Expires')}</p>
                  <h3 className="mt-2 text-2xl font-bold"> {coupon.expiry_date 
                    ? window.appSettings?.formatDateTime(coupon.expiry_date, false) || coupon.expiry_date
                    : t('Never')
                  }</h3>
                </div>
                <div className="rounded-full bg-orange-100 p-3 dark:bg-orange-900">
                  <DollarSign className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Coupon Details */}
        <Card className="p-6">
          <h2 className="text-lg font-semibold mb-4">{t('Coupon Information')}</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <div>
                <label className="text-sm font-medium text-gray-600">{t('Coupon Code')}</label>
                <p className="text-lg font-mono bg-gray-100 px-3 py-2 rounded">{coupon.code}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-600">{t('Type')}</label>
                <div className="mt-1">{getTypeBadge(coupon.type)}</div>
              </div>
              <div>
                <label className="text-sm font-medium text-gray-600">{t('Created By')}</label>
                <p className="text-sm">{coupon.creator.name} ({coupon.creator.email})</p>
              </div>
            </div>
            <div className="space-y-4">
              {coupon.minimum_spend && (
                <div>
                  <label className="text-sm font-medium text-gray-600">{t('Minimum Spend')}</label>
                  <p className="text-sm">{window.appSettings?.formatCurrency(coupon.minimum_spend) || `$${coupon.minimum_spend.toFixed(2)}`}</p>
                </div>
              )}
              {coupon.maximum_spend && (
                <div>
                  <label className="text-sm font-medium text-gray-600">{t('Maximum Spend')}</label>
                  <p className="text-sm">{window.appSettings?.formatCurrency(coupon.maximum_spend) || `$${coupon.maximum_spend.toFixed(2)}`}</p>
                </div>
              )}
              <div>
                <label className="text-sm font-medium text-gray-600">{t('Created At')}</label>
                <p className="text-sm">{window.appSettings?.formatDateTime(coupon.created_at) || coupon.created_at}</p>
              </div>
            </div>
          </div>
        </Card>

        {/* Usage History */}
        <Card className="p-6">
          <h2 className="text-lg font-semibold mb-4">{t('Usage History')}</h2>
          <div className="border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  {usageColumns.map((column) => (
                    <TableHead key={column.key}>{column.label}</TableHead>
                  ))}
                </TableRow>
              </TableHeader>
              <TableBody>
                {usage_history.data && usage_history.data.length > 0 ? (
                  usage_history.data.map((item: any, index: number) => (
                    <TableRow key={index}>
                      {usageColumns.map((column) => (
                        <TableCell key={column.key}>
                          {column.render ? column.render(item[column.key]) : item[column.key]}
                        </TableCell>
                      ))}
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell colSpan={usageColumns.length} className="text-center py-8 text-gray-500">
                      {t('No usage history found')}
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
          
          {/* Pagination */}
          {usage_history.last_page > 1 && (
            <div className="mt-4 border rounded-lg dark:border-gray-700 overflow-hidden">
              <Pagination
                from={usage_history.from || 0}
                to={usage_history.to || 0}
                total={usage_history.total || 0}
                currentPage={usage_history.current_page}
                lastPage={usage_history.last_page}
                entityName={t('records')}
                onPageChange={(url) => {
                  const urlObj = new URL(url, window.location.origin);
                  const page = urlObj.searchParams.get('page');
                  window.location.href = route('coupons.show', { 
                    coupon: coupon.id, 
                    page: page
                  });
                }}
              />
            </div>
          )}
        </Card>
      </div>
    </PageWrapper>
  );
}