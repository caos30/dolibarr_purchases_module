<?php
/* Copyright (C) 2017 Sergi Rodrigues <proyectos@imasdeweb.com>
 *
 * Licensed under the GNU GPL v3 or higher (See file gpl-3.0.html)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/purchases/admin/about.php
 *      \ingroup    purchases
 *      \brief      Page about
 *      \version    v 1.0 2017/11/20
*/

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

// == PURCHASES_MODULE_DOCUMENT_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/purchases/core/modules/modPurchases.class.php')){
        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/purchases');
        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/purchases');
    }else{
        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/purchases');
        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/purchases');
    }

if (!$user->admin) accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("purchases");


/**
 * View
 */

$help_url='';
llxHeader('','',$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("purchasesSetup"),$linkback,'setup');
print '<br>';

$h=0;

$head[$h][0] = 'config.php';
$head[$h][1] = $langs->trans("Config");
$head[$h][2] = 'tabconfig';
$h++;

$head[$h][0] = 'about.php';
$head[$h][1] = $langs->trans("About");
$head[$h][2] = 'tababout';
$h++;

$search_query = 'proyectos@imasdeweb.com';

dol_fiche_head($head, 'tababout', '');

print $langs->trans("purchasesAboutInfo").'<br>';
print '<br>';

print $langs->trans("purchasesMoreModules",$search_query).'<br>';
print '&nbsp; &nbsp; &nbsp; '.$langs->trans("purchasesMoreModulesLink",$search_query).'<br>';
print '<a href="http://www.dolistore.com/search.php?search_query='.$search_query.'" target="_blank"><img border="0" width="180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a><br><br><br>';

print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
