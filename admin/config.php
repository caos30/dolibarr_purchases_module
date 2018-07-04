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
 *	\file       htdocs/purchases/admin/config.php
 *      \ingroup    purchases
 *      \brief      Settings page of module
 *      \version    v 1.0 2017/11/20
 */

// == ACTIVATE the ERROR reporting	
ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1); 

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");

// == PURCHASES_MODULE_DOCUMENT_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/purchases/core/modules/modPurchases.class.php')){
        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/purchases');
        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/purchases');
    }else{
        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/purchases');
        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/purchases');
    }

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');

if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("purchases");

/***************************************************
 * 
 *	Actions / prepare data
 * 
****************************************************/

    // == request action by GET/POST
    
        //if (!empty($_POST)){ echo var_export($_POST,true);die(); }
        /*
        if (isset($_POST['action']) && $_POST['action'] == "addRule" && $fk_account > 0){
            $rule = new ImpBM_rule($db);
            $rule->ruleOrder = GETPOST('ruleOrder','int');
            $rule->pattern = GETPOST('rulePattern');
            $rule->fk_account = $fk_account;
            $rule->fk_categ = GETPOST('fk_categ');

            $result = $rule->create(NULL);
            if ($result < 0) dol_print_error($db,$myobject->error);
        }
         * 
         */
        
/***************************************************
 * 
 *	View
 * 
****************************************************/

$help_url='';
llxHeader('','purchases',$help_url);

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

$html = new Form($db);

dol_fiche_head($head, 'tabconfig', '');

?>

<form id="frm" name="stoctransfersForm" action="<?= $_SERVER["PHP_SELF"] ?>" method="post">
      
    <input type="hidden" name="action" id="action" value=""/>
    
    <table class="noborder">
        <tr>
            <td>
                <?= $langs->trans('purchasesConfig01') ?>
            </td>
        </tr>
    </table>
    
    <!-- MODULE VERSION & USER GUIDE LINK -->
    <?php 
        require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/core/modules/modPurchases.class.php';
        $module = new modPurchases($db); 
        $user_lang = substr($langs->defaultlang,0,2);
    ?>
    <div style="margin: 2rem 0;color: #ccc;display: inline-block;border-top: 1px #ccc solid;border-bottom: 1px #ccc solid;background-color: rgba(0,0,0,0.05);padding: 0.5rem;">
        <span class="help">Purchases <?= $module->version ?> 
           &nbsp; | &nbsp; <a href="https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=<?= $user_lang == 'es' ? '35':'36'?>" target="_blank"><?= $langs->trans('purchasesUserGuide') ?></a>
        </span>
    </div>
    
    
<?php

dol_fiche_end();

print "</form>\n";


clearstatcache();

dol_htmloutput_mesg($mesg);


llxFooter();

$db->close();
