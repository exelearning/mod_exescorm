<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'exescorm', language 'es'
 *
 * @package   mod_exescorm
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['toc'] = 'TOC (Tabla de Contenidos)';
$string['navigation'] = 'Navegación';
$string['aicchacptimeout'] = 'Tiempo de espera AICC HACP';
$string['aicchacptimeout_desc'] = 'Periodo de tiempo en minutos en el que una sesión externa AICC HACP se mantendrá abierta';
$string['aicchacpkeepsessiondata'] = 'Datos de sesión AICC HACP';
$string['aicchacpkeepsessiondata_desc'] = 'Periodo de tiempo en días en el que se mantendrán los datos de la sesión externa AICC HACP (un valor alto llenará la tabla con datos antiguos, pero puede ser útil a la hora de depurar)';
$string['aiccuserid'] = 'AICC pasa el número ID del usuario';
$string['aiccuserid_desc'] = 'El estándar AICC para los nombres de usuario es muy restrictivo en comparación con Moodle, y sólo permite caracteres alfanuméricos, guiones y subrayado. No se permiten puntos, espacios y el símbolo @. Si está activado, los números ID de usuario se pasan al paquete AICC en lugar de los nombres de usuario.';
$string['activation'] = 'Activación';
$string['activityloading'] = 'Usted será automáticamente encaminado a la actividad en';
$string['activityoverview'] = 'Hay paquetes eXeLearning que requieren atención';
$string['activitypleasewait'] = 'Cargando actividad, espere por favor...';
$string['adminsettings'] = 'Configuración de administración';
$string['advanced'] = 'Parámetros';
$string['aliasonly'] = 'Cuando se selecciona un archivo imsmanifest.xml de un repositorio debemos utilizar un alias / atajo para este archivo.';
$string['allowapidebug'] = 'Activar depuración y trazado API (ajustar la máscara de captura con apidebugmask)';
$string['allowtypeexternal'] = 'Habilitar tipo de paquete externo';
$string['allowtypeexternalaicc'] = 'Habilitar URL AICC directa';
$string['allowtypeexternalaicc_desc'] = 'Si se habilita permite una url directa a un paquete simple AICC';
$string['allowtypelocalsync'] = 'Habilitar tipo de paquete descargado';
$string['allowtypeaicchacp'] = 'Habilitar AICC HACP externo';
$string['allowtypeaicchacp_desc'] = 'Si se habilita permite comunicaciones externas AICC HACP sin necesidad de identidicación de usuario para peticiones de un paquete AICC externo';
$string['apidebugmask'] = 'API debug capture mask  - use a simple regex on &lt;username&gt;:&lt;activityname&gt; e.g. admin:.* will debug for admin user only';
$string['areacontent'] = 'Archivos de contenido';
$string['areapackage'] = 'Archivo de paquete';
$string['asset'] = 'Recurso';
$string['assetlaunched'] = 'Recurso - Visto';
$string['attempt'] = 'Intento';
$string['attempts'] = 'Intentos';
$string['attemptstatusall'] = 'Área personal y página de inicio';
$string['attemptstatusmy'] = 'Solo Área personal';
$string['attemptstatusentry'] = 'Solo página de inicio';
$string['attemptsx'] = '{$a} intentos';
$string['attemptsmanagement'] = 'Gestión de intentos';
$string['attempt1'] = '1 intento';
$string['attr_error'] = 'Valor incorrecto para el atributo ({$a->attr}) en la marca {$a->tag}.';
$string['autocommit'] = 'Auto-guardado';
$string['autocommit_help'] = 'Si está habilitado, los datos se guardan automáticamente en la base de datos. Útil para los objetos eXeLearning que no guardan sus datos con regularidad.';
$string['autocommitdesc'] = 'Guardar automáticamente los datos si el paquete no ha sido guardado.';
$string['autocontinue'] = 'Continuación automática';
$string['autocontinue_help'] = 'Si se habilita, los objetos de aprendizaje subsecuentes son iniciados automáticamente sino el botón Continuar debe ser usado.';
$string['autocontinuedesc'] = 'Si se habilita, los objetos de aprendizaje subsecuentes son iniciados automáticamente sino el botón Continuar debe ser usado.';
$string['EXESCORM_AVERAGEATTEMPT'] = 'Intentos promedio';
$string['badmanifest'] = 'Algunos Errores de manifiesto: ver registro de errores';
$string['badimsmanifestlocation'] = 'Se encontró un archivo imsmanifest.xml pero no estaba en la raíz de su archivo zip. Por favor, revise el contenido.';
$string['badarchive'] = 'Debe proporcionar un archivo zip válido';
$string['badexelearningpackage'] = 'El paquete no cumple las normas de los contenidos eXeLearning definidas para el sitio.';
$string['browse'] = 'Vista previa';
$string['browsed'] = 'Navegado';
$string['browsemode'] = 'Modo de presentación preliminar';
$string['browserepository'] = 'Navegar por el repositorio';
$string['calculatedweight'] = 'Peso calculado';
$string['calendarend'] = '{$a} cierre';
$string['calendarstart'] = '{$a} abre';
$string['cannotaccess'] = 'No se puede llamar a este script de esa manera';
$string['cannotfindsco'] = 'No se ha encontrado SCO';
$string['closebeforeopen'] = 'Usted ha especificado una fecha de cierre anterior a la fecha de apertura';
$string['collapsetocwinsize'] = 'Contraer TOC cuando el tamaño de la ventana inferior';
$string['collapsetocwinsizedesc'] = 'Este ajuste permite especificar el tamaño de la ventana inferior con el que el TOC se contrae automáticamente.';
$string['compatibilitysettings'] = 'Configuración de compatibilidad';
$string['completed'] = 'Finalizado';
$string['completiondetail:completionstatuspassed'] = 'Superar la actividad';
$string['completiondetail:completionstatuscompleted'] = 'Completar la actividad';
$string['completiondetail:completionstatuscompletedorpassed'] = 'Completar o superar la actividad';
$string['completiondetail:completionscore'] = 'Obtener una calificación de {$a} o más';
$string['completiondetail:allscos'] = 'Hacer todas las partes de esta actividad';
$string['completionscorerequired'] = 'Requiere puntuación mínima';
$string['completionscorerequireddesc'] = 'Se requiere un puntaje mínimo de {$a} para completarse.';
$string['completionscorerequired_help'] = 'Al habilitar este parámetro se requerirá que el usuario tenga al menos la puntuación mínima registrada para que se marque la actividad eXeLearning como finalizada, así como cualquier otro requerimiento de Finalización de Actividad.';
$string['completionstatus_passed'] = 'Pasado';
$string['completionstatus_completed'] = 'Finalizado';
$string['completionstatusallscos'] = 'Requiere que todos los scos devuelvan el estado de finalización';
$string['completionstatusallscos_help'] = 'Algunos paquetes SCORM contienen múltiples componentes o "scos": cuando está habilitado, todos los "scos" dentro del paquete deben devolver el "estado de la lección" correspondiente para que esta actividad se marque como completada.';
$string['completionstatusrequired'] = 'Se requiere estado';
$string['completionstatusrequireddesc'] = 'El estudiante debe alcanzar al menos uno de los siguientes estados: {$a}';
$string['completionstatusrequired_help'] = 'Al comprobar uno o más estados se requerirá que el alumno cumpla al menos con uno de esos estados para que se marque como finalizada esta  actividad eXeLearning, así como cualquier otro requerimiento de Finalización de Actividad';
$string['confirmloosetracks'] = 'ATENCIÓN: El paquete parece haber sido cambiado o modificado. Si la estructura del paquete se ha cambiado, las pistas de algunos usuarios pueden haberse perdido durante el proceso de actualización.';
$string['contents'] = 'Contenido';
$string['coursepacket'] = 'Paquete de curso';
$string['coursestruct'] = 'Estructura de curso';
$string['crontask'] = 'Procesamiento en segundo plano para SCORM';
$string['currentwindow'] = 'Ventana actual';
$string['datadir'] = 'Error del sistema de archivos: No se puede crear el directorio de datos del curso';
$string['defaultdisplaysettings'] = 'Configuración de pantalla predeterminada';
$string['defaultgradesettings'] = 'Configuración de calificación predeterminada';
$string['defaultothersettings'] = 'Otras configuraciones predeterminadas';
$string['deleteattemptcheck'] = '¿Está totalmente seguro de que quiere eliminar completamente estos intentos?';
$string['deleteallattempts'] = 'Eliminar todos los intentos SCORM';
$string['deleteselected'] = 'Eliminar los intentos seleccionados';
$string['deleteuserattemptcheck'] = '¿Está totalmente seguro de que quiere eliminar completamente sus intentos?';
$string['details'] = 'Detalles del rastreo SCO';
$string['directories'] = 'Mostrar enlaces de directorio';
$string['disabled'] = 'Deshabilitado';
$string['display'] = 'Mostrar paquete';
$string['displayattemptstatus'] = 'Mostrar estado de intentos';
$string['displayattemptstatus_help'] = 'Esta preferencia permite mostrar un resumen de los intentos de los usuarios en el bloque Vista general del curso en Mi Tablero y/o en la página de entrada del eXeLearning.';
$string['displayattemptstatusdesc'] = 'Mostrar un resumen de los intentos del usuario en el bloque de descripción general del curso en el Tablero y / o la página de entrada eXeLearning.';
$string['displaycoursestructure'] = 'Mostrar estructura del curso en la página de entrada';
$string['displaycoursestructure_help'] = 'Si está activado, la tabla de contenidos se mostrará en la página de resumen SCORM.';
$string['displaycoursestructuredesc'] = 'Si está habilitado, la tabla de contenido se muestra en la página de esquema SCORM.';
$string['displaydesc'] = 'Mostrar el contenido en una nueva ventana.';
$string['displaysettings'] = 'Configuración de pantalla.';
$string['dnduploadexescorm'] = 'Añadir un SCORM creado con eXeLearning';
$string['domxml'] = 'Librería externa DOMXML';
$string['editdialogcontent'] = 'Está a punto de editar el contenido en eXeLearning. Cuando termine de editar, volverá automáticamente a Moodle.';
$string['editdialogcontent:caution'] = 'Modificar la estructura del contenido o de las actividades interactivas puede provocar la pérdida de calificaciones o intentos, y causar fallos en el funcionamiento.';
$string['editdialogcontent:continue'] = '¿Desea continuar?';
$string['editonlinebtnlabel'] = 'Editar';
$string['editonlinebtnlabel_help'] = 'Envíe el contenido a eXeLearning para su edición.';
$string['element'] = 'Elemento';
$string['enter'] = 'Entrar';
$string['entercourse'] = 'Introducir el curso eXeLearning';
$string['errorlogs'] = 'Registro de errores';
$string['erroraccessingreport'] = 'Error accediendo al informe';
$string['eventattemptdeleted'] = 'Intento eliminado';
$string['eventinteractionsviewed'] = 'Interacciones visualizadas';
$string['eventreportviewed'] = 'Reporte visualizado';
$string['eventscolaunched'] = 'Sco iniciado';
$string['eventscorerawsubmitted'] = 'Enviada puntuación SCORM';
$string['eventstatussubmitted'] = 'Enviado estatus SCORM';
$string['eventtracksviewed'] = 'Visualizado rastreos';
$string['eventuserreportviewed'] = 'Reporte del usuario visualizado';
$string['everyday'] = 'Todos los días';
$string['everytime'] = 'Cada vez que se use';
$string['exceededmaxattempts'] = 'Ha alcanzado el número máximo de intentos';
$string['exescorm:addinstance'] = 'Añadir un SCORM creado con eXeLearning';
$string['exescormclose'] = 'Disponible para';
$string['exescormcourse'] = 'Curso de aprendizaje';
$string['exescorm:deleteresponses'] = 'Eliminar intentos SCORM';
$string['exescorm:forbiddenfileslist'] = 'Archivos prohibidos Lista RE';
$string['exescorm:forbiddenfileslist_desc'] = 'Aquí puede cofigurar una lista de archivos prohibidos. Introduzca cada archivo prohibido como una expresión regular PHP (RE) en una nueva línea. Por ejemplo:';
$string['exescorm:onlinetypehelp'] = 'Cuando haga clic en cualquiera de los botones de guardar en la parte inferior de esta página, le llevará a eXeLearning para crear o editar el contenido. Cuando termine, eXeLearning lo enviará de vuelta a Moodle.';
$string['exescorm:sendtemplate'] = 'Enviar plantilla';
$string['exescorm:sendtemplate_desc'] = 'Envía la plantilla predeterminada a eXeLearning al crear un nuevo contenido.';
$string['exescorm:mandatoryfileslist'] = ' Ficheros obligatorios Lista RE';
$string['exescorm:mandatoryfileslist_desc'] = 'Aquí se puede cofigurar una lista de archivos obligatorios. Introduzca cada archivo obligatorio como una expresión regular PHP (RE) en una nueva línea.';
$string['exescormloggingoff'] = 'El registro de la API está desactivado';
$string['exescormloggingon'] = 'El registro de la API está activado';
$string['exescormopen'] = 'Disponible en';
$string['exescormresponsedeleted'] = 'Intentos de usuario eliminados';
$string['exescorm:deleteownresponses'] = 'Borrar los propios intentos';
$string['exescorm:savetrack'] = 'Guardar pistas';
$string['exescorm:skipview'] = 'Saltar resumen';
$string['exescorm:template'] = 'Nueva plantilla de paquete.';
$string['exescorm:template_desc'] = 'El elp subido aquí se utilizará como paquete por defecto para los nuevos contenidos. Se mostrará hasta que sea sustituido por el enviado por eXeLearning.';
$string['exescorm:editonlineanddisplay'] = 'Ir a eXeLearning y mostrar';
$string['exescorm:editonlineandreturntocourse'] = 'Ir a eXeLearning y volver al curso';
$string['exescormtype'] = 'Tipo';
$string['exescormtype_help'] = 'Este ajuste determina cómo se incluye el paquete en el curso. Hay 2 opciones:

* Paquete subido - Permite elegir el SCORM creado con eXeLearning por medio del selector de archivos.
* Editar con eXeLearning - Crea la actividad y te lleva a eXeLearning para editar el contenido. Al terminar, eXeLearning lo enviará de vuelta a Moodle.';
$string['exescorm:viewreport'] = 'Ver informes';
$string['exescorm:viewscores'] = 'Ver puntuaciones';
$string['exeonline:connectionsettings'] = 'Configuración de la conexión con eXeLearning';
$string['exeonline:baseuri'] = 'URI remoto';
$string['exeonline:baseuri_desc'] = 'URL de eXeLearning';
$string['exeonline:hmackey1'] = 'Clave de firma';
$string['exeonline:hmackey1_desc'] = 'Clave utilizada para firmar los datos enviados al servidor de eXeLearning, de forma que podamos estar seguros de que se originaron en este servidor. Utilice un máximo de 32 caracteres.';
$string['exeonline:provider_name'] = 'Nombre del proveedor';
$string['exeonline:provider_name_desc'] = 'Nombre del proveedor de eXeLearning. Este se utiliza para identificar el proveedor en la interfaz de eXeLearning.';
$string['exeonline:provider_version'] = 'Versión del proveedor';
$string['exeonline:provider_version_desc'] = 'Versión del proveedor de eXeLearning. Este se utiliza para identificar el proveedor en la interfaz de eXeLearning.';
$string['exeonline:tokenexpiration'] = 'Caducidad del token';
$string['exeonline:tokenexpiration_desc'] = 'Tiempo máximo (en segundos) para editar el paquete en eXeLearning y volver a Moodle.';
$string['exit'] = 'Salir del curso';
$string['exitactivity'] = 'Salir de la actividad';
$string['expired'] = 'Lo sentimos, esta actividad se cerró en {$a} y ya no está disponible';
$string['external'] = 'Actualizar la temporalización de paquetes externos';
$string['failed'] = 'Error';
$string['finishexescorm'] = 'Si ha terminado de ver este recurso, {$a}';
$string['finishexescormlinkname'] = 'haga clic aquí para volver a la página del curso';
$string['firstaccess'] = 'Primer acceso';
$string['firstattempt'] = 'Primer intento';
$string['floating'] = 'Flotante';
$string['forcecompleted'] = 'Forzar finalización';
$string['forcecompleted_help'] = 'Si está habilitado, el estado del intento actual se cambia a "completado". (Sólo se aplica a los paquetes SCORM 1.2.)';
$string['forcecompleteddesc'] = 'Esta preferencia fija el valor por defecto para mostrar el ajuste de forzar completados';
$string['forcenewattempts'] = 'Forzar nuevo intento';
$string['forcenewattempts_help'] = 'Hay 3 opciones:

* No: si un intento anterior se completa, pasa o falla, se le proporcionará al estudiante la opción de ingresar en modo de revisión o comenzar un nuevo intento.
* Cuando el intento anterior se completó, pasó o falló: se basa en el paquete SCORM que establece el estado de \'completado\', \'aprobado\' o \'fallido\'.
* Siempre: cada reingreso a la actividad SCORM generará un nuevo intento y el alumno no regresará al mismo punto al que llegó en su intento anterior.';
$string['forceattemptalways'] = 'Siempre';
$string['forceattemptoncomplete'] = 'Cuando el intento anterior se completó, pasó o falló';
$string['forcejavascript'] = 'Obligar a los usuarios a tener JavaScript habilitado';
$string['forcejavascript_desc'] = 'Si está activado (recomendado) impide el acceso a los contenidos cuando JavaScript no está soportado/activado en el navegador del usuario. Si está desactivado, el usuario puede ver el contenido, pero la comunicación API fallará y no se almacenará la información de la calificación.';
$string['forcejavascriptmessage'] = 'Se requiere JavaScript para visualizar este objeto, por favor, active JavaScript en su navegador y vuelva a intentarlo.';
$string['found'] = 'Encontrado manifiesto';
$string['frameheight'] = 'La altura del marco del escenario o la ventana.';
$string['framewidth'] = 'La anchura del marco del escenario o la ventana.';
$string['fromleft'] = 'Desde la izquierda';
$string['fromtop'] = 'Desde arriba';
$string['fullscreen'] = 'Llenar toda la pantalla';
$string['general'] = 'Datos generales';
$string['gradeaverage'] = 'Calificación promedio';
$string['gradeforattempt'] = 'Calificación del intento';
$string['gradehighest'] = 'Calificación más alta';
$string['grademethod'] = 'Método de calificación';
$string['grademethod_help'] = 'El método de calificación define cómo se determina la calificación de un intento único en una actividad.

