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
 * Strings for component 'exescorm', language 'gl'
 *
 * @package   mod_exescorm
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['toc'] = 'TOC (Táboa de Contidos)';
$string['navigation'] = 'Navegación';
$string['aicchacptimeout'] = 'Tempo de espera AICC HACP';
$string['aicchacptimeout_desc'] = 'Período de tempo en minutos no que unha sesión externa AICC HACP se manterá aberta';
$string['aicchacpkeepsessiondata'] = 'Datos de sesión AICC HACP';
$string['aicchacpkeepsessiondata_desc'] = 'Período de tempo en días no que se manterán os datos da sesión externa AICC HACP (un valor alto encherá a táboa con datos antigos, pero pode ser útil á hora de depurar)';
$string['aiccuserid'] = 'AICC pasa o número ID da persoas usuaria';
$string['aiccuserid_desc'] = 'O estándar AICC para os nomes de usuaria/o é moi restritivo en comparación con Moodle, e só permite caracteres alfanuméricos, guións e subliñado. Non se permiten puntos, espazos e o símbolo @. Se está activado, os números ID de usuaria/o pásanse ao paquete AICC en lugar dos nomes de usuario.';
$string['activation'] = 'Activación';
$string['activityloading'] = 'Vai ser automaticamente encamiñado á actividade en';
$string['activityoverview'] = 'Hai paquetes eXeLearning que requiren atención';
$string['activitypleasewait'] = 'Cargando actividade, agarde...';
$string['adminsettings'] = 'Configuración de administración';
$string['advanced'] = 'Parámetros';
$string['aliasonly'] = 'Cando se selecciona un ficheiro imsmanifest.xml dun repositorio debemos utilizar un alias / atallo para este ficheiro.';
$string['allowapidebug'] = 'Activar depuración e trazado API (axustar a máscara de captura con apidebugmask)';
$string['allowtypeexternal'] = 'Activar tipo de paquete externo';
$string['allowtypeexternalaicc'] = 'Activar URL AICC directa';
$string['allowtypeexternalaicc_desc'] = 'Se se activa permite un URL directo a un paquete simple AICC';
$string['allowtypelocalsync'] = 'Activar tipo de paquete descargado';
$string['allowtypeaicchacp'] = 'Activar AICC HACP externo';
$string['allowtypeaicchacp_desc'] = 'Se se activa permite comunicacións externas AICC HACP sen necesidade de identificación de persoa usuaria para peticións dun paquete AICC externo';
$string['apidebugmask'] = 'Máscara de captura de depuración API (regex simple en &lt;username&gt;:&lt;activityname&gt;)';
$string['areacontent'] = 'Ficheiros de contido';
$string['areapackage'] = 'Ficheiro de paquete';
$string['asset'] = 'Recurso';
$string['assetlaunched'] = 'Recurso - Visto';
$string['attempt'] = 'Intento';
$string['attempts'] = 'Intentos';
$string['attemptstatusall'] = 'Área persoal e páxina de inicio';
$string['attemptstatusmy'] = 'Só Área persoal';
$string['attemptstatusentry'] = 'Só páxina de inicio';
$string['attemptsx'] = '{$a} intentos';
$string['attemptsmanagement'] = 'Xestión de intentos';
$string['attempt1'] = '1 intento';
$string['attr_error'] = 'Valor incorrecto para o atributo ({$a->attr}) na marca {$a->tag}.';
$string['autocommit'] = 'Auto-gardado';
$string['autocommit_help'] = 'Se está activado, os datos gárdanse automaticamente na base de datos. Útil para os obxectos eXeLearning que non gardan os seus datos con regularidade.';
$string['autocommitdesc'] = 'Gardar automaticamente os datos se o paquete non foi gardado.';
$string['autocontinue'] = 'Continuación automática';
$string['autocontinue_help'] = 'Se se activa, os obxectos de aprendizaxe subsecuentes son iniciados automaticamente senón o botón Continuar debe ser usado.';
$string['autocontinuedesc'] = 'Se se activa, os obxectos de aprendizaxe subsecuentes son iniciados automaticamente senón o botón Continuar debe ser usado.';
$string['EXESCORM_AVERAGEATTEMPT'] = 'Media de intentos';
$string['badmanifest'] = 'Algúns Erros de manifesto: ver rexistro de erros';
$string['badimsmanifestlocation'] = 'Atopouse un ficheiro imsmanifest.xml pero non estaba na raíz do seu ficheiro zip. Revise o contido.';
$string['badarchive'] = 'Debe proporcionar un ficheiro zip válido';
$string['badexelarningpackage'] = 'O paquete non cumpre as normas dos contidos eXeLearning definidas para o sitio.';
$string['browse'] = 'Vista previa';
$string['browsed'] = 'Navegado';
$string['browsemode'] = 'Modo de presentación preliminar';
$string['browserepository'] = 'Navegar polo repositorio';
$string['calculatedweight'] = 'Peso calculado';
$string['calendarend'] = '{$a} peche';
$string['calendarstart'] = '{$a} abre';
$string['cannotaccess'] = 'Non se pode chamar a este script dese xeito';
$string['cannotfindsco'] = 'Non se atopou SCO';
$string['closebeforeopen'] = 'Especificou unha data de peche anterior á data de apertura';
$string['collapsetocwinsize'] = 'Contraer TOC cando o tamaño da xanela inferior';
$string['collapsetocwinsizedesc'] = 'Este axuste permite especificar o tamaño da xanela inferior co que o TOC se contrae automaticamente.';
$string['compatibilitysettings'] = 'Configuración de compatibilidade';
$string['completed'] = 'Finalizado';
$string['completiondetail:completionstatuspassed'] = 'Superar a actividade';
$string['completiondetail:completionstatuscompleted'] = 'Completar a actividade';
$string['completiondetail:completionstatuscompletedorpassed'] = 'Completar ou superar a actividade';
$string['completiondetail:completionscore'] = 'Obter unha cualificación de {$a} ou máis';
$string['completiondetail:allscos'] = 'Facer tódalas partes desta actividade';
$string['completionscorerequired'] = 'Require puntuación mínima';
$string['completionscorerequireddesc'] = 'Requírese unha puntuación mínima de {$a} para completarse.';
$string['completionscorerequired_help'] = 'Ao activar este parámetro requirirase que a persoa usuaria teña cando menos a puntuación mínima rexistrada para que se marque a actividade eXeLearning como finalizada, así como calquera outro requirimento de Finalización de Actividade.';
$string['completionstatus_passed'] = 'Pasado';
$string['completionstatus_completed'] = 'Finalizado';
$string['completionstatusallscos'] = 'Require que tódolos SCO devolvan o estado de finalización';
$string['completionstatusallscos_help'] = 'Algúns paquetes SCORM conteñen múltiples compoñentes ou "SCO": cando está activado, tódolos "SCO" dentro do paquete deben devolver o "estado da lección" correspondente para que esta actividade se marque como completada.';
$string['completionstatusrequired'] = 'Requírese estado';
$string['completionstatusrequireddesc'] = 'O alumnado debe acadar ao menos un dos seguintes estados: {$a}';
$string['completionstatusrequired_help'] = 'Ao comprobar un ou máis estados requirirase que o alumnado cumpra cando menos cun deses estados para que se marque como finalizada esta  actividade eXeLearning, así como calquera outro requirimento de Finalización de Actividade';
$string['confirmloosetracks'] = 'ATENCIÓN: O paquete parece ter sido cambiado ou modificado. Se a estrutura do paquete cambiou, as pistas dalgunhas persoas usuarias poden terse perdido durante o proceso de actualización.';
$string['contents'] = 'Contido';
$string['coursepacket'] = 'Paquete de curso';
$string['coursestruct'] = 'Estrutura de curso';
$string['crontask'] = 'Procesamento en segundo plano para SCORM';
$string['currentwindow'] = 'Xanela actual';
$string['datadir'] = 'Erro do sistema de ficheiros: non se pode crear o directorio de datos do curso';
$string['defaultdisplaysettings'] = 'Configuración de pantalla predeterminada';
$string['defaultgradesettings'] = 'Configuración de cualificación predeterminada';
$string['defaultothersettings'] = 'Outras configuracións predeterminadas';
$string['deleteattemptcheck'] = 'Está totalmente seguro que quere eliminar completamente estes intentos?';
$string['deleteallattempts'] = 'Eliminar tódolos intentos SCORM';
$string['deleteselected'] = 'Eliminar os intentos seleccionados';
$string['deleteattemptcheck'] = 'Está totalmente seguro de que quere eliminar completamente estes intentos?';
$string['details'] = 'Detalles do rastrexo SCO';
$string['directories'] = 'Amosar ligazóns de directorio';
$string['disabled'] = 'Desactivado';
$string['display'] = 'Amosar paquete';
$string['displayattemptstatus'] = 'Amosar estado de intentos';
$string['displayattemptstatus_help'] = 'Esta preferencia permite amosar un resumen dos intentos das persoas usuarias no bloque Vista xeral do curso no Meu Taboleiro e/ou na páxina de entrada do eXeLearning.';
$string['displayattemptstatusdesc'] = 'Amosar un resumo dos intentos da persoa usuaria no bloque de descrición xeral do curso no Taboleiro e/ou a páxina de entrada eXeLearning.';
$string['displaycoursestructure'] = 'Amosar estrutura do curso na páxina de entrada';
$string['displaycoursestructure_help'] = 'Se está activado, a táboa de contidos amosarase na páxina de resumo SCORM.';
$string['displaycoursestructuredesc'] = 'Se está activado, a táboa de contido amósase na páxina de esquema SCORM.';
$string['displaydesc'] = 'Amosar o contido nunha nova xanela.';
$string['displaysettings'] = 'Configuración de pantalla.';
$string['dnduploadexescorm'] = 'Engadir un SCORM creado con eXeLearning';
$string['domxml'] = 'Librería externa DOMXML';
$string['editonlinebtnlabel'] = 'Editar';
$string['editonlinebtnlabel_help'] = 'Envíe o contido a eXeLearning para a súa edición.';
$string['element'] = 'Elemento';
$string['enter'] = 'Entrar';
$string['entercourse'] = 'Introducir o curso eXeLearning';
$string['errorlogs'] = 'Rexistro de erros';
$string['eventattemptdeleted'] = 'Intento eliminado';
$string['eventinteractionsviewed'] = 'Interaccións visualizadas';
$string['eventreportviewed'] = 'Reporte visualizado';
$string['eventscolaunched'] = 'SCO iniciado';
$string['eventscorerawsubmitted'] = 'Enviada puntuación SCORM';
$string['eventstatussubmitted'] = 'Enviado status SCORM';
$string['eventtracksviewed'] = 'Rastrexos visualizados';
$string['eventuserreportviewed'] = 'Reporte da persoa usuaria visualizada';
$string['everyday'] = 'Tódolos días';
$string['everytime'] = 'Cada vez que se use';
$string['exceededmaxattempts'] = 'Acadou o número máximo de intentos';
$string['exescorm:addinstance'] = 'Engadir un SCORM creado con eXeLearning';
$string['exescormclose'] = 'Dispoñible para';
$string['exescormcourse'] = 'Curso de aprendizaxe';
$string['exescorm:deleteresponses'] = 'Eliminar intentos SCORM';
$string['exescorm:forbiddenfileslist'] = 'Ficheiros prohibidos Listaxe RE';
$string['exescorm:forbiddenfileslist_desc'] = 'Aquí pode configurar unha listaxe de ficheiros prohibidos. Introduza cada ficheiro prohibido como unha expresión regular PHP (RE) nunha nova liña. Por exemplo:';
$string['exescorm:onlinetypehelp'] = 'Cando faga clic en calquera dos botóns de gardar na parte inferior desta páxina, accederá a eXeLearning para crear ou editar o contido. Cando remate, eXeLearning enviarao de volta a Moodle.';
$string['exescorm:sendtemplate'] = 'Enviar modelo';
$string['exescorm:sendtemplate_desc'] = 'Envía o modelo predeterminado a eXeLearning ao crear un novo contido.';
$string['exescorm:mandatoryfileslist'] = ' Ficheiros obrigatorios Listaxe RE';
$string['exescorm:mandatoryfileslist_desc'] = 'Aquí pódese configurar unha listaxe de ficheiros obrigatorios. Introduza cada ficheiro obrigatorio como unha expresión regular PHP (RE) nunha nova liña.';
$string['exescormloggingoff'] = 'O rexistro da API está desactivado';
$string['exescormloggingon'] = 'O rexistro da API está activado';
$string['exescormopen'] = 'Dispoñible en';
$string['exescormresponsedeleted'] = 'Intentos de usuario/a eliminados';
$string['exescorm:deleteownresponses'] = 'Borrar os propios intentos';
$string['exescorm:savetrack'] = 'Gardar pistas';
$string['exescorm:skipview'] = 'Saltar resumo';
$string['exescorm:template'] = 'Novo modelo de paquete.';
$string['exescorm:template_desc'] = 'O elp subido aquí utilizarase como paquete por defecto para os novos contidos. Amosarase ata que sexa substituído polo enviado por eXeLearning.';
$string['exescormtype'] = 'Tipo';
$string['exescormtype_help'] = 'Este axuste determina como se inclúe o paquete no curso. Hai 4 opcións:

