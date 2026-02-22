// pages/hr/employee-salaries/payroll-calculation.tsx
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { ArrowLeft, Calculator, Clock, DollarSign, TrendingUp, TrendingDown } from 'lucide-react';
import { useState, useEffect } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from '@/components/custom-toast';

export default function PayrollCalculation() {
  const { t } = useTranslation();
  const { employeeSalary, payrollRuns, selectedPayrollRun, payrollData } = usePage().props as any;
  
  const [currentPayrollRun, setCurrentPayrollRun] = useState(selectedPayrollRun);
  const [salaryBreakdown, setSalaryBreakdown] = useState(payrollData?.salaryBreakdown || { earnings: {}, deductions: {} });
  const [attendanceSummary, setAttendanceSummary] = useState(payrollData?.attendanceSummary || {});
  const [payrollCalculation, setPayrollCalculation] = useState(payrollData?.payrollCalculation || {});
  const [attendanceRecords, setAttendanceRecords] = useState(payrollData?.attendanceRecords || []);
  const [currentMonth, setCurrentMonth] = useState(payrollData?.currentMonth || null);
  const [loading, setLoading] = useState(false);

  const handlePayrollChange = async (payrollRunId: string) => {
    if (payrollRunId === currentPayrollRun?.id?.toString()) return;
    
    setLoading(true);
    try {
      const response = await fetch(route('hr.employee-salaries.get-payroll-calculation', {
        employeeSalary: employeeSalary.id,
        payrollRun: payrollRunId
      }));
      
      if (response.ok) {
        const data = await response.json();
        const selectedRun = payrollRuns.find((run: any) => run.id.toString() === payrollRunId);
        
        setCurrentPayrollRun(selectedRun);
        setSalaryBreakdown(data.salaryBreakdown || { earnings: {}, deductions: {} });
        setAttendanceSummary(data.attendanceSummary || {});
        setPayrollCalculation(data.payrollCalculation || {});
        setAttendanceRecords(data.attendanceRecords || []);
        setCurrentMonth(data.currentMonth);
      } else {
        toast.error(t('Failed to load payroll data'));
      }
    } catch (error) {
      toast.error(t('Failed to load payroll data'));
    } finally {
      setLoading(false);
    }
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Payroll Management'), href: route('hr.employee-salaries.index') },
    { title: t('Employee Salaries'), href: route('hr.employee-salaries.index') },
    { title: t('Payroll Calculation') }
  ];

  const pageActions = [
    {
      label: t('Back to Salaries'),
      icon: <ArrowLeft className="h-4 w-4 mr-2" />,
      variant: 'outline',
      onClick: () => router.get(route('hr.employee-salaries.index'))
    }
  ];

  return (
    <PageTemplate
      title={`${t('Payroll Calculation')} - ${employeeSalary.employee.name}`}
      url="/hr/employee-salaries/payroll-calculation"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
      noPadding
    >
      <div className="space-y-6">
        {/* Employee Info */}
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-4">
              <Calculator className="h-8 w-8 text-purple-600" />
              <div>
                <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                  {employeeSalary.employee.name}
                </h2>
                <p className="text-gray-600 dark:text-gray-400">
                  {t('Payroll Calculation for')} {currentMonth ? new Date(currentMonth).toLocaleDateString('en-US', { year: 'numeric', month: 'long' }) : '-'}
                </p>
              </div>
            </div>
            
            {/* Payroll Dropdown */}
            <div className="w-64">
              <Select
                value={currentPayrollRun?.id?.toString() || ''}
                onValueChange={handlePayrollChange}
                disabled={loading}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t('Select Payroll Run')} />
                </SelectTrigger>
                <SelectContent>
                  {payrollRuns?.map((run: any) => (
                    <SelectItem key={run.id} value={run.id.toString()}>
                      {run.title} ({new Date(run.pay_period_start).toLocaleDateString()} - {new Date(run.pay_period_end).toLocaleDateString()})
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
              <div className="flex items-center gap-2">
                <DollarSign className="h-5 w-5 text-blue-600" />
                <span className="text-sm font-medium text-blue-600">{t('Basic Salary')}</span>
              </div>
              <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                {window.appSettings?.formatCurrency(employeeSalary.basic_salary || 0)}
              </p>
            </div>
            
            <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
              <div className="flex items-center gap-2">
                <Clock className="h-5 w-5 text-green-600" />
                <span className="text-sm font-medium text-green-600">{t('Working Days')}</span>
              </div>
              <p className="text-2xl font-bold text-green-900 dark:text-green-100">
                {attendanceSummary.total_working_days || 0}
              </p>
            </div>
            
            <div className="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
              <div className="flex items-center gap-2">
                <TrendingUp className="h-5 w-5 text-purple-600" />
                <span className="text-sm font-medium text-purple-600">{t('Net Salary')}</span>
              </div>
              <p className="text-2xl font-bold text-purple-900 dark:text-purple-100">
                {window.appSettings?.formatCurrency(payrollCalculation.net_salary || 0)}
              </p>
            </div>
          </div>
        </div>

        {/* Loading State */}
        {loading && (
          <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
            <div className="flex items-center justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
              <span className="ml-2 text-gray-600 dark:text-gray-400">{t('Loading payroll data...')}</span>
            </div>
          </div>
        )}

        {/* Attendance Summary */}
        {!loading && (
          <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              {t('Attendance Summary')}
            </h3>
            
            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
              <div className="text-center">
                <p className="text-2xl font-bold text-blue-600">{attendanceSummary.total_working_days}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Working Days')}</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-green-600">{attendanceSummary.present_days}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Present Days')}</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-yellow-600">{attendanceSummary.half_days}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Half Days')}</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-red-600">{attendanceSummary.absent_days}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Absent Days')}</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-indigo-600">{attendanceSummary.leave_days || 0}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Paid Leave Days')}</p>
              </div>
               <div className="text-center">
                <p className="text-2xl font-bold text-indigo-600">{attendanceSummary.unpaid_leave_days || 0}</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Total Unpaid Leave')}</p>
                <p className="text-xs text-gray-500 dark:text-gray-500">(Unpaid Leaves + Half Days + Absent)</p>
              </div>
              <div className="text-center">
                <p className="text-2xl font-bold text-orange-600">{(Number(attendanceSummary.overtime_hours) || 0).toFixed(1)}h</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{t('Overtime')}</p>
              </div>
            </div>
          </div>
        )}

        {/* Salary Breakdown */}
        {!loading && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Earnings */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
              <div className="flex items-center gap-2 mb-4">
                <TrendingUp className="h-5 w-5 text-green-600" />
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                  {t('Earnings')}
                </h3>
              </div>
              
              <div className="space-y-3">
                {Object.entries(salaryBreakdown.earnings || {}).map(([component, amount]: [string, any]) => (
                  <div key={component} className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span className="text-gray-700 dark:text-gray-300">{component}</span>
                    <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(amount)}</span>
                  </div>
                ))}
                <div className="flex justify-between items-center py-2 font-semibold text-lg border-t-2 border-green-200">
                  <span className="text-gray-900 dark:text-white">{t('Total Earnings')}</span>
                  <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(payrollCalculation.total_earnings || 0)}</span>
                </div>
              </div>
            </div>

            {/* Deductions */}
            <div className="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
              <div className="flex items-center gap-2 mb-4">
                <TrendingDown className="h-5 w-5 text-red-600" />
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                  {t('Deductions')}
                </h3>
              </div>
              
              <div className="space-y-3">
                {Object.entries(salaryBreakdown.deductions || {}).map(([component, amount]: [string, any]) => (
                  <div key={component} className="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <span className="text-gray-700 dark:text-gray-300">{component}</span>
                    <span className="font-mono text-red-600">{window.appSettings?.formatCurrency(amount)}</span>
                  </div>
                ))}
                <div className="flex justify-between items-center py-2 font-semibold text-lg border-t-2 border-red-200">
                  <span className="text-gray-900 dark:text-white">{t('Total Deductions')}</span>
                  <span className="font-mono text-red-600">{window.appSettings?.formatCurrency(payrollCalculation.total_deductions || 0)}</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Attendance Records Table */}
        {!loading && (
          <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                {t('Daily Attendance Records')}
              </h3>
            </div>
            
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-800">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('Date')}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('Clock In')}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('Clock Out')}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('Total Hours')}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('Overtime')}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                      {t('Status')}
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                  {attendanceRecords && attendanceRecords.length > 0 ? (
                    attendanceRecords.map((record: any, index: number) => (
                      <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                          {window.appSettings?.formatDateTimeSimple(record.date, false) || new Date(record.date).toLocaleDateString()}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-green-600">
                          {record.clock_in || '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-red-600">
                          {record.clock_out || '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                          {record.total_hours ? `${Number(record.total_hours).toFixed(2)}h` : '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-orange-600">
                          {record.overtime_hours > 0 ? `${Number(record.overtime_hours).toFixed(1)}h` : '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${
                            record.status === 'present' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' :
                            record.status === 'absent' ? 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20' :
                            record.status === 'half_day' ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20' :
                            record.status === 'holiday' ? 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20' :
                            record.status === 'on_leave' ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' :
                            'bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20'
                          }`}>
                            {record.status === 'present' ? t('Present') :
                             record.status === 'absent' ? t('Absent') :
                             record.status === 'half_day' ? t('Half Day') :
                             record.status === 'holiday' ? t('Holiday') :
                             record.status === 'on_leave' ? t('On Leave') :
                             record.status || '-'}
                          </span>
                          {record.is_late && (
                            <span className="ml-1 inline-flex items-center rounded-md px-1 py-0.5 text-xs font-medium bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20">
                              {t('Late')}
                            </span>
                          )}
                          {record.is_early_departure && (
                            <span className="ml-1 inline-flex items-center rounded-md px-1 py-0.5 text-xs font-medium bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20">
                              {t('Early')}
                            </span>
                          )}
                        </td>
                      </tr>
                    ))
                  ) : (
                    <tr>
                      <td colSpan={6} className="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        {t('No attendance records found for this month')}
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Final Calculation */}
        {!loading && (
          <div className="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg shadow p-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              {t('Final Calculation')}
            </h3>
            
            <div className="space-y-3">
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Base Salary')}</span>
                <span className="font-mono text-blue-600">{window.appSettings?.formatCurrency(employeeSalary.basic_salary)}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Total Earnings')}</span>
                <span className="font-mono text-green-600">{window.appSettings?.formatCurrency(payrollCalculation.total_earnings || 0)}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Per Day Salary')}</span>
                <span className="font-mono text-blue-600">{window.appSettings?.formatCurrency(payrollCalculation.per_day_salary || 0)}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Unpaid Leave Deduction')} ({attendanceSummary.unpaid_leave_from_leave || 0} days)</span>
                <span className="font-mono text-red-600">- {window.appSettings?.formatCurrency((payrollCalculation.per_day_salary || 0) * (attendanceSummary.unpaid_leave_from_leave || 0))}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Half Day Deduction')} ({attendanceSummary.half_days} × 0.5)</span>
                <span className="font-mono text-orange-600">- {window.appSettings?.formatCurrency((payrollCalculation.per_day_salary || 0) * (attendanceSummary.half_days || 0) * 0.5)}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Absent Day Deduction')} ({attendanceSummary.absent_days} days)</span>
                <span className="font-mono text-red-600">- {window.appSettings?.formatCurrency((payrollCalculation.per_day_salary || 0) * (attendanceSummary.absent_days || 0))}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Overtime Amount')}</span>
                <span className="font-mono text-green-600">+ {window.appSettings?.formatCurrency(payrollCalculation.overtime_amount || 0)}</span>
              </div>
              <div className="flex justify-between items-center py-2">
                <span className="text-gray-700 dark:text-gray-300">{t('Total Deductions')}</span>
                <span className="font-mono text-red-600">- {window.appSettings?.formatCurrency(payrollCalculation.total_deductions || 0)}</span>
              </div>
              <div className="flex justify-between items-center py-3 font-bold text-xl border-t-2 border-purple-200">
                <span className="text-gray-900 dark:text-white">{t('Net Salary')}</span>
                <span className="font-mono text-purple-600">{window.appSettings?.formatCurrency(payrollCalculation.net_salary || 0)}</span>
              </div>
            </div>
            
            <div className="mt-4 p-4 bg-white dark:bg-gray-800 rounded-lg">
              <p className="text-sm text-gray-600 dark:text-gray-400">
                <strong>{t('Calculation Formula')}:</strong> {t('Net Salary')} = ({t('Basic Salary')} + {t('Allowances')}) - ({t('Per Day Salary')} × {t('Unpaid Leave Days')}) + {t('Overtime')} - {t('Deductions')}
              </p>
              <div className="text-sm text-gray-600 dark:text-gray-400 mt-2">
                <p><strong>{t('Unpaid Leave Days Breakdown')}:</strong></p>
                <p>• Unpaid Leaves: {attendanceSummary.unpaid_leave_from_leave || 0} days</p>
                <p>• Absent Days: {attendanceSummary.absent_days || 0} days</p>
                <p>• Half Days: {attendanceSummary.half_days || 0} × 0.5 = {((attendanceSummary.half_days || 0) * 0.5)} days</p>
                <p><strong>Total Unpaid Days: {attendanceSummary.unpaid_leave_days || 0} days</strong></p>
              </div>
            </div>
          </div>
        )}
      </div>
    </PageTemplate>
  );
}