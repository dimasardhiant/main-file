// pages/hr/payroll-runs/show.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { ArrowLeft, Download, Users, DollarSign, Calendar } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from '@/components/custom-toast';
import { CrudTable } from '@/components/CrudTable';

export default function PayrollRunShow() {
  const { t } = useTranslation();
  const { payrollRun } = usePage().props as any;

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Payroll Management'), href: route('hr.payroll-runs.index') },
    { title: t('Payroll Runs'), href: route('hr.payroll-runs.index') },
    { title: payrollRun.title }
  ];

  const pageActions = [
    {
      label: t('Back to List'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('hr.payroll-runs.index'))
    }
  ];

  // Add generate payslips button if payroll is completed
  if (payrollRun.status === 'completed') {
    pageActions.unshift({
      label: t('Generate Payslips'),
      icon: <Download className="h-4 w-4 mr-2" />,
      variant: 'default',
      onClick: () => handleGeneratePayslips()
    });
  }

  const handleGeneratePayslips = () => {
    toast.loading(t('Generating payslips...'));

    router.post(route('hr.payslips.bulk-generate'), {
      payroll_run_id: payrollRun.id
    }, {
      onSuccess: (page) => {
        toast.dismiss();
        if (page.props.flash.success) {
          toast.success(t(page.props.flash.success));
          // Redirect to payslips page to see generated payslips
          setTimeout(() => {
            router.get(route('hr.payslips.index'));
          }, 1000);
        } else if (page.props.flash.error) {
          toast.error(t(page.props.flash.error));
        }
      },
      onError: (errors) => {
        toast.dismiss();
        if (typeof errors === 'string') {
          toast.error(errors);
        } else {
          toast.error('Failed to generate payslips');
        }
      }
    });
  };

  const getStatusColor = (status: string) => {
    const colors = {
      draft: 'bg-gray-50 text-gray-700 ring-gray-600/20',
      processing: 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
      completed: 'bg-green-50 text-green-700 ring-green-600/20',
      cancelled: 'bg-red-50 text-red-700 ring-red-600/20'
    };
    return colors[status as keyof typeof colors] || colors.draft;
  };

  return (
    <PageTemplate
      title={payrollRun.title}
      url={`/hr/payroll-runs/${payrollRun.id}`}
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Payroll Run Summary */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('Total Employees')}</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{payrollRun.employee_count}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('Gross Pay')}</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-green-600">{window.appSettings?.formatCurrency(payrollRun.total_gross_pay)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('Total Deductions')}</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-red-600">{window.appSettings?.formatCurrency(payrollRun.total_deductions)}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('Net Pay')}</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-blue-600">{window.appSettings?.formatCurrency(payrollRun.total_net_pay)}</div>
          </CardContent>
        </Card>
      </div>

      {/* Payroll Run Details */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>{t('Payroll Run Details')}</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Pay Period')}</label>
              <p className="text-sm">
                {window.appSettings?.formatDateTimeSimple(payrollRun.pay_period_start, false) || new Date(payrollRun.pay_period_start).toLocaleDateString()} - {window.appSettings?.formatDateTimeSimple(payrollRun.pay_period_end, false) || new Date(payrollRun.pay_period_end).toLocaleDateString()}
              </p>
            </div>
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Pay Date')}</label>
              <p className="text-sm">{ window.appSettings?.formatDateTimeSimple(payrollRun.pay_date, false) || new Date(payrollRun.pay_date).toLocaleDateString()}</p>
            </div>
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Payroll Frequency')}</label>
              <p className="text-sm">
                <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                  {payrollRun.payroll_frequency === 'weekly' ? t('Weekly') : payrollRun.payroll_frequency === 'biweekly' ? t('Bi-Weekly') : t('Monthly')}
                </span>
              </p>
            </div>
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Status')}</label>
              <p>
                <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ${getStatusColor(payrollRun.status)}`}>
                  {t(payrollRun.status)}
                </span>
              </p>
            </div>
            <div>
              <label className="text-sm font-medium text-gray-500">{t('Created At')}</label>
              <p className="text-sm">{window.appSettings?.formatDateTimeSimple(payrollRun.created_at, false) || new Date(payrollRun.created_at).toLocaleDateString()}</p>
            </div>
          </div>
          {payrollRun.notes && (
            <div className="mt-4">
              <label className="text-sm font-medium text-gray-500">{t('Notes')}</label>
              <p className="text-sm mt-1">{payrollRun.notes}</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Employee Payroll Entries */}
      <Card>
        <CardHeader>
          <CardTitle>{t('Employee Payroll Entries')}</CardTitle>
          <div className="mt-2 p-3 bg-blue-50 rounded-md space-y-2">
            <p className="text-sm text-blue-800 font-medium">
              {t('Gross Pay Formula')} : <span className="font-mono">Total Earnings (Basic Salary + Component Earning) - Unpaid Leave Deduction + Overtime Earnings</span>
            </p>
            <p className="text-sm text-blue-800 font-medium">
              {t('Net Salary Formula')} : <span className="font-mono">Gross Pay - Total Deductions</span>
            </p>
          </div>
        </CardHeader>
        <CardContent>
          <CrudTable
            columns={[
              {
                key: 'employee',
                label: t('Employee'),
                render: (value: any, row: any) => row.employee?.name || '-',
              },
              {
                key: 'basic_salary',
                label: t('Basic Salary'),
                render: (value: number) => (
                  <span className="font-mono text-gray-900">{window.appSettings?.formatCurrency(value)}</span>
                )
              },
              {
                key: 'per_day_salary',
                label: t('Per Day Salary'),
                render: (value: number) => (
                  <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(value || 0)}</span>
                )
              },
              {
                key: 'component_earnings',
                label: t('Component Earnings'),
                render: (value: number) => (
                  <span className="font-mono text-blue-600">{window.appSettings?.formatCurrency(value || 0)}</span>
                )
              },
              {
                key: 'total_earnings',
                label: t('Total Earning'),
                render: (value: number) => (
                  <span className="font-mono text-blue-600">{window.appSettings?.formatCurrency(value || 0)}</span>
                )
              },
              {
                key: 'working_days',
                label: t('Working Days'),
                render: (value: number) => value || 0
              },
              {
                key: 'present_days',
                label: t('Present Days'),
                render: (value: number) => value || 0
              },
              {
                key: 'overtime_hours',
                label: t('Overtime Hours'),
                render: (value: number) => `${value || 0}h`
              },
              {
                key: 'overtime_amount',
                label: t('Overtime Earnings'),
                render: (value: number) => (
                  <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(value || 0)}</span>
                )
              },
              {
                key: 'unpaid_leave_deduction',
                label: t('Leave Deductions'),
                render: (value: number) => (
                  <span className="font-mono text-red-600">{window.appSettings?.formatCurrency(value || 0)}</span>
                )
              },
              {
                key: 'gross_pay',
                label: t('Gross Pay'),
                render: (value: number) => (
                  <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(value)}</span>
                )
              },
              {
                key: 'total_deductions',
                label: t('Component Deduction'),
                render: (value: number) => (
                  <span className="font-mono text-red-600">{window.appSettings?.formatCurrency(value)}</span>
                )
              },
              {
                key: 'net_pay',
                label: t('Net Pay'),
                render: (value: number) => (
                  <span className="font-mono text-blue-600 font-bold">{window.appSettings?.formatCurrency(value)}</span>
                )
              }
            ]}
            data={payrollRun.payroll_entries || []}
            from={1}
            onAction={() => { }}
            permissions={[]}
            entityPermissions={{}}
            actions={[]}
            showActions={false}

          />
        </CardContent>
      </Card>
    </PageTemplate>
  );
}