* Paquete subido - Permite elixir o SCORM creado con eXeLearning por medio do selector de ficheiros.
* Crear con eXeLearning - Crea a actividade e lévate a eXeLearning para editar o contido. Ao rematar, eXeLearning enviarao de volta a Moodle.
* Manifesto SCORM externo - Posibilita especificar un URL imsmanifest.xml. NOTA: Se o URL ten un nome de dominio distinto ao do seu sitio, a mellor opción é "Paquete descargado", dado que noutro caso as cualificacións non se gardarán.
* Paquete descargado - Posibilita especificar un URL do paquete. O paquete será descomprimido e gardado localmente, e actualizado cando se actualice o paquete eXeLearning externo.
* URL AICC externo - este URL é o URL de inicio dunha actividade AICC única. Construirase un pseudopaquete arredor da mesma.';
$string['exescorm:viewreport'] = 'Ver informes';
$string['exescorm:viewscores'] = 'Ver puntuacións';
$string['exeonline:connectionsettings'] = 'Configuración da conexión con eXeLearning';
$string['exeonline:baseuri'] = 'URI remoto';
$string['exeonline:baseuri_desc'] = 'URL de eXeLearning';
$string['exeonline:hmackey1'] = 'Clave de sinatura';
$string['exeonline:hmackey1_desc'] = 'Clave utilizada para asinar os datos enviados ao servidor de eXeLearning, de xeito que poidamos estar seguros de que se orixinaron neste servidor. Utilice un máximo de 32 caracteres.';
$string['exeonline:tokenexpiration'] = 'Caducidade do token';
$string['exeonline:tokenexpiration_desc'] = 'Tempo máximo (en segundos) para editar o paquete en eXeLearning e volver a Moodle.';
$string['exit'] = 'Saír do curso';
$string['exitactivity'] = 'Saír da actividade';
$string['expired'] = 'Sentímolo, esta actividade pechouse en {$a} e xa non está dispoñible';
$string['external'] = 'Actualizar a temporalización de paquetes externos';
$string['failed'] = 'Erro';
$string['finishexescorm'] = 'Se rematou de ver este recurso, {$a}';
$string['finishexescormlinkname'] = 'faga clic aquí para volver á páxina do curso';
$string['firstaccess'] = 'Primeiro acceso';
$string['firstattempt'] = 'Primeiro intento';
$string['floating'] = 'Flotante';
$string['forcecompleted'] = 'Forzar finalización';
$string['forcecompleted_help'] = 'Se está activado, o estado do intento actual cámbiase a "completado". (Só se aplica aos paquetes SCORM 1.2.)';
$string['forcecompleteddesc'] = 'Esta preferencia fixa o valor por defecto para amosar o axuste de forzar completados';
$string['forcenewattempts'] = 'Forzar novo intento';
$string['forcenewattempts_help'] = 'Hai 3 opcións:

