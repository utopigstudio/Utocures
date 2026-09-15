# Revisión del contenido de ayuda

Referencia: `C:/Users/utopig/Desktop/asdasdasd.pdf`, siete páginas y 23 bloques de preguntas. Se ha contrastado el documento completo con el catálogo y con las pantallas, validaciones y exportaciones actuales. El documento es una referencia editorial, no una especificación que demuestre que todas sus funciones existen.

## Organización aplicada

Se conserva el recorrido propuesto: comprender la herramienta, acceder, preparar los datos, planificar, trabajar y revisar resultados. Cada tema ocupa una tarjeta del índice; si contiene varias guías, sus títulos y enlaces se distinguen dentro de ella. No se incorpora buscador.

Cada procedimiento mantiene instrucciones numeradas y un resultado esperado. Las preguntas complementan los pasos, no los sustituyen. Los artículos de consulta sin tareas no muestran una sección de procedimientos vacía. Problemas frecuentes ofrece enlaces en cada respuesta; no obliga a deducir cuál de las guías relacionadas corresponde al problema.

Planificar y asignar se mantienen juntos porque se realizan en el mismo formulario. Consultar, corregir y exportar horas permanecen en la misma guía con acciones separadas, porque comparten pantalla y datos. No es necesario convertir cada bloque del PDF en una ruta nueva ni repetir las instrucciones en varias páginas.

## Cobertura del documento

| Bloque del PDF | Guías principales | Criterio de adaptación |
| --- | --- | --- |
| 1. Primeros pasos | `getting-started` | Perfiles y primera entrada; enlaces de acceso, no contraseñas enviadas por correo. |
| 2. Acceso | `access`, `users`, `client-access` | Entrada común y gestión de cuentas según perfil. |
| 3. Configuración inicial | `admin-start`, `configuration` | Orden de preparación; se explica que no hay importación desde la pantalla. |
| 4. Servicios | `services-characteristics` | Crear, editar y asignar; no se inventa un botón de eliminación. |
| 5. Características | `characteristics` | Describen personas; no filtran automáticamente las asignaciones. |
| 6. Clientes | `clients`, `client-access` | Ficha, servicios, acceso y vínculos a archivos, notas y horarios. |
| 7. Varias sedes | `client-locations` | Guía propia, aun sin soporte de sedes: explica la limitación y evita soluciones falsas. |
| 8. Trabajadoras | `employees` | Alta, cuenta, edición y recuperación del acceso. |
| 9. Disponibilidad | `employee-availability` | Jornadas partidas, días diferentes y diferencia respecto al trabajo asignado. |
| 10. Planificación | `schedules` | Visitas únicas, semanales y quincenales. |
| 11. Asignación | `schedules` | Sustitución de un día y ejemplo de cuatro personas simultáneas. |
| 12. Ausencias | `absences` | Gestión administrativa; no se promete cancelación o reasignación automática. |
| 13. Agenda | `employee-agenda` | Guía propia y acceso contextual desde inicio y calendarios del personal. |
| 14. Fichajes | `employee-work` | Entrada, salida, contador, incidencias y ausencia de una función de firma. |
| 15. Correcciones | `time-records`, `common-problems` | Se distingue no haber iniciado de olvidar finalizar. |
| 16. Incidencias | `notes`, `employee-work` | Notas del cliente compartidas; la incidencia no corrige horas por sí sola. |
| 17. Control de horas | `time-records`, `employee-hours` | Vista administrativa y consulta personal separadas por permisos. |
| 18. Exportación | `time-records` | Procedimiento propio y contenido real de las tres hojas de Excel. |
| 19. Facturación | `invoices`, `automatic-invoices`, `client-locations` | Apoyo a gestión y exportación; sin integración fiscal o contable. |
| 20. Anuncios | `announcements`, `announcement-board` | Publicación administrativa y lectura según perfil. |
| 21. Documentos y notas | `files`, `employee-documents`, `notes` | Diferencia entre archivos, notas de clientes y notas de empleadas. |
| 22. Privacidad | `privacy` | Describe lo visible; no confunde ocultación visual con protección de datos. |
| 23. Problemas frecuentes | `common-problems` | Respuestas concretas enlazadas con la guía correspondiente. |

Se conservan presupuestos, contratos, plantillas, recursos, usuarios, configuración, auditoría, mensajes y uso en móvil aunque el documento no les dedique bloques separados. Los enlaces antiguos siguen siendo válidos.

## Correcciones contrastadas con código

- `EmployeeObserver::created` envía el mensaje inicial; editar una empleada no ejecuta ese evento. Se elimina la instrucción incorrecta de volver a guardar para reenviar invitaciones. Se indica la recuperación desde acceso.
- `ClientStoreRequest` exige CIF/NIF y correo únicos. Cambiar solo el correo no permite duplicar el mismo CIF. La ficha tiene una dirección y el horario no incluye una dirección propia; no se aconseja falsificar identificadores para representar sedes.
- `EmployeeController::workShow` obtiene las notas del cliente completo, no solo las de esa visita. Se elimina la afirmación de que las notas de otras visitas o de administración son privadas.
- Los formularios tienen campos obligatorios. La ayuda ya no aconseja dejarlos vacíos como mecanismo para ocultarlos.
- `EmployeeTimeRecordController::downloadExport` genera tres hojas mediante `EmployeeTimeRecordExport`. Respeta el periodo, pero no aplica la búsqueda por nombre ni limita el archivo a la agrupación visible. Se elimina la promesa de que se exporta exactamente la selección de pantalla.
- Un fichaje abierto debe finalizarlo la trabajadora antes de corregir su salida. Si nunca se inició, la pantalla administrativa no permite crearlo desde cero. Se separan ambos problemas.
- Los menús actuales no tienen una entrada Horarios; la planificación se encuentra dentro de las fichas. El inicio de trabajadoras es `EmployeeDashboard`, con acceso a Agenda.

## Diferencias y límites que requieren trabajo de producto

El PDF pregunta por importación de Excel, sedes, firmas y facturación fiscal integrada. La ayuda explica su ausencia; añadir estas funciones sería otro cambio de producto.

**Privacidad técnica pendiente:** `EmployeeController::workShow`, las agendas y el inicio del personal cargan la relación `client` completa; `Client` no define campos ocultos. Aunque `WorkView.vue` no dibuja el bloque bancario, sus datos pueden formar parte de la respuesta enviada al navegador. La revisión de la ayuda no corrige esa exposición. Se necesita limitar los campos devueltos por el servidor y comprobar los permisos antes de poder asegurar que esos datos son exclusivos de administración.

**Diferencia con el PDF sobre ausencias:** la pantalla actual permite registrar periodos y consultar visitas, pero no ofrece el listado automático de visitas afectadas que el documento describe. La ayuda utiliza el calendario y explica la reasignación manual.

**Importes:** se mantiene la indicación de revisar descuentos y facturas automáticas. Los problemas de cálculo ya documentados en `help-maintenance.md` no se resuelven cambiando textos.

## Validación

Comprobar ambos catálogos, enlaces entre guías y perfiles permitidos, categorías agrupadas, artículos sin procedimientos y enlaces contextuales a disponibilidad y agenda. Ejecutar TypeScript, ESLint y compilación de producción. La revisión de código y del PDF no equivale a una prueba visual de la aplicación en navegador.
