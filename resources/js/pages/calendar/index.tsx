import React, { useState } from 'react';
import { PageTemplate } from '@/components/page-template';
import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { useTranslation } from 'react-i18next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';

interface CalendarEvent {
  id: number;
  title: string;
  start: string | Date;
  end: string | Date;
  type: 'meeting' | 'holiday' | 'leave';
  allDay?: boolean;
  color: string;
  status?: string;
}

interface CalendarProps {
  events: CalendarEvent[];
  canManage: boolean;
}

export default function CalendarIndex({ events, canManage }: CalendarProps) {
  const { t } = useTranslation();
  const [selectedEvent, setSelectedEvent] = useState<CalendarEvent | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const breadcrumbs = [
    { title: t('Dashboard'), href: route('dashboard') },
    { title: t('Calendar') }
  ];

  const pageActions = [];

  const handleEventClick = (clickInfo: any) => {
    const event = events.find(e => e.id === parseInt(clickInfo.event.id));
    if (event) {
      setSelectedEvent(event);
      setIsDialogOpen(true);
    }
  };

  return (
    <PageTemplate
      title={t('Calendar')}
      url="/calendar"
      breadcrumbs={breadcrumbs}
      actions={pageActions}
    >
      <div className="bg-white rounded-lg shadow p-6">
        <div className="mb-4 flex justify-between items-center">
          <div className="flex gap-2">
            <div className="flex items-center gap-2 text-sm">
              <div className="w-3 h-3 bg-blue-500 rounded"></div>
              <span>{t('Meetings')}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <div className="w-3 h-3 bg-green-500 rounded"></div>
              <span>{t('Holidays')}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <div className="w-3 h-3 bg-yellow-500 rounded"></div>
              <span>{t('Leaves')}</span>
            </div>
          </div>
        </div>
        
        <div style={{ height: '600px' }}>
          <FullCalendar
            plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            headerToolbar={{
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,timeGridDay'
            }}
            events={events}
            height="100%"
            editable={canManage}
            selectable={canManage}
            selectMirror={true}
            dayMaxEvents={true}
            weekends={true}
            eventDisplay="block"
            eventBackgroundColor=""
            eventBorderColor=""
            eventTextColor="white"
            eventClick={handleEventClick}
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
              <Badge variant="outline" className={`
                ${selectedEvent?.type === 'meeting' ? 'bg-blue-50 text-blue-700' : ''}
                ${selectedEvent?.type === 'holiday' ? 'bg-green-50 text-green-700' : ''}
                ${selectedEvent?.type === 'leave' ? 'bg-yellow-50 text-yellow-700' : ''}
              `}>
                {selectedEvent?.type === 'meeting' && t('Meeting')}
                {selectedEvent?.type === 'holiday' && t('Holiday')}
                {selectedEvent?.type === 'leave' && t('Leave')}
              </Badge>
              {selectedEvent?.status && (
                <Badge variant="secondary">{selectedEvent.status}</Badge>
              )}
            </div>
            <div>
              <p className="text-sm text-muted-foreground">{t('Start Date')}</p>
              <p className="font-medium">
                {selectedEvent?.start ? new Date(selectedEvent.start).toLocaleString() : ''}
              </p>
            </div>
            <div>
              <p className="text-sm text-muted-foreground">{t('End Date')}</p>
              <p className="font-medium">
                {selectedEvent?.end ? new Date(selectedEvent.end).toLocaleString() : ''}
              </p>
            </div>
            {selectedEvent?.allDay && (
              <div>
                <Badge variant="outline">{t('All Day Event')}</Badge>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </PageTemplate>
  );
}