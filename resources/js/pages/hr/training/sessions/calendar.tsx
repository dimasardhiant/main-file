
// pages/hr/training/sessions/calendar.tsx
import { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import { usePage, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { List } from 'lucide-react';
import { Card } from '@/components/ui/card';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function TrainingSessionsCalendar() {
  const { t } = useTranslation();
  const { calendarEvents, trainingPrograms, filters: pageFilters = {} } = usePage().props as any;
  
  // State
  const [selectedProgram, setSelectedProgram] = useState(pageFilters.training_program_id || 'all');
  const [selectedStatus, setSelectedStatus] = useState(pageFilters.status || 'all');
  
  const handleViewList = () => {
    router.get(route('hr.training-sessions.index'));
  };
  
  const handleEventClick = (info: any) => {
    const eventId = info.event.id;
    router.get(route('hr.training-sessions.show', eventId));
  };
  
  const handleFilterChange = (field: string, value: string) => {
    router.get(route('hr.training-sessions.calendar'), { 
      [field]: value === 'all' ? undefined : value,
      ...(field !== 'training_program_id' && selectedProgram !== 'all' ? { training_program_id: selectedProgram } : {}),
      ...(field !== 'status' && selectedStatus !== 'all' ? { status: selectedStatus } : {})
    }, { preserveState: true });
  };

  // Define page actions
  const pageActions = [
    {
      label: t('List View'),
      icon: <List className="h-4 w-4 mr-2" />,
      variant: 'outline' as const,
      onClick: handleViewList
    }
  ];

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('HR Management'), href: route('hr.training-sessions.index') },
    { title: t('Training Management'), href: route('hr.training-sessions.index') },
    { title: t('Training Sessions'), href: route('hr.training-sessions.index') },
    { title: t('Calendar') }
  ];

  // Prepare training program options for filter
  const trainingProgramOptions = [
    { value: 'all', label: t('All Programs') },
    ...(trainingPrograms || []).map((program: any) => ({
      value: program.id.toString(),
      label: program.name
    }))
  ];

  // Prepare status options for filter
  const statusOptions = [
    { value: 'all', label: t('All Statuses') },
    { value: 'scheduled', label: t('Scheduled') },
    { value: 'in_progress', label: t('In Progress') },
    { value: 'completed', label: t('Completed') },
    { value: 'cancelled', label: t('Cancelled') }
  ];

  return (
    <PageTemplate 
      title={t("Training Sessions Calendar")} 
      url="/hr/training/sessions/calendar"
      actions={pageActions}
      breadcrumbs={breadcrumbs}
    >
      {/* Filters */}
      <div className="flex flex-wrap gap-4 mb-4">
        <div className="w-full md:w-64">
          <label className="block text-sm font-medium mb-1">{t('Program')}</label>
          <Select
            value={selectedProgram}
            onValueChange={(value) => {
              setSelectedProgram(value);
              handleFilterChange('training_program_id', value);
            }}
          >
            <SelectTrigger>
              <SelectValue placeholder={t('All Programs')} />
            </SelectTrigger>
            <SelectContent>
              {trainingProgramOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        
        <div className="w-full md:w-64">
          <label className="block text-sm font-medium mb-1">{t('Status')}</label>
          <Select
            value={selectedStatus}
            onValueChange={(value) => {
              setSelectedStatus(value);
              handleFilterChange('status', value);
            }}
          >
            <SelectTrigger>
              <SelectValue placeholder={t('All Statuses')} />
            </SelectTrigger>
            <SelectContent>
              {statusOptions.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
      
      {/* Calendar */}
      <Card className="p-4">
        <FullCalendar
          plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
          initialView="dayGridMonth"
          headerToolbar={{
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
          }}
          events={calendarEvents}
          eventClick={handleEventClick}
          eventTimeFormat={{
            hour: '2-digit',
            minute: '2-digit',
            meridiem: 'short'
          }}
          height="auto"
          aspectRatio={1.8}
          eventDisplay="block"
          eventContent={(eventInfo) => {
            return (
              <div className="p-1 overflow-hidden">
                <div className="font-medium text-xs truncate">{eventInfo.event.title}</div>
                {eventInfo.view.type !== 'dayGridMonth' && (
                  <div className="text-xs truncate">{eventInfo.event.extendedProps.program}</div>
                )}
              </div>
            );
          }}
        />
      </Card>
    </PageTemplate>
  );
}