* Non: se un intento anterior se completa, pasa ou falla, proporcionaráselle ao alumnado a opción de ingresar en modo de revisión ou comenzar un novo intento.
* Cando o intento anterior se completou, pasou ou fallou: baséase no paquete SCORM que establece o estado de \'completado\', \'aprobado\' ou \'errado\'.
* Sempre: cada reingreso á actividade SCORM xerará un novo intento e o alumnado non regresará ao mesmo punto ao que chegou no seu intento anterior.';
$string['forceattemptalways'] = 'Sempre';
$string['forceattemptoncomplete'] = 'Cando o intento anterior se completou, pasou ou fallou';
$string['forcejavascript'] = 'Obrigar as persoas usuarias a ter JavaScript activado';
$string['forcejavascript_desc'] = 'Se está activado (recomendado) impide o acceso aos contidos cando JavaScript non é compatible co navegador da persoa usuaria ou non está activado. Se está desactivado, a persoa usuaria pode ver o contido, pero a comunicación API fallará e non se almacenará a información da cualificación.';
$string['forcejavascriptmessage'] = 'Requírese JavaScript para visualizar este obxecto, por favor, active JavaScript no seu navegador e volva a intentalo.';
$string['found'] = 'Manifesto atopado';
$string['frameheight'] = 'A altura do marco do escenario ou a xanela.';
$string['framewidth'] = 'A anchura do marco do escenario ou a xanela.';
$string['fromleft'] = 'Desde a esquerda';
$string['fromtop'] = 'Desde arriba';
$string['fullscreen'] = 'Encher toda a pantalla';
$string['general'] = 'Datos xerais';
$string['gradeaverage'] = 'Cualificación media';
$string['gradeforattempt'] = 'Cualificación do intento';
$string['gradehighest'] = 'Cualificación máis alta';
$string['grademethod'] = 'Método de cualificación';
$string['grademethod_help'] = 'O método de cualificación define como se determina a cualificación dun intento único nunha actividade.

