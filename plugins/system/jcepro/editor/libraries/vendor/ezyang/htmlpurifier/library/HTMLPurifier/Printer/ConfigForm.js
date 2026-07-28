/* jce - 2.9.99.9 | 2026-07-08 | https://www.joomlacontenteditor.net | Source: https://github.com/widgetfactory/jce | Copyright (C) 2006 - 2026 Ryan Demmer. All rights reserved | GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html; see LICENSE.txt */
function toggleWriteability(id_of_patient, checked) {
    document.getElementById(id_of_patient).disabled = checked;
}