# Centro de ayuda

La ayuda se abre en `/help` y cada guía tiene una dirección estable: `/help/{id}`.
La cabecera abre la guía de la pantalla actual en una ventana, sin abandonar formularios; el menú administrativo incluye el centro completo. Los apartados de horarios, acceso, vacaciones, archivos, notas, servicios y facturación tienen accesos específicos.

`HelpButton.vue` carga el artículo al abrir mediante `/help-content/{id}` (autenticado, filtrado por perfil e idioma y sin caché compartida). Ofrece reintento, cierre con retorno de foco y guía completa en otra pestaña. El atributo opcional `section` selecciona inicialmente pasos, efectos o preguntas; la persona puede cambiar de sección dentro de la ventana. `CardTitle` admite `help-article`, `help-section` y `help-label` para incorporar el mismo botón a nuevos apartados. Los botones son de tipo `button`: abrir ayuda no envía formularios. El contenido se reutiliza del catálogo, sin duplicar textos.

## Contenido y mantenimiento

- `resources/help/es.json` y `resources/help/ca.json`: 37 guías organizadas por recorrido de uso. El orden del catálogo fija el orden de temas; las guías que comparten categoría aparecen dentro de la misma tarjeta. Se incluye `client-locations` para explicar el caso de varias sedes y sus limitaciones.
- `steps` explica cómo llegar a la pantalla; `tasks` contiene acciones independientes con `title`, `steps` y `result`. Cada acción comienza su numeración en 1 y explica el resultado esperado. `effects`, `questions` y `related` mantienen las consecuencias adicionales, dudas y enlaces. Cada pregunta admite `guide` como enlace a su explicación completa; el destino debe estar permitido para todos los perfiles del artículo.
- `resources/js/components/help/HelpSteps.vue`: muestra los pasos y permite saltar directamente a una acción. Lo comparten la guía completa y la ayuda contextual.
- `app/Support/HelpCatalog.php`: selecciona el idioma y filtra los artículos por perfil antes de enviarlos al navegador.
- `app/Http/Controllers/HelpController.php`: exige un artículo existente y permitido para el perfil.
- `resources/js/pages/help/Index.vue`: índice de secciones (Login, Clientes, Empleados, etc.), guías agrupadas bajo cada sección y preguntas desplegables. El centro no incluye búsqueda de texto.
- `resources/js/lib/help.ts`: relación entre pantallas y guías contextuales.
- `lang/{es,ca}/help.php`: etiquetas de interfaz.

Al cambiar una funcionalidad, revisar ambas traducciones y conservar los identificadores para no romper enlaces. Los enlaces relacionados se muestran solo si el perfil puede abrirlos. No añadir instrucciones sobre una acción basándose únicamente en su ruta: comprobar también el botón o formulario que la ofrece y sus efectos en controladores, modelos, observadores y tareas automáticas.

Redactar para personas sin experiencia: indicar primero el apartado del menú, después el nombre literal del botón y su posición, los campos que hay que rellenar, cómo guardar y cómo comprobar el resultado. Separar crear, editar, eliminar, descargar y enviar. Evitar vocabulario interno de programación. `characteristics` y `budget-templates` tienen guía propia; `announcement-board` explica la lectura a clientes y personal, mientras `announcements` explica la publicación a administración.

## Cobertura revisada

| Ámbito | Código contrastado |
| --- | --- |
| Acceso, idioma, instalación y conexión | Controladores Auth, UserLocaleController, User, UserMenuContent, PwaInstallPrompt, OfflineGuard, TimerCounter |
| Inicio por perfil | DashboardController y las tres pantallas de inicio |
| Clientes y acceso | ClientController, ClientAccessController, ClientObserver, formularios y ClientAccessCard |
| Personal, disponibilidad y estados | EmployeeController, EmployeeObserver, EmployeeStatusPeriodService y formularios |
| Series y visitas | AssignedHoursSchedulingService, AssignedHourService, AssignedHoursTemplateObserver, AssignedHoursTemplate y GenerateAssignedHours |
| Servicios y características | Controladores, formularios y relaciones de las opciones |
| Presupuestos, contratos y plantillas | Controladores, formularios, vistas, observadores y envío de PDF |
| Facturas manuales y automáticas | InvoiceController, InvoiceObserver, InvoiceLineObserver y GenerateAutomaticInvoices |
| Archivos individuales y colectivos | FileController, EmployeeDocumentController y sus validaciones |
| Fichajes, ubicación e incidencias | EmployeeController, EmployeeTimeRecordController, TimerCounter, WorkView, HoursWorked, Notes y NoteObserver |
| Comunicaciones | AnnouncementController, Announcement::visibleTo, MessageController, NavNotifications y tareas de avisos |
| Recursos, usuarios, configuración y auditoría | Controladores, formularios y vistas correspondientes |
| Disponibilidad (nuevo artículo `employee-availability`) | ModalHours.vue, EmployeeController::storeAssignedHour/editAssignedHour/destroyAssignedHour |
| Agenda de la trabajadora (nuevo artículo `employee-agenda`) | EmployeeController::schedule/scheduleMonth/workShow |
| Privacidad (nuevo artículo `privacy`) | Client (sin `$hidden`), EmployeeController::schedule(), WorkView.vue (campos realmente pintados) |
| Problemas frecuentes (nuevo artículo `common-problems`) | Recopila preguntas ya verificadas en el resto de artículos; no añade comportamiento nuevo |