Hai 4 métodos de cualificación:

* Obxectos de aprendizaxe - Número de obxectos de aprendizaxe completados/aprobados
* Cualificación máis alta: a puntuación máxima obtida entre tódolos obxectos realizados
* Cualificación media: a media de tódalas puntuacións
* Cualificación suma: a suma de tódalas puntuacións';
$string['grademethoddesc'] = 'O método de cualificación define como se determina a nota da actividade para un único intento.';
$string['gradenoun'] = 'Cualificación';
$string['gradereported'] = 'Cualificación informada';
$string['gradesettings'] = 'Configuración de cualificación';
$string['gradescoes'] = 'Obxectos de aprendizaxe';
$string['gradesum'] = 'Cualificacións sumadas';
$string['height'] = 'Altura';
$string['hidden'] = 'Oculto';
$string['hidebrowse'] = 'Ocultar botón de vista previa';
$string['hidebrowse_help'] = 'O modo de vista previa permite ao alumnado explorar unha actividade antes de intentala.';
$string['hidebrowsedesc'] = 'O modo de vista previa permite ao alumnado explorar unha actividade antes de intentala.';
$string['hideexit'] = 'Ocultar ligazón de saída';
$string['hidereview'] = 'Ocultar botón de revisión';
$string['hidetoc'] = 'Amosar a estrutura do curso no visor';
$string['hidetoc_help'] = 'Como se amosa a táboa de contidos no visor';
$string['hidetocdesc'] = 'Este axuste especifica como se amosa a táboa de contidos no visor';
$string['EXESCORM_HIGHESTATTEMPT'] = 'Intento máis alto';
$string['chooseapacket'] = 'Elixir ou actualizar un paquete';
$string['identifier'] = 'Identificador de pregunta';
$string['incomplete'] = 'Incompleto';
$string['indicator:cognitivedepth'] = 'SCORM cognitiva';
$string['indicator:cognitivedepth_help'] = 'Este indicador está baseado na profundidade cognitiva acadada polo alumnado nunha actividade SCORM.';
$string['indicator:cognitivedepthdef'] = 'SCORM Cognitivo';
$string['indicator:cognitivedepthdef_help'] = 'O participante acadou esta porcentaxe do compromiso cognitivo ofrecido polas actividades SCORM durante este intervalo de análise (Niveis = Sen vista, Vista, Enviar, Ver comentarios)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'SCORM social';
$string['indicator:socialbreadth_help'] = 'Este indicador está baseado na amplitude social acadada polo alumnado nunha actividade SCORM.';
$string['indicator:socialbreadthdef'] = 'SCORM Social';
$string['indicator:socialbreadthdef_help'] = 'O participante acadou esta porcentaxe do compromiso social ofrecido polas actividades SCORM durante este intervalo de análise (Niveis = Sen participación, Só participante)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['interactions'] = 'Interaccións';
$string['masteryoverride'] = 'A puntuación de dominio anula o estado';
$string['masteryoverride_help'] = 'Se está activado e se proporciona unha puntuación de dominio, cando se chama a LMSFinish e se estableceu unha puntuación neta, o estado volverase calcular utilizando a puntuación neta e o do dominio e anularase calquera estado proporcionado por eXeLearning (incluído "incompleto").';
$string['masteryoverridedesc'] = 'Esta preferencia establece o valor por defecto da nota mínima para aprobar sobrescribindo o valor establecido.';
$string['myattempts'] = 'Os meus intentos';
$string['myaiccsessions'] = 'As miñas sesións AICC';
$string['repositorynotsupported'] = 'Este repositorio non admite a vinculación directa a un ficheiro imsmanifest.xml';
$string['trackid'] = 'ID';
$string['trackid_help'] = 'Este é o identificador establecido polo teu paquete eXeLearning para esta pregunta,';
$string['trackcorrectcount'] = 'Conta correcta';
$string['trackcorrectcount_help'] = 'Número de resultados correctos para a pregunta';
$string['trackpattern'] = 'Patrón';
$string['trackpattern_help'] = 'Esta é a resposta correcta a esta pregunta, non amosa a resposta do alumnado.';
$string['tracklatency'] = 'Latencia';
$string['tracklatency_help'] = 'Tempo transcorrido entre o momento en que a pregunta se puxo a disposición do alumnado para dar unha resposta e o momento da primeira resposta';
$string['trackresponse'] = 'Resposta';
$string['trackresponse_help'] = 'Esta é a resposta dada polo alumnado para esta pregunta';
$string['trackresult'] = 'Resultado';
$string['trackresult_help'] = 'Resultado en base á resposta do alumnado e o resultado correcto';
$string['trackscoremin'] = 'Puntuación mínima';
$string['trackscoremin_help'] = 'Valor mínimo no rango de puntuacións';
$string['trackscoremax'] = 'Puntuación máxima';
$string['trackscoremax_help'] = 'Valor máximo no rango de puntuacións';
$string['trackscoreraw'] = 'Puntuación bruta';
$string['trackscoreraw_help'] = 'Número que reflicte o resultado do alumnado en relación co rango delimitado polos valores mínimo e máximo';
$string['tracksuspenddata'] = 'Datos de suspensión';
$string['tracksuspenddata_help'] = 'Proporciona espacio para almacenar e recuperar datos entre sesións de aprendizaxe';
$string['tracktime'] = 'Hora';
$string['tracktime_help'] = 'Hora na que se iniciou o intento';
$string['tracktype'] = 'Tipo';
$string['tracktype_help'] = 'O tipo de pregunta, por exemplo "selección" ou "resposta curta".';
$string['trackweight'] = 'Peso';
$string['trackweight_help'] = 'Peso asignado ao elemento';
$string['invalidactivity'] = 'A actividade SCORM é incorrecta';
$string['invalidmanifestname'] = 'So poden ser seleccionados imsmanifest.xml ou ficheiros .zip';
$string['invalidstatus'] = 'Estado inválido';
$string['invalidurl'] = 'Especificouse un URL non válido';
$string['invalidurlhttpcheck'] = 'Especificouse un URL non válido. Mensaxe de debug:<pre>{$a->cmsg}</pre>';
$string['invalidhacpsession'] = 'Sesión HACP non válida';
$string['invalidmanifestresource'] = 'ADVERTENCIA: os seguintes recursos son mencionados no manifesto, pero non se poden atopar';
$string['last'] = 'Último acceso en';
$string['lastaccess'] = 'Último acceso';
$string['lastattempt'] = 'Último intento finalizado';
$string['lastattemptlock'] = 'Bloquear despois do último intento';
$string['lastattemptlock_help'] = 'Se se activa, ao alumnado impediráselle o lanzamento do reprodutor despois de ter utilizado tódolos intentos que tiña asignados.';
$string['lastattemptlockdesc'] = 'Se está activado, o alumnado non pode iniciar o reprodutor despois de usar tódolos intentos asignados.';
$string['location'] = 'Amosar a barra de localización';
$string['max'] = 'Cualificación máxima';
$string['maximumattempts'] = 'Número de intentos';
$string['maximumattempts_help'] = 'Este axuste permite restrinxir o número de intentos. Só é aplicable aos paquetes SCORM 1.2 e AICC.';
$string['maximumattemptsdesc'] = 'Esta preferencia fixa o valor por defecto sobre o número máximo de intentos nunha actividade';
$string['maximumgradedesc'] = 'Esta preferencia fixa o valor por defecto sobre a cualificación máxima dunha actividade';
$string['menubar'] = 'Amosar a barra de menú';
$string['min'] = 'Cualificación mínima';
$string['missing_attribute'] = 'Falta atributo ({$a->attr}) en marca {$a->tag}';
$string['missingparam'] = 'Un parámetro requirido falta ou é incorrecto';
$string['missing_tag'] = 'Falta marca {$a->tag}';
$string['mode'] = 'Moda';
$string['modulename'] = 'eXeLearning (SCORM)';
$string['modulename_help'] = 'Un SCORM creado con eXeLearning é un conxunto de ficheiros que se empaquetan conforme a unha norma estándar para os obxectos de aprendizaxe. O módulo de actividade eXeLearning (SCORM) permite crear e editar estes SCORM.

