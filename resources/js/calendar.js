import { Calendar } from '@fullcalendar/core';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import esLocale from '@fullcalendar/core/locales/es';  // Importa el idioma español desde el paquete principal

document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  if (calendarEl) {
    var resources = window.apartamentos || [];
  
    var formattedResources = resources.map(element => ({
      id: element.id,
      title: element.titulo
    }));
  
    var calendar = new Calendar(calendarEl, {
      plugins: [resourceTimelinePlugin],
      initialView: 'resourceTimelineMonth',
      schedulerLicenseKey: '<YOUR-LICENSE-KEY-GOES-HERE>',
      locale: esLocale,  // Establece el idioma a español
      resources: formattedResources,
    });
  
    calendar.render();
  }
});
