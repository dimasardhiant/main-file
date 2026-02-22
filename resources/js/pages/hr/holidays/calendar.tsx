// pages/hr/holidays/calendar.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';
import { List, Download, FileText } from 'lucide-react';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

export default function HolidayCalendar() {
  const { t } = useTranslation();
  const { calendarEvents, branches, categories, years, currentYear, filters = {} } = usePage().props as any;
  
  // State
  const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
  const [selectedBranch, setSelectedBranch] = useState(filters.branch_id || '');
  const [selectedYear, setSelectedYear] = useState(currentYear.toString());
  const [selectedEvent, setSelectedEvent] = useState<any>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  
  // Handle event click
  const handleEventClick = (clickInfo: any) => {
    const event = clickInfo.event;
    setSelectedEvent({
      id: event.id,
      title: event.title,
      start: event.start,
      end: event.end,
      ...event.extendedProps
    });
    setIsDialogOpen(true);
  };
  
  
  

  
  // Handle back to list view
  const handleBackToList = () => {
    router.get(route('hr.holidays.index'), {
      year: selectedYear,
      category: selectedCategory || undefined,
      branch_id: selectedBranch || undefined
    });
  };
  
  // Handle export PDF
  const handleExportPdf = () => {
    window.open(route('hr.holidays.export.pdf', {
      year: selectedYear,
      category: selectedCategory || undefined,
      branch_id: selectedBranch || undefined
    }), '_blank');
  };
  
  // Handle export iCal
  const handleExportIcal = () => {
    window.open(route('hr.holidays.export.ical', {
      year: selectedYear,
      category: selectedCategory || undefined,
      branch_id: selectedBranch || undefined
    }), '_blank');
  };
  

  
  // Define page actions
  const pageActions = [
    {
      label: t('List View'),
      icon: <List className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleBackToList
    },
    {
      label: t('Export PDF'),
      icon: <FileText className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleExportPdf
    },
    {
      label: t('Export iCal'),
      icon: <Download className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleExportIcal
    }
  ];
  
  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.holidays.index') },
    { title: t('Holidays'), href: route('hr.holidays.index') },
    { title: t('Calendar') }
  ];
  
  // Prepare category options for filter
  const categoryOptions = [
    { value: '_none_', label: t('All Categories') },
    ...(categories || []).map((category: string) => ({
      value: category,
      label: category.charAt(0).toUpperCase() + category.slice(1)
    }))
  ];

  // Prepare branch options for filter
  const branchOptions = [
    { value: '_none_', label: t('All Branches') },
    ...(branches || []).map((branch: any) => ({
      value: branch.id.toString(),
      label: branch.name
    }))
  ];

  // Prepare year options
  const yearOptions = [
    ...(years || []).map((year: number) => ({
      value: year.toString(),
      label: year.toString()
    }))
  ];
  

  
  return (
    <PageTemplate 
      title={`${t("Holiday Calendar")} - ${selectedYear}`} 
      url="/hr/holidays/calendar"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >

      
      {/* Legend */}
      <div className="mb-6 flex flex-wrap gap-2">
        <Badge variant="secondary" className="bg-blue-50 text-blue-700 hover:bg-blue-50">
          {t('National')}
        </Badge>
        <Badge variant="secondary" className="bg-purple-50 text-purple-700 hover:bg-purple-50">
          {t('Religious')}
        </Badge>
        <Badge variant="secondary" className="bg-green-50 text-green-700 hover:bg-green-50">
          {t('Company Specific')}
        </Badge>
        <Badge variant="secondary" className="bg-amber-50 text-amber-700 hover:bg-amber-50">
          {t('Regional')}
        </Badge>
      </div>

      {/* Calendar */}
      <div className="bg-white rounded-lg shadow p-6">
        <div style={{ height: '600px' }}>
          <FullCalendar
            plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            headerToolbar={{
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,timeGridDay'
            }}
            buttonText={{
              today: t('Today'),
              month: t('Month'),
              week: t('Week'),
              day: t('Day')
            }}
            events={calendarEvents}
            height="100%"
            eventClick={handleEventClick}
            dayMaxEvents={true}
            weekends={true}
          />
        </div>
      </div>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>{selectedEvent?.title}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <Badge variant="outline">{selectedEvent?.category}</Badge>
              {selectedEvent?.is_half_day && (
                <Badge variant="secondary">{t('Half Day')}</Badge>
              )}
              {selectedEvent?.is_paid ? (
                <Badge variant="outline" className="bg-green-50 text-green-700">{t('Paid')}</Badge>
              ) : (
                <Badge variant="outline" className="bg-red-50 text-red-700">{t('Unpaid')}</Badge>
              )}
            </div>
            {selectedEvent?.description && (
              <div>
                <p className="text-sm text-muted-foreground">{t('Description')}</p>
                <p className="font-medium">{selectedEvent.description}</p>
              </div>
            )}
            <div>
              <p className="text-sm text-muted-foreground">{t('Start Date')}</p>
              <p className="font-medium">
                {selectedEvent?.start ? window.appSettings?.formatDateTimeSimple(selectedEvent.start, false) || new Date(selectedEvent.start).toLocaleDateString() : ''}
              </p>
            </div>
            {selectedEvent?.end && (
              <div>
                <p className="text-sm text-muted-foreground">{t('End Date')}</p>
                <p className="font-medium">
                  {window.appSettings?.formatDateTimeSimple(selectedEvent.end, false) || new Date(selectedEvent.end).toLocaleDateString()}
                </p>
              </div>
            )}
            {selectedEvent?.branches && selectedEvent.branches.length > 0 && (
              <div>
                <p className="text-sm text-muted-foreground">{t('Branches')}</p>
                <div className="flex flex-wrap gap-1">
                  {selectedEvent.branches.map((branch: string, index: number) => (
                    <Badge key={index} variant="outline" className="text-xs">{branch}</Badge>
                  ))}
                </div>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}