Referencia de organización: `project_escola_freta/app/Support/Help/HelpRegistry.php` y su vista `help-center.blade.php`. Se adapta el catálogo por temas y artículos a Vue/Inertia; no se incorpora Filament.

## Revisión frente al documento de referencia

La comparación con `asdasdasd.pdf` y la correspondencia de sus 23 bloques se documentan en [help-content-review.md](help-content-review.md). Se mantienen las preguntas sobre funciones ausentes para explicar sus límites, sin inventar acciones ni omitir dudas reales. No se atribuyen acuerdos o exclusiones al documento que no figuran en él.

## Comportamientos existentes que necesitan revisión aparte

Esta entrega documenta el funcionamiento observado; no cambia los cálculos ni los procesos de negocio.

1. **Descuento manual:** los formularios de presupuestos y facturas calculan la previsualización como porcentaje; `BudgetLineObserver` e `InvoiceLineObserver` descuentan el valor recibido como importe. La ayuda pide comprobar los importes guardados antes de utilizar o enviar el documento.
2. **Facturación automática:** la tarea del día 1 consulta el mes en curso, no el anterior. En `GenerateAutomaticInvoices`, el precio de línea ya se multiplica por las horas y el observador vuelve a multiplicarlo por la cantidad. Se aplica IVA fijo del 21 %, no se usa la casilla `tax_included`, se incluyen registros sin filtrar explícitamente los abiertos y no hay comprobación específica de factura existente para el mismo periodo. Se explica el alcance de la casilla y la necesidad de revisión; no se presenta como una facturación mensual validada.
3. **Historial del personal:** `HoursWorked.vue` ofrece controles de periodo/orden, pero `EmployeeController::hoursWorked()` devuelve el historial sin aplicar esos parámetros. No se promete en la ayuda que filtren el resultado. Esta pantalla solo consulta incidencias; no permite crearlas.
4. **Incidencia después de iniciar:** `WorkView` recibe el identificador del fichaje activo del servidor. Tras iniciar, puede ser necesario volver a abrir la visita para disponer de él; la guía explica ese paso.
5. **Ausencias:** no cancelan ni reasignan visitas; sí excluyen de los avisos de falta de fichaje las entradas cubiertas por el periodo.
6. **Lecturas compartidas:** los avisos de administración y la lectura del lado administrativo de una conversación no se separan por administrador.
7. **Fichajes abiertos:** administración no puede introducir la salida de un fichaje abierto. La empleada debe finalizarlo desde su contador; después administración puede corregir la salida real indicando el motivo. La ayuda refleja la validación de `EmployeeTimeRecordUpdateRequest` y los campos deshabilitados de `CalendarView`.
8. **Selector de trabajadoras al programar:** `Api/EmployeeController::index` (usado por el formulario de horario) no descarta a una empleada con vacaciones o baja registrada en esas fechas; la incluye igualmente con `unavailability_reason` para que administración decida. Las características (`AssignedCharacteristic`) no se leen en ningún punto de `AssignedHoursSchedulingService` ni de este selector: son solo descriptivas.
9. **Datos de cliente en la agenda de la trabajadora:** `Client` no define `$hidden` y `EmployeeController::schedule()`/`scheduleMonth()` cargan la relación `client` completa. `WorkView.vue` pinta nombre, fecha de nacimiento, género, correo, teléfonos, dirección, ciudad, código postal y país; no pinta datos de facturación. No existe hoy una forma de ocultar un campo concreto solo al personal. Ocultar un bloque en Vue no impide que sus datos viajen en la respuesta: véase la revisión técnica pendiente en `help-content-review.md`.

## Comprobaciones

Al validar el catálogo, comprobar identificadores únicos e iguales en ambos idiomas, pasos y resultados no vacíos en cada tarea (las guías de consulta pueden no tener tareas), relaciones existentes y correspondencia entre las guías contextuales y los perfiles permitidos.

`npm run build`, `npx vue-tsc --noEmit` y ESLint sobre los archivos nuevos verifican compilación y tipos. La revisión visual en navegador depende de disponer de un navegador conectado; no se debe dar por realizada si la herramienta de navegador devuelve una lista vacía.
