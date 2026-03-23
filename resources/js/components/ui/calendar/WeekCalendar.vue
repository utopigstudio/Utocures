<script setup>
import { ref, computed } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({
  events: { type: Array, default: () => [] },
  eventClick: { type: Function, default: null },
  select: { type: Function, default: null },
  formatCallback: { type: Function },
  mode: { type: String, default: 'compact' },
})

const calendarEvents = computed(() => {
  if (props.formatCallback) {
    return props.formatCallback(props.events)
  }
  return props.events
})

const showHeader = computed(() => props.mode === 'full')

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'timeGridWeek',
  headerToolbar: showHeader.value
    ? {
        left: 'title',
        right: 'prev,next'
      }
    : false,
  slotEventOverlap: true,
  eventMaxStack: 1,
  allDaySlot: false,
  weekends: true,
  events: calendarEvents,
  editable: true,
  selectable: props.select ? true : false,
  firstDay: 1,
  selectOverlap: false,
  height: 400,
  scrollTimeReset: false,
  timeZone: 'local',
  locale: 'es',
  eventContent(arg) {
    return {
      html: `<div class="fc-event-title">${arg.event.title}</div>`
    }
  },
  dayHeaderContent(arg) {
    if (props.mode === 'full') {
      return arg.date.toLocaleDateString('es-ES', {
        weekday: 'short',
        day: 'numeric',
      })
    }
    const dias = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa']
    return dias[arg.date.getDay()]
  },
  selectAllow(arg) {
    const endMinusMs = new Date(arg.end.getTime() - 1)
    return arg.start.toDateString() === endMinusMs.toDateString()
  },
  eventClick(info) {
    const start = info.event.start.toTimeString().substring(0,5)
    const end = info.event.end.toTimeString().substring(0,5)
    const day_of_week = info.event.start.getDay()
    const id = info.event.extendedProps.id
    const extendedProps = info.event.extendedProps

    if (props.eventClick) {
      props.eventClick(start, end, day_of_week, id, extendedProps)
      return
    }
  },
  select(info) {
    if (props.select) {
      const start = info.start.toTimeString().substring(0,5)
      const end = info.end.toTimeString().substring(0,5)
      const day_of_week = info.start.getDay()

      props.select(start, end, day_of_week)
      return
    }
  },
})
</script>

<style scoped>
:deep(.fc .fc-scrollgrid),
:deep(.fc .fc-scrollgrid table),
:deep(.fc .fc-scrollgrid tbody),
:deep(.fc-theme-standard th),
:deep(.fc .fc-scrollgrid td),
:deep(.fc-theme-standard td),
:deep(.fc .fc-scrollgrid tr),
:deep(.fc .fc-timegrid-slot) {
  border: none !important;
}

:deep(.fc .fc-bg-event) {
  opacity: 1 !important;
  cursor: pointer !important;
  border-radius: 4px !important;
}

:deep(.fc .fc-day-today),
:deep(.fc .fc-timegrid-col.fc-day-today),
:deep(.fc .fc-daygrid-day.fc-day-today) {
  background: transparent !important;
}

:deep(.fc-timegrid-slot) {
  height: 30px !important;
  min-height: 30px !important;
  line-height: 30px !important;
}

:deep(td.fc-timegrid-slot.fc-timegrid-slot-lane[data-time$=":00:00"]) {
  border-top: 1px solid #e5e7eb !important;
}
:deep(td.fc-timegrid-slot.fc-timegrid-slot-lane[data-time="00:00:00"]) {
  border-top: none !important;
}

:deep(.fc-timegrid-col) {
  position: relative;
}
:deep(.fc-timegrid-col:not(:first-child))::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 1px;
  height: 100%;
  background-color: #e5e7eb;
  pointer-events: none;
}

:deep(.fc-timegrid-more-link) {
  background-color: #ebebeb;
  border: 2px solid #ccc;
}

:deep(.fc-timegrid-axis),
:deep(.fc-timegrid-axis td),
:deep(.fc-timegrid-axis .fc-timegrid-slot),
:deep(.fc-timegrid-axis-frame) {
  border: none !important;
}

:deep(.fc .fc-col-header-cell) {
  border: none !important;
  font-weight: normal !important;
}

:deep(.fc-scrollgrid-sync-inner) {
  border-left: 1px solid #e5e7eb !important;
  height: 60px !important;
  line-height: 60px !important;
}

:deep(.fc .fc-col-header-cell-cushion) {
  padding: 0 !important;
}

:deep(.fc .fc-event),
:deep(.fc-timegrid-more-link) {
  margin: 4px !important;
}

:deep(.fc-direction-ltr .fc-timegrid-col-events) {
  margin: 0 !important;
}

:deep(.fc-day-today.fc-popover) {
  z-index: 1 !important;
  border-radius: 4px !important;
  background: #fff !important;
}
</style>

<template>
  <div class="bg-white p-4 overflow-x-auto" scroll-region>
    <div class="min-w-[940px]">
      <FullCalendar :options="calendarOptions" />
    </div>
    <slot />
  </div>
</template>