O contido amósase normalmente en varias páxinas, con navegación entre as páxinas. Hai varias opcións para a visualización dos contidos, con xanelas emerxentes, en táboas de contidos, con botóns de navegación etc. Os contidos de eXeLearning moitas veces inclúen preguntas cualificables, que se rexistran no libro de cualificacións.

As actividades eXeLearning pódense usar:

* Para a presentación de contidos multimedia e animacións.
* Como ferramenta de avaliación.';
$string['modulename_link'] = 'mod/mod_exescorm/view';
$string['modulenameplural'] = 'Contidos eXeLearning (SCORM)';
$string['nav'] = 'Amosar navegación';
$string['nav_help'] = 'Este axuste especifica se se amosarán ou ocultarán os botóns de navegación e a súa posición.

Hai tres opcións:

* Non - Non amosar os botóns de navegación
* Baixo o contido - Amosar os botóns de navegación debaixo do contido
* Flotantes - Permite especificar manualmente a posición dos botóns de navegación desde a esquerda e desde arriba con respecto á xanela.';
$string['navdesc'] = 'Este axuste especifica se se amosarán ou ocultarán os botóns de navegación e a súa posición.';
$string['navpositionleft'] = 'Posición dos botóns de navegación desde a esquerda en píxeles.';
$string['navpositiontop'] = 'Posición dos botóns de navegación desde arriba, en píxeles.';
$string['networkdropped'] = 'O visor de eXeLearning determinou que a túa conexión a Internet é inestable ou foi interrompida. Se continúa nesta actividade de eXeLearning, o seu progreso pode que non se garde.<br />
Debería pechar a actividade agora e volver cando teña unha conexión a Internet estable.';
$string['newattempt'] = 'Comezar un novo intento';
$string['next'] = 'Continuar';
$string['noactivity'] = 'Nada que informar';
$string['noattemptsallowed'] = 'Número de intentos permitidos';
$string['noattemptsmade'] = 'Número de intentos realizados';
$string['no_attributes'] = 'A marca {$a->tag} debe ter atributos';
$string['no_children'] = 'A marca {$a->tag} debe ter fillos';
$string['nolimit'] = 'Intentos ilimitados';
$string['nomanifest'] = 'Ficheiro incorrecto - falta imsmanifest.xml ou estrutura AICC';
$string['noprerequisites'] = 'Sentímolo pero non posúe os pre-requisitos requiridos para acceder a este obxecto de aprendizaxe';
$string['noreports'] = 'Non hai informes que amosar';
$string['normal'] = 'Normal';
$string['noscriptnoexescorm'] = 'O seu navegador non admite Javascript, ou ten a opción Javascript desactivado. Este contido non pode reproducirse ou gardar os datos correctamente.';
$string['notattempted'] = 'Non se intentou';
$string['not_corr_type'] = 'Non concorda o tipo para a marca {$a->tag}';
$string['notopenyet'] = 'Esta actividade non estará dispoñible ata {$a}';
$string['objectives'] = 'Obxectivos';
$string['openafterclose'] = 'Especificou unha data de apertura posterior á data de peche';
$string['optallstudents'] = 'Tódolas persoas usuarias';
$string['optattemptsonly'] = 'Só usuarias/os con intentos';
$string['optnoattemptsonly'] = 'Só usuarias/os sen intentos';
$string['options'] = 'Opcións (non admitidas por algúns navegadores)';
$string['optionsadv'] = 'Opcións (Avanzadas)';
$string['optionsadv_desc'] = 'Se se selecciona, o ancho e o alto serán listados como opcións avanzadas.';
$string['organization'] = 'Organización';
$string['organizations'] = 'Organizacións';
$string['othersettings'] = 'Axustes adicionais';
$string['page-mod-exescorm-x'] = 'Calquera páxina do módulo eXeLearning';
$string['pagesize'] = 'Tamaño da páxina';
$string['package'] = 'Paquete';
$string['package_help'] = 'O ficheiro do paquete é un ficheiro zip que contén un SCORM xerado con eXeLearning.';
$string['packagedir'] = 'Erro do sistema: non se pode crear o directorio de paquetes';
$string['packagefile'] = 'Non se especificou un paquete';
$string['packagehdr'] = 'Paquete';
$string['packageurl'] = 'URL';
$string['packageurl_help'] = 'Este parámetro activa un URL para especificar o paquete eXeLearning en lugar de seleccionar un ficheiro a través do selector de ficheiros.';
$string['passed'] = 'Pasado';
$string['php5'] = 'PHP 5 (librería nativa DOMXML)';
$string['player:next'] = 'Seguinte';
$string['player:prev'] = 'Anterior';
$string['player:skipnext'] = 'Seguinte do mesmo nivel';
$string['player:skipprev'] = 'Anterior do mesmo nivel';
$string['player:toogleFullscreen'] = 'Alternar pantalla completa';
$string['player:up'] = 'Subir nivel';
$string['pluginadministration'] = 'Administración do contido eXeLearning';
$string['pluginname'] = 'eXeLearning (SCORM)';
$string['popup'] = 'Abrir Obxectos de Aprendizaxe nunha xanela nova';
$string['popuplaunched'] = 'Este contido abriuse nunha nova xanela. Se rematou de ver este recurso, faga clic aquí para regresar á páxina do curso.';
$string['popupmenu'] = 'Nun menú despregable';
$string['popupopen'] = 'Abrir paquete nunha xanela nova';
$string['popupsblocked'] = 'Parece que as xanelas emerxentes están bloqueadas, detendo a execución deste módulo. Verifique a configuración do explorado antes de comezar de novo.';
$string['position_error'] = 'A marca {$a->tag} non pode ser un fillo da marca {$a->parent}';
$string['preferencesuser'] = 'Preferencias para esta exportación';
$string['preferencespage'] = 'Preferencias exclusivas para esta páxina';
$string['prev'] = 'Anterior';
$string['privacy:metadata:aicc:data'] = 'Datos persoais pasados a través do subsistema.';
$string['privacy:metadata:aicc:externalpurpose'] = 'Este complemento envía datos externamente utilizando o protocolo AICC HACP.';
$string['privacy:metadata:aicc_session:lessonstatus'] = 'O estado da lección a rastrexar';
$string['privacy:metadata:aicc_session:exescormmode'] = 'O modo do elemento a rastrexar';
$string['privacy:metadata:aicc_session:exescormstatus'] = 'O estado do elemento a rastrexar';
$string['privacy:metadata:aicc_session:sessiontime'] = 'O tempo da sesión a rastrexar';
$string['privacy:metadata:aicc_session:timecreated'] = 'A hora na que se creou o elemento rastrexado';
$string['privacy:metadata:attempt'] = 'O número de intento';
$string['privacy:metadata:scoes_track:element'] = 'O nome do elemento a rastrexar';
$string['privacy:metadata:scoes_track:value'] = 'O valor do elemento dado';
$string['privacy:metadata:exescorm_aicc_session'] = 'A información de sesión do protocolo AICC HACP';
$string['privacy:metadata:exescorm_scoes_track'] = 'Os datos rastrexados dos SCO pertencentes á actividade.';
$string['privacy:metadata:timemodified'] = 'A hora no que o elemento rastrexado se modificou por última vez';
$string['privacy:metadata:userid'] = 'O ID da persoa usuaria que accedeu ao contido eXeLearning.';
$string['protectpackagedownloads'] = 'Descarga de paquete protexido';
$string['protectpackagedownloads_desc'] = 'Se está activado, os paquetes eXeLearning poden ser descargados só se a persoa usuaria ten asignada capacidade en course:manageactivities. Se está desactivado, os paquetes eXeLearning poden ser sempre descargados.';
$string['raw'] = 'Puntuación bruta';
$string['regular'] = 'Manifesto regular';
$string['report'] = 'Informe';
$string['reports'] = 'Informes';
$string['reportcountallattempts'] = '{$a->nbattempts} intentos de {$a->nbusers} persoas usuarias, dun total de {$a->nbresults} resultados';
$string['reportcountattempts'] = '{$a->nbresults} resultados ({$a->nbusers} users)';
$string['response'] = 'Resposta';
$string['result'] = 'Resultado';
$string['results'] = 'Resultados';
$string['review'] = 'Revisión';
$string['reviewmode'] = 'Modo Revisión';
$string['rightanswer'] = 'Resposta correcta';
$string['exescormstandard'] = 'Modo estándar';
$string['exescormstandarddesc'] = 'Cando está desactivado, Moodle permite que os paquetes SCORM 1.2 almacenen máis do que permite a especificación, e utiliza a configuración de formato de nome completo de Moodle cando pasa o nome das persoas usuarias ao paquete eXeLearning.';
$string['scoes'] = 'Obxectos de aprendizaxe';
$string['score'] = 'Puntuación';
$string['scrollbars'] = 'Permitir desprazamento da xanela';
$string['search:activity'] = 'Paquete eXeLearning (SCORM) - Información de actividade';
$string['selectall'] = 'Seleccionar todo';
$string['selectnone'] = 'Deseleccionar todo';
$string['show'] = 'Amosar';
$string['sided'] = 'Lateral';
$string['skipview'] = 'Omitir ao alumnado a páxina de estrutura de contidos';
$string['skipview_help'] = 'Este axuste especifica se a estrutura da páxina de contido debe ser omitida (non se amosa). Se o paquete contén só un obxecto de aprendizaxe, a páxina da estrutura do contido sempre se pode omitir.';
$string['skipviewdesc'] = 'Esta preferencia fixa o valor por defecto sobre cando pasar por alto a estrutura de contido dunha páxina';
$string['slashargs'] = 'ATENCIÓN: os argumentos \'slash\' están desactivados neste sitio e os obxectos poden non funcionar como se agarda.';
$string['stagesize'] = 'Tamaño de marco/xanela';
$string['stagesize_help'] = '<p>Estes dous parámetros definen a altura e a anchura do marco ou xanela na que se visualizará o obxecto de aprendizaxe.</p>';
$string['started'] = 'Comezado en';
$string['status'] = 'Status';
$string['statusbar'] = 'Amosar a barra de estado';
$string['student_response'] = 'Resposta';
$string['subplugintype_exescormreport'] = 'Informe';
$string['subplugintype_exescormreport_plural'] = 'Informes';
$string['suspended'] = 'Suspendido';
$string['syntax'] = 'Erro de sintaxe';
$string['tag_error'] = 'Marca descoñecida ({$a->tag}) con este contido: {$a->value}';
$string['time'] = 'Hora';
$string['title'] = 'Título';
$string['toolbar'] = 'Amosar a barra de ferramentas';
$string['too_many_attributes'] = 'A marca {$a->tag} ten demasiados atributos';
$string['too_many_children'] = 'A marca {$a->tag} ten demasiados fillos';
$string['totaltime'] = 'Hora';
$string['trackingloose'] = 'ATENCIÓN: os datos de rastrexo deste paquete perderanse!';
$string['type'] = 'Tipo';
$string['typeaiccurl'] = 'URL AICC externo';
$string['typeexescormcreate'] = 'Crear con eXeLearning';
$string['typeexescormedit'] = 'Editar con eXeLearning';
$string['typeexternal'] = 'Manifesto SCORM externo';
$string['typelocal'] = 'Paquete subido';
$string['typelocalsync'] = 'Paquete baixado';
$string['undercontent'] = 'Baixo o contido';
$string['unziperror'] = 'Aconteceu un erro durante a descompresión do paquete';
$string['updatefreq'] = 'Actualizar frecuencia automaticamente';
$string['updatefreq_error'] = 'A frecuencia de auto-actualización unicamente pode ser establecida se o paquete está aloxado externamente';
$string['updatefreq_help'] = 'Esto permite descargar e actualizar automaticamente o paquete externo';
$string['updatefreqdesc'] = 'Esta preferencia especifica o valor por defecto sobre a frecuencia de actualización automática dunha actividade';
$string['validateaexescorm'] = 'Validar un paquete';
$string['validation'] = 'Resultado da validación';
$string['validationtype'] = 'Esta preferencia axusta a librería DOMXML usada para validar un Manifesto SCORM. Se ten dúbidas, deixe a opción activada.';
$string['value'] = 'Valor';
$string['versionwarning'] = 'A versión do manifesto é anterior á 1.3, atención á marca {$a->tag}';
$string['viewallreports'] = 'Ver os informes de {$a} intentos';
$string['viewalluserreports'] = 'Ver os informes de {$a} persoas usuarias';
$string['whatgrade'] = 'Cualificación de intentos';
$string['whatgrade_help'] = 'Se se permiten múltiples intentos, este parámetro especifica se se almacenará no libro de cualificacións o valor máis alto, a media, o primeiro ou o último intento. A opción de último intento completado non inclúe os intentos cun estado \'errado\'.