Hay 4 métodos de calificación:

* Objetos de aprendizaje - Número de objetos de aprendizaje completados/aprobados
* Calificación más alta: La puntuación máxima obtenida  entre todos los objetos realizados
* Calificación promedio: La media de todas las puntuaciones
* Calificaciones sumadas: La suma de todas las puntuaciones';
$string['grademethoddesc'] = 'El método de calificación define cómo se determina la nota de la actividad para un único intento.';
$string['gradenoun'] = 'Calificación';
$string['gradereported'] = 'Calificación informada';
$string['gradesettings'] = 'Configuración de calificación';
$string['gradescoes'] = 'Objetos de aprendizaje';
$string['gradesum'] = 'Calificaciones sumadas';
$string['height'] = 'Altura';
$string['hidden'] = 'Oculto';
$string['hidebrowse'] = 'Ocultar botón de previsualización';
$string['hidebrowse_help'] = 'El modo de vista previa permite al estudiante explorar una actividad antes de intentarla.';
$string['hidebrowsedesc'] = 'El modo de vista previa permite al estudiante explorar una actividad antes de intentarla.';
$string['hideexit'] = 'Ocultar enlace de salida';
$string['hidereview'] = 'Ocultar botón de revisión';
$string['hidetoc'] = 'Mostrar la estructura del curso en el visor';
$string['hidetoc_help'] = 'Cómo se muestra la tabla de contenidos en el visor';
$string['hidetocdesc'] = 'Este ajuste especifica cómo se muestra la tabla de contenidos en el visor';
$string['EXESCORM_HIGHESTATTEMPT'] = 'Intento más alto';
$string['chooseapacket'] = 'Elegir o actualizar un paquete';
$string['identifier'] = 'Identificador de pregunta';
$string['incomplete'] = 'Incompleto';
$string['indicator:cognitivedepth'] = 'SCORM cognitiva';
$string['indicator:cognitivedepth_help'] = 'Este indicador está basado en la profundidad cognitiva alcanzada por el estudiante en una actividad SCORM.';
$string['indicator:cognitivedepthdef'] = 'SCORM Cognitivo';
$string['indicator:cognitivedepthdef_help'] = 'El participante ha alcanzado este porcentaje del compromiso cognitivo ofrecido por las actividades SCORM durante este intervalo de análisis (Niveles = Sin vista, Vista, Enviar, Ver comentarios)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'SCORM social';
$string['indicator:socialbreadth_help'] = 'Este indicador está basado en la amplitud social alcanzada por el estudiante en una actividad SCORM.';
$string['indicator:socialbreadthdef'] = 'SCORM Social';
$string['indicator:socialbreadthdef_help'] = 'El participante ha alcanzado este porcentaje del compromiso social ofrecido por las actividades SCORM durante este intervalo de análisis (Niveles = Sin participación, Solo participante)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';

