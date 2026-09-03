<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'local_subcourseenrol', language 'es'.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Auto-matrícula por subcurso';
$string['enabled'] = 'Habilitar auto-matrícula';
$string['enabled_desc'] = 'Si está habilitado, los usuarios que hagan clic en un enlace de actividad subcourse se matricularán automáticamente en el curso de destino si no lo están ya.';

$string['howto_heading'] = 'Cómo funciona';
$string['howto_desc'] = '<p>Cuando un usuario hace clic en una actividad Subcourse dentro de un curso maestro, este plugin lo matricula automáticamente en el curso referenciado (destino) con rol de estudiante.</p>
<ul>
<li>La fecha de expiración de la matrícula en el curso destino se hereda del curso maestro.</li>
<li>Si el curso maestro no tiene fecha de expiración, la matrícula en el curso destino tampoco tendrá expiración.</li>
<li>Si el usuario accede al curso destino directamente desde el catálogo de cursos (sin pasar por el enlace del subcourse), se aplica el comportamiento normal de Moodle: el usuario no podrá acceder si no está matriculado o si la auto-matrícula no está habilitada.</li>
</ul>
<p><strong>Requisitos:</strong></p>
<ul>
<li>El plugin <code>mod_subcourse</code> debe estar instalado.</li>
<li>El usuario debe estar matriculado y activo en el curso maestro.</li>
<li>El método de matrícula <code>manual</code> debe estar disponible en el curso destino.</li>
</ul>';
$string['privacy:metadata'] = 'Este plugin no almacena datos personales.';
$string['event_user_autoenrolled'] = 'Usuario auto-matriculado mediante subcourse';
$string['event_user_autoenrolled_desc'] = 'El usuario con id "{$a->userid}" se auto-matriculó en el curso con id "{$a->courseid}" porque accedió a una actividad subcourse en el curso maestro con id "{$a->other[\'mastercourseid\']}".';