Notas sobre a xestión de múltiples intentos:

* A posibilidade de iniciar un novo intento márcase nunha a casa situada enriba do botón Ingresar na páxina de estrutura do contido, polo que debe asegurarse que permite o acceso a esa páxina se desexa permitir máis dun intento.
* Algúns paquetes SCORM son intelixentes sobre os novos intentos, pero moitos non o son, o que significa que se o alumnado volve facer un intento e o contido SCORM non ten a lóxica interna para evitar sobrescribir os intentos anteriores, estes pódense sobrescribir, mesmo se o intento estaba en estado "completado" ou "aprobado".
* A configuración de "Forzar completar", "Forzar novo intento" e "Bloqueo despois do intento final" tamén melloran a xestión de múltiples intentos.';
$string['whatgradedesc'] = 'No caso de permitir múltiples intentos se se rexistrará no libro de cualificacións o intento máis alto, a media, o primeiro ou o último completado.';
$string['width'] = 'Anchura';
$string['window'] = 'Xanela';
$string['youmustselectastatus'] = 'Debe seleccionar un estado que será requirido';

// Deprecated since Moodle 4.0.
$string['info'] = 'Info';
$string['displayactivityname'] = 'Amosar o nome da actividade';
$string['displayactivityname_help'] = 'Amosar ou non o nome da actividade sobre o visor de eXeLearning.';