$string['interactions'] = 'Interacciones';
$string['masteryoverride'] = 'El puntaje de dominio anula el estado';
$string['masteryoverride_help'] = 'Si está habilitado y se proporciona un puntaje de dominio, cuando se llama a LMSFinish y se ha establecido un puntaje neto, el estado se volverá a calcular utilizando el puntaje neto y el de dominio y se anulará cualquier estado proporcionado por eXeLearning (incluido "incompleto").';
$string['masteryoverridedesc'] = 'Esta preferencia establece el valor por defecto de la nota mínima para aprobar sobrescribiendo el valor establecido.';
$string['myattempts'] = 'Mis intentos';
$string['myaiccsessions'] = 'Mis sesiones AICC';
$string['repositorynotsupported'] = 'Este repositorio no soporta vincularse directamente hacia un fichero imsmanifest.xml';
$string['trackid'] = 'ID';
$string['trackid_help'] = 'Éste es el identificador establecido por tu paquete eXeLearning para esta pregunta,';
$string['trackcorrectcount'] = 'Conteo correcto';
$string['trackcorrectcount_help'] = 'Número de resultados correctos para la pregunta';
$string['trackpattern'] = 'Patrón';
$string['trackpattern_help'] = 'Esta es la respuesta correcta a esta pregunta, no muestra la respuesta de los alumnos.';
$string['tracklatency'] = 'Latencia';
$string['tracklatency_help'] = 'Tiempo transucrrido entre el momento en que la se puso a disposición del alumno la interacción para respoder y el momento de la primera respuesta';
$string['trackresponse'] = 'Respuesta';
$string['trackresponse_help'] = 'Esta es la respuesta dada por el alumno para esta pregunta';
$string['trackresult'] = 'Resultado';
$string['trackresult_help'] = 'Resultado en base a la respuesta del alumno y el resultado correcto';
$string['trackscoremin'] = 'Puntuación mínima';
$string['trackscoremin_help'] = 'Valor mínimo en el rango de posible de puntuaciones';
$string['trackscoremax'] = 'Puntuación máxima';
$string['trackscoremax_help'] = 'Valor máximo en el rango de posible de puntuaciones';
$string['trackscoreraw'] = 'Puntuación bruta';
$string['trackscoreraw_help'] = 'Número que refleja el resultado del alumno en relación con el rango delimitado por los valores de mínimo y máximo';
$string['tracksuspenddata'] = 'Datos de suspensión';
$string['tracksuspenddata_help'] = 'proporciona espacio para almacenar y recuperar datos entre sesiones de aprendizaje';
$string['tracktime'] = 'Hora';
$string['tracktime_help'] = 'Hora en la que se inició el intento';
$string['tracktype'] = 'Tipo';
$string['tracktype_help'] = 'El tipo de pregunta, por ejemplo "selección" o "respuesta corta".';
$string['trackweight'] = 'Peso';
$string['trackweight_help'] = 'Peso asigando al elemento';
$string['invalidactivity'] = 'La actividad SCORM es incorrecta';
$string['invalidmanifestname'] = 'Solo pueden ser seleccionados imsmanifest.xml o ficheros .zip';
$string['invalidstatus'] = 'Estado inválido';
$string['invalidurl'] = 'Se ha especificado una URL no válida';
$string['invalidurlhttpcheck'] = 'Se ha especificado una URL no válida. Mensaje de debug:<pre>{$a->cmsg}</pre>';
$string['invalidhacpsession'] = 'Sesión HACP no válida';
$string['invalidmanifestresource'] = 'ADVERTENCIA: Los siguientes recursos son mencionados en el manifiesto, pero no se puden encontrar';
$string['last'] = 'Último acceso en';
$string['lastaccess'] = 'Último acceso';
$string['lastattempt'] = 'Último intento finalizado';
$string['lastattemptlock'] = 'Bloquear después último intento';
$string['lastattemptlock_help'] = 'Si se activa, al estudiante se le impide el lanzamiento del reproductor después de haber utilizado todos los intentos que tenía asignados.';
$string['lastattemptlockdesc'] = 'Si está habilitado, un estudiante no puede iniciar el reproductor después de usar todos sus intentos asignados.';
$string['location'] = 'Mostrar la barra de ubicación';
$string['max'] = 'Calificación máxima';
$string['maximumattempts'] = 'Número de intentos';
$string['maximumattempts_help'] = 'Este ajuste permite restringir el número de intentos. Sólo es aplicable a los paquetes SCORM 1.2 y AICC.';
$string['maximumattemptsdesc'] = 'Esta preferencia fija el valor por defecto sobre el número máximo de intentos en una actividad';
$string['maximumgradedesc'] = 'Esta preferencia fija el valor por defecto sobre la calificación máxima de una actividad';
$string['menubar'] = 'Mostrar la barra de menú';
$string['min'] = 'Calificación mínima';
$string['minimumscoregreater'] = 'La puntuación mínima debe ser mayor que 0.';
$string['missing_attribute'] = 'Falta atributo ({$a->attr}) en marca {$a->tag}';
$string['missingparam'] = 'Un parámetro requerido falta o es incorrecto';
$string['missing_tag'] = 'Falta marca {$a->tag}';
$string['mode'] = 'Moda';
$string['modulename'] = 'eXeLearning (SCORM)';
$string['modulename_help'] = 'Un SCORM creado con eXeLearning es un conjunto de archivos que se empaquetan conforme a una norma estándar para los objetos de aprendizaje. El módulo de actividad eXeLearning (SCORM) permite crear y editar estos SCORM.

