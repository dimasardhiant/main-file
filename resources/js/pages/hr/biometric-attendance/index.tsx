import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { RefreshCw, Timer, CheckCircle, XCircle, Clock, Eye } from 'lucide-react';
import { hasPermission } from '@/utils/authorization';
import { CrudTable } from '@/components/CrudTable';
import { toast } from '@/components/custom-toast';
import { useTranslation } from 'react-i18next';
import { Pagination } from '@/components/ui/pagination';
import { SearchAndFilterBar } from '@/components/ui/search-and-filter-bar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

export default function BiometricAttendance() {
  const { t } = useTranslation();
  const { auth, biometricData, filters: pageFilters = {}, configurationMissing } = usePage().props as any;
  const permissions = auth?.permissions || [];

  // State
  const [searchTerm, setSearchTerm] = useState(pageFilters.search || '');
  const [startDate, setStartDate] = useState(pageFilters.start_date || '');
  const [endDate, setEndDate] = useState(pageFilters.end_date || '');
  const [showFilters, setShowFilters] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [detailEntries, setDetailEntries] = useState([]);
  const [selectedEmployee, setSelectedEmployee] = useState(null);

  // Check if any filters are active
  const hasActiveFilters = () => {
    return searchTerm !== '' || startDate !== '' || endDate !== '';
  };

  // Count active filters
  const activeFilterCount = () => {
    return (searchTerm ? 1 : 0) + (startDate ? 1 : 0) + (endDate ? 1 : 0);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    applyFilters();
  };

  const applyFilters = () => {
    router.get(route('hr.biometric-attendance.index'), {
      page: 1,
      search: searchTerm || undefined,
      start_date: startDate || undefined,
      end_date: endDate || undefined,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const handleAction = (action: string, row: any) => {
    switch (action) {
      case 'view':
        handleShowDetails(row);
        break;
      case 'sync':
        handleSync(row);
        break;
    }
  };

  const handleShowDetails = async (row: any) => {
    try {
      const response = await fetch(route('hr.biometric-attendance.show', {
        employeeCode: row.employee_code,
        date: row.date
      }));

      const result = await response.json();

      if (result.success) {
        setDetailEntries(result.data.entries);
        setSelectedEmployee({
          code: result.data.employee_code,
          date: result.data.date,
          name: row.name
        });
        setShowDetailsModal(true);
      } else {
        toast.error(t(result.message || 'Failed to fetch details'));
      }
    } catch (error) {
      toast.error(t('Error fetching details'));
    }
  };

  const handleSync = (row: any) => {
    toast.loading(t('Syncing biometric data...'));

    const syncData = {
      biometric_emp_id: row.employee_code,
      biometric_id: row.id,
      date: row.date,
      clock_in: row.clock_in,
      clock_out: row.clock_out
    };

    router.post(route('hr.biometric-attendance.sync', row.id), syncData, {
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
          toast.error(errors);
        } else {
          toast.error(`Failed to sync: ${Object.values(errors).join(', ')}`);
        }
      }
    });
  };

  const handleResetFilters = () => {
    setSearchTerm('');
    setStartDate('');
    setEndDate('');
    setShowFilters(false);

    router.get(route('hr.biometric-attendance.index'), {
      page: 1,
      per_page: pageFilters.per_page
    }, { preserveState: true, preserveScroll: true });
  };

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Biometric Attendance') }
  ];

  // Define table actions
  const actions = [
    {
      label: t('Details'),
      icon: 'Eye',
      action: 'view',
      className: 'text-blue-500',
      requiredPermission: 'view-biometric-attendance'
    },
    {
      label: t('Sync'),
      icon: 'RefreshCw',
      action: 'sync',
      className: 'text-green-500',
      requiredPermission: 'sync-biometric-attendance',
      condition: (row: any) => row.sync_status !== 'synced'
    }
  ];

  // Define table columns
  const columns = [
    {
      key: 'employee_code',
      label: t('Employee Code'),
      sortable: false,
      render: (value: string) => (
        <span className="font-mono text-sm font-medium">{value}</span>
      )
    },
    {
      key: 'name',
      label: t('Employee Name'),
      sortable: false,
      render: (value: string) => (
        <span className="font-medium">{value}</span>
      )
    },
    {
      key: 'date',
      label: t('Date'),
      sortable: false,
      render: (value: string) => (
        <span className="text-sm">{window.appSettings.formatDateTimeSimple(value, false)}</span>
      )
    },
    {
      key: 'clock_in',
      label: t('Clock In'),
      sortable: false,
      render: (value: string) => (
        <span className="font-mono text-sm font-medium text-green-600">{value || '-'}</span>
      )
    },
    {
      key: 'clock_out',
      label: t('Clock Out'),
      sortable: false,
      render: (value: string) => (
        <span className="font-mono text-sm font-medium text-red-600">{value || '-'}</span>
      )
    },
    {
      key: 'total_entries',
      label: t('Total Entries'),
      sortable: false,
      render: (value: number) => (
        <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
          {value}
        </span>
      )
    },

  ];

  return (
    <PageTemplate
      title={t("Biometric Attendance")}
      url="/hr/biometric-attendance"
      breadcrumbs={breadcrumbs}
      noPadding
    >
      {/* Search and filters section */}
      {!configurationMissing && (
        <div className="bg-white dark:bg-gray-900 rounded-lg shadow mb-4 p-4">
          <SearchAndFilterBar
            searchTerm={searchTerm}
            onSearchChange={setSearchTerm}
            onSearch={handleSearch}
            searchPlaceholder={t('Search by employee name or code...')}
            filters={[
              {
                name: 'start_date',
                label: t('Start Date'),
                type: 'date',
                value: startDate,
                onChange: setStartDate
              },
              {
                name: 'end_date',
                label: t('End Date'),
                type: 'date',
                value: endDate,
                onChange: setEndDate
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
              router.get(route('hr.biometric-attendance.index'), {
                page: 1,
                per_page: parseInt(value),
                search: searchTerm || undefined,
                start_date: startDate || undefined,
                end_date: endDate || undefined
              }, { preserveState: true, preserveScroll: true });
            }}
          />
        </div>
      )}

      {/* Content section */}
      <div className="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        {/* Header with info */}
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <Timer className="h-4 w-4" />
            <span>{t('Biometric attendance data from device')}</span>
          </div>
        </div>

        {configurationMissing ? (
          <div className="px-6 py-12 text-center">
            <div className="max-w-md mx-auto">
              <div className="mb-4">
                <Timer className="h-16 w-16 mx-auto text-gray-400" />
              </div>
              <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                {t('Configuration Required')}
              </h3>
              <p className="text-gray-600 dark:text-gray-400 mb-6">
                {t('Please configure ZKTeco API settings to fetch biometric attendance data.')}
              </p>
              <div className="space-y-2 text-sm text-gray-500 dark:text-gray-400">
                <p>{t('Required settings:')}</p>
                <ul className="list-disc list-inside space-y-1">
                  <li>{t('ZKTeco API URL')}</li>
                  <li>{t('Username')}</li>
                  <li>{t('Password')}</li>
                  <li>{t('Auth Token')}</li>
                </ul>
              </div>
            </div>
          </div>
        ) : (
          <>
            <CrudTable
              columns={columns}
              actions={actions}
              data={biometricData?.data || []}
              from={biometricData?.from || 1}
              onAction={handleAction}
              permissions={permissions}
              entityPermissions={{
                view: 'view-biometric-attendance',
                create: 'manage-biometric-attendance',
                edit: 'manage-biometric-attendance',
                delete: 'manage-biometric-attendance'
              }}
            />

            {/* Pagination section */}
            <Pagination
              from={biometricData?.from || 0}
              to={biometricData?.to || 0}
              total={biometricData?.total || 0}
              links={biometricData?.links}
              entityName={t("biometric records")}
              onPageChange={(url) => router.get(url)}
            />
          </>
        )}
      </div>

      {/* Details Modal */}
      <Dialog open={showDetailsModal} onOpenChange={setShowDetailsModal}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>
              {t('Punch Details')} - {selectedEmployee?.name} ({selectedEmployee?.code})
              <br />
              <span className="text-sm font-normal text-gray-600">
                {selectedEmployee?.date && window.appSettings.formatDateTimeSimple(selectedEmployee.date, false)}
              </span>
            </DialogTitle>
          </DialogHeader>
          <div className="max-h-96 overflow-y-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b">
                  <th className="text-left p-2">{t('Time')}</th>
                  <th className="text-left p-2">{t('Status')}</th>
                  <th className="text-left p-2">{t('Verify Type')}</th>
                  <th className="text-left p-2">{t('Terminal')}</th>
                </tr>
              </thead>
              <tbody>
                {detailEntries.map((entry: any, index: number) => (
                  <tr key={entry.id} className="border-b hover:bg-gray-50">
                    <td className="p-2 font-mono">{entry.time}</td>
                    <td className="p-2">{entry.punch_state_display}</td>
                    <td className="p-2">{entry.verify_type_display}</td>
                    <td className="p-2">{entry.terminal_alias}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            {detailEntries.length === 0 && (
              <div className="text-center py-8 text-gray-500">
                {t('No entries found')}
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}