El contenido se muestra normalmente en varias páginas, con navegación entre las páginas. Hay varias opciones para la visualización de los contenidos, con ventanas pop-up, en tablas de contenidos, con botones de navegación, etc. Los contenidos de eXeLearning muchas veces incluyen preguntas calificables, que se registra en el libro de calificaciones.

Las actividades eXeLearning se puede usar

* Para la presentación de contenidos multimedia y animaciones
* Como herramienta de evaluación';
$string['modulename_link'] = 'mod/mod_exescorm/view';
$string['modulenameplural'] = 'Contenidos eXeLearning (SCORM)';
$string['nav'] = 'Mostrar navegación';
$string['nav_help'] = 'Este ajuste especifica si se han de mostrar/ocultar los botones de navegación y su posición.

Hay tres opciones:

* No - No mostrar los botones de navegación
* Bajo el contenido - Mostrar los botones de navegación debajo del contenido del contenido
* Flotantes - Permite especificar manualmente la posición de los botones de navegación desde la izquierda y desde arriba con respecto a la ventana.';
$string['navdesc'] = 'Este ajuste especifica si se han de mostrar/ocultar los botones de navegación y su posición.';
$string['navpositionleft'] = 'Posición de los botones de navegación desde la izquierda en píxeles.';
$string['navpositiontop'] = 'Posición de los botones de navegación desde arriba, en píxeles.';
$string['networkdropped'] = 'El visor de eXeLearning ha determinado que tu conexión a internet es inestable o ha sido interrumpida. Si continuas en esta actividad eXeLearning, tu progreso puede no ser guardado.<br>
Debería cerrar la actividad ahora y volver cuando tenga una conexión a internet estable.';
$string['newattempt'] = 'Comenzar un nuevo intento';
$string['next'] = 'Continuar';
$string['noactivity'] = 'Nada que informar';
$string['noattemptsallowed'] = 'Número de intentos permitidos';
$string['noattemptsmade'] = 'Número de intentos realizados';
$string['no_attributes'] = 'La marca {$a->tag} debe tener atributos';
$string['no_children'] = 'La marca {$a->tag} debe tener hijos';
$string['nolimit'] = 'Intentos ilimitados';
$string['nomanifest'] = 'Archivo incorrecto - falta imsmanifest.xml o estructura AICC';
$string['noprerequisites'] = 'Lo sentimos, pero no posee los pre-requisitos requeridos para acceder a este objeto de aprendizaje';
$string['noreports'] = 'No hay informes que mostrar';
$string['normal'] = 'Normal';
$string['noscriptnoexescorm'] = 'Su navegador no admite javascript, o tiene la opción javascript deshabilitada. Este contenido no puede reproducirse o guardar los datos correctamente.';
$string['notattempted'] = 'No se ha intentado';
$string['not_corr_type'] = 'No concuerda el tipo para la marca {$a->tag}';
$string['notopenyet'] = 'Esta actividad no estará disponible hasta {$a}';
$string['objectives'] = 'Objetivos';
$string['openafterclose'] = 'Ha especificado una fecha de apertura posterior a la fecha de cierre';
$string['optallstudents'] = 'todos los usuarios';
$string['optattemptsonly'] = 'Sólo usuarios con intentos';
$string['optnoattemptsonly'] = 'Sólo usuarios sin intentos';
$string['options'] = 'Opciones (no admitidas por algunos navegadores)';
$string['optionsadv'] = 'Opciones (Avanzadas)';
$string['optionsadv_desc'] = 'Si se selecciona, el ancho y el alto serán listados como opciones avanzadas.';
$string['organization'] = 'Organización';
$string['organizations'] = 'Organizaciones';
$string['othersettings'] = 'Ajustes adicionales';
$string['page-mod-exescorm-x'] = 'Cualquier página del módulo eXeLearning';
$string['pagesize'] = 'Tamaño de la página';
$string['package'] = 'Paquete';
$string['package_help'] = 'El archivo del paquete es un archivo zip que contiene un SCORM generado con eXeLearning.';
$string['packagedir'] = 'Error de sistema: No se puede crear el directorio de paquetes';
$string['packagefile'] = 'No se ha especificado paquete';
$string['packagehdr'] = 'Paquete';
$string['packageurl'] = 'URL';
$string['packageurl_help'] = 'Este parámetro habilita una URL para especificar el contenido eXeLearning en lugar de seleccionar un archivo a través del selector de archivos.';
$string['passed'] = 'Pasado';
$string['php5'] = 'PHP 5 (librería nativa DOMXML)';
$string['player:next'] = 'Siguiente';
$string['player:prev'] = 'Anterior';
$string['player:skipnext'] = 'Siguiente del mismo nivel';
$string['player:skipprev'] = 'Anterior del mismo nivel';
$string['player:toogleFullscreen'] = 'Alternar pantalla completa';
$string['player:up'] = 'Subir nivel';
$string['pluginadministration'] = 'Administración del contenido eXeLearning';
$string['pluginname'] = 'eXeLearning (SCORM)';
$string['popup'] = 'Abrir Objetos de Aprendizaje en una ventana nueva';
$string['popuplaunched'] = 'Este contenido se ha abierto en una nueva ventana. Si has terminado de ver este recurso, haz clic aquí para regresar a la página del curso.';
$string['popupmenu'] = 'En un menú desplegable';
$string['popupopen'] = 'Abrir paquete en una ventana nueva';
$string['popupsblocked'] = 'Parece que las ventanas emergentes están bloqueadas, deteniendo la ejecución de este módulo. Por favor, verifique la configuración del explorado antes de comenzar de nuevo.';
$string['position_error'] = 'La marca {$a->tag} no puede ser un hijo de la marca {$a->parent}';
$string['preferencesuser'] = 'Preferencias para esta exportación';
$string['preferencespage'] = 'Preferencias exclusivas para esta página';
$string['prev'] = 'Anterior';
$string['privacy:metadata:aicc:data'] = 'Datos personales pasados a través del subsistema.';
$string['privacy:metadata:aicc:externalpurpose'] = 'Este complemento envía datos externamente utilizando el protocolo AICC HACP.';
$string['privacy:metadata:aicc_session:lessonstatus'] = 'El estado de la lección a rastrear';
$string['privacy:metadata:aicc_session:exescormmode'] = 'El modo del elemento a rastrear';
$string['privacy:metadata:aicc_session:exescormstatus'] = 'El estado del elemento a rastrear';
$string['privacy:metadata:aicc_session:sessiontime'] = 'El tiempo de sesión a rastrear';
$string['privacy:metadata:aicc_session:timecreated'] = 'La hora en que se creó el elemento rastreado';
$string['privacy:metadata:attempt'] = 'El número de intento';
$string['privacy:metadata:scoes_track:element'] = 'El nombre del elemento a rastrear';
$string['privacy:metadata:scoes_track:value'] = 'El valor del elemento dado';
$string['privacy:metadata:exescorm_aicc_session'] = 'La información de sesión del protocolo AICC HACP';
$string['privacy:metadata:exescorm_scoes_track'] = 'Los datos rastreados de las SCOes pertenecientes a la actividad.';
$string['privacy:metadata:timemodified'] = 'La hora en que el elemento rastreado se modificó por última vez';
$string['privacy:metadata:userid'] = 'El ID del usuario que accedió al contenido eXeLearning.';
$string['protectpackagedownloads'] = 'Descarga de paquete protegido';
$string['protectpackagedownloads_desc'] = 'Si está habilitado, los paquetes eXeLearning pueden ser descargados solo si el usuario tiene asignada capacidad en course:manageactivities. Si está deshabilitado, los paquetes eXeLearning pueden ser siempre descargados.';
$string['raw'] = 'Puntuación bruta';
$string['regular'] = 'Manifiesto regular';
$string['report'] = 'Informe';
$string['reports'] = 'Informes';
$string['reportcountallattempts'] = '{$a->nbattempts} intentos de {$a->nbusers} usuarios, de un total de {$a->nbresults} resultados';
$string['reportcountattempts'] = '{$a->nbresults} resultados ({$a->nbusers} users)';
$string['response'] = 'Respuesta';
$string['result'] = 'Resultado';
$string['results'] = 'Resultados';
$string['review'] = 'Revisión';
$string['reviewmode'] = 'Modo Revisión';
$string['rightanswer'] = 'Respuesta correcta';
$string['exescormstandard'] = 'Modo estándar';
$string['exescormstandarddesc'] = 'Cuando está deshabilitado, Moodle permite que los paquetes SCORM 1.2 almacenen más de lo que permite la especificación, y utiliza la configuración de formato de nombre completo de Moodle cuando pasa el nombre de los usuarios al paquete eXeLearning.';
$string['scoes'] = 'Objetos de aprendizaje';
$string['score'] = 'Puntuación';
$string['scrollbars'] = 'Permitir desplazamiento de la ventana';
$string['search:activity'] = 'Paquete eXeLearning (SCORM) - Información de actividad';
$string['selectall'] = 'Seleccionar todo';
$string['selectnone'] = 'Deseleccionar todo';
$string['show'] = 'Mostrar';
$string['sided'] = 'Lateral';
$string['skipview'] = 'Pasar por alto al estudiante la página de estructura de contenidos';
$string['skipview_help'] = 'Este ajuste especifica si la estructura de la página de contenido debe ser omitida (no se muestra). Si el paquete contiene sólo un objeto de aprendizaje, la página de la estructura del contenido siempre se puede omitir.';
$string['skipviewdesc'] = 'Esta preferencia fija el valor por defecto sobre cuándo pasar por alto la estructura de contenido de una página';
$string['slashargs'] = 'ATENCIÓN: los argumentos \'slash\' están deshabilitados en este sitio y los objetos pueden no funcionar como se espera.';
$string['stagesize'] = 'Tamaño de marco/ventana';
$string['stagesize_help'] = '<p>Estos dos parámetros definen la altura y la anchura del marco o ventana en el que se visualizará el objeto de aprendizaje.</p>';
$string['started'] = 'Comenzado en';
$string['status'] = 'Estatus';
$string['statusbar'] = 'Mostrar la barra de estado';
$string['student_response'] = 'Respuesta';
$string['subplugintype_exescormreport'] = 'Informe';
$string['subplugintype_exescormreport_plural'] = 'Informes';
$string['suspended'] = 'Suspendido';
$string['syntax'] = 'Error de sintaxis';
$string['tag_error'] = 'Marca desconocida ({$a->tag}) con este contenido: {$a->value}';
$string['time'] = 'Hora';
$string['title'] = 'Título';
$string['toolbar'] = 'Mostrar la barra de herramientas';
$string['too_many_attributes'] = 'La marca {$a->tag} tiene demasiados atributos';
$string['too_many_children'] = 'La marca {$a->tag} tiene demasiados hijos';
$string['totaltime'] = 'Hora';
$string['trackingloose'] = 'ATENCIÓN: ¡Los datos de rastreo de este paquete se perderán!';
$string['type'] = 'Tipo';
$string['typeaiccurl'] = 'URL AICC externa';
$string['typeexescormcreate'] = 'Crear con eXeLearning';
$string['typeexescormedit'] = 'Editar con eXeLearning';
$string['typeexternal'] = 'Manifiesto SCORM externo';
$string['typelocal'] = 'Paquete subido';
$string['typelocalsync'] = 'Paquete bajado';
$string['undercontent'] = 'Bajo el contenido';
$string['unziperror'] = 'Ha ocurrido un error durante la descompresión del paquete';
$string['updatefreq'] = 'Actualizar frecuencia automáticamente';
$string['updatefreq_error'] = 'La frecuencia de auto-actualización únicamente puede ser establecida si el paquete está hospedado externamente';
$string['updatefreq_help'] = 'Esto permite descargar y actualizar automáticamente el paquete externo';
$string['updatefreqdesc'] = 'Esta preferencia fija el valor por defecto sobre la frecuencia de actualización automática de una actividad';
$string['validateaexescorm'] = 'Validar un paquete';
$string['validation'] = 'Resultado de la validación';
$string['validationtype'] = 'Esta preferencia ajusta la librería DOMXML usada para validar un Manifiesto SCORM. Si tiene dudas, deje la opción activada.';
$string['value'] = 'Valor';
$string['versionwarning'] = 'La versión del manifiesto es anterior a la 1.3, atención a la marca {$a->tag}';
$string['viewallreports'] = 'Ver los informes de {$a} intentos';
$string['viewalluserreports'] = 'Ver los informes de {$a} usuarios';
$string['whatgrade'] = 'Calificación de intentos';
$string['whatgrade_help'] = 'Si se permiten múltiples intentos, este parámetro especifica si se almacenará en el libro de calificaciones el valor más alto, el promedio (media), el primer o el último intento. La opción de último intento completado no incluye los intentos con un estado \'fallido\'.

Notas sobre la gestión de múltiples intentos:

* La posibilidad de iniciar un nuevo intento se marca en una casilla situada encima del botón Ingresar en la página de estructura del contenido, por lo que debe asegurarse que permite el acceso a esa página si desea permitir más de un intento.
* Algunos paquetes SCORM son inteligentes sobre los nuevos intentos, pero muchos no lo son, lo que significa que si el estudiante vuelve a hacer un intento y el contenido SCORM no tiene la lógica interna para evitar sobrescribir los intentos anteriores, estos se pueden sobrescribir, incluso si el  intento estaba en estado "completado" o "aprobado".
* La configuración de "Forzar completar", "Forzar nuevo intento" y "Bloqueo después del intento final" también mejoran la gestión de múltiples intentos.';
$string['whatgradedesc'] = 'Si en el caso de permitir múltiples intentos se registrará en el libro de calificaciones el intento más alto, el promedio (media), el primero o el último completado.';
$string['width'] = 'Anchura';
$string['window'] = 'Ventana';
$string['youmustselectastatus'] = 'Debe seleccionar un estado que será requerido';

// Deprecated since Moodle 4.0.
$string['info'] = 'Info';
$string['displayactivityname'] = 'Mostrar el nombre de la actividad';
$string['displayactivityname_help'] = 'Mostrar o no mostrar el nombre de la actividad sobre el visor de eXeLearning.';
