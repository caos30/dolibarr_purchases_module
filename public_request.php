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
 *	\file       htdocs/purchases/public_request.php
 *      \defgroup   purchases 
 *      \brief      Manage web form to capture prices by suppliers without login
 *      \version    v 1.0 2017/11/20
 */

    // == ACTIVATE the ERROR reporting
    ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1);

    
define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");

// == PURCHASES_MODULE_DOCUMENT_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/purchases/core/modules/modPurchases.class.php')){
        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/purchases');
        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/purchases');
    }else{
        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/purchases');
        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/purchases');
    }

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases_purchase.class.php';
require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases.lib.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// == decide the user language (it can change it using flags)
    // = list of languages available for this module
        $languages = array();
        $scandir = scandir(PURCHASES_MODULE_DOCUMENT_ROOT.'/langs');
        foreach($scandir as $l){
            if (preg_match('/_/',$l)) $languages[$l] = $l;
        }
    // = request to change current default language ?
        if (!empty($_GET['lang']) && isset($languages[trim($_GET['lang'])])) 
            $_SESSION['lang'] = trim($_GET['lang']);
    
        if (!empty($_SESSION['lang']) && $langs->getDefaultLang() != $_SESSION['lang']){
            unset($langs);
            $langs = new Translate("",$conf);
            $langs->setDefaultLang($_SESSION['lang']);
        }
    
// == load translations 
    $langs->load("products");
    $langs->load("stocks");
    $langs->load("orders");
    $langs->load("purchases@purchases");

    $user->getrights('purchases');

// == Get parameters
    $action = GETPOST('action','alpha');
    $purchase_id = GETPOST('rid', '0');
    $purchase_id_md5 = GETPOST('mrid', '0');
    $supplier_id = GETPOST('sid', '0');
    $supplier_id_md5 = GETPOST('msid', '0');
    
// == integrity check
    if (md5('pupy'.trim($supplier_id)) != trim($supplier_id_md5)
            || md5('pupy'.trim($purchase_id)) != trim($purchase_id_md5)) {
        echo $langs->trans("purchasesErrorMsg04");
        die();
    }
    
// == data object
    global $purchase;
    $purchase = new Purchase($db);
    // == request to edit an existing purchase
        if ($purchase_id > 0) {
            $ret = $purchase->fetch($purchase_id);
            
    // == request to create a new purchase with a list of products and/or a fk_project 
        }else if (!empty($_GET['products'])){
            $rowid = $purchase->createWithProducts();
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$rowid); die();
        }
    
// == other data
    $now = dol_now();
    $listofdata=array();
    if (! empty($_SESSION['massstockmove'])) $listofdata=json_decode($_SESSION['massstockmove'],true);
    
// == SESSION MESSAGES (this is from this module)
    if (!empty($_SESSION['EventMessages']) && is_array($_SESSION['EventMessages']) && count($_SESSION['EventMessages'])>0){
        foreach($_SESSION['EventMessages'] as $arr){
            setEventMessages($arr[0],$arr[1],$arr[2]);
        }
    }
    $_SESSION['EventMessages'] = array();
    
    
if (!empty($_POST)){
    //echo _var_export($_GET,'$_GET')._var_export($_POST,'$_POST')._var_export($_FILES,'$_FILES'); echo _var_export($purchase->products,'$purchase->products'); die();
}

/***************************************************
 * 
 *	Actions
 * 
****************************************************/

if ($action == 'save_quotes') {
    
        if (empty($_POST['token']) || empty($_GET['rid'])){
            $_SESSION['EventMessages'][] = array("purchasesErrorMsg01",null,'errors');
        }else{

            // == process request
                $error = $purchase->saveQuotationForm();

            // == redirection THANKS page
                if ($error == 0){ 
                    header("Location: ".$_SERVER['HTTP_REFERER']."&action=request_thanks"); die();
                }
        }
    
}else if ($action == 'request_thanks') {
    
    print _render_view('request_thanks',array('purchase'=>$purchase, 'supplier_id'=>$supplier_id));
    
    return;
    
}
    
    
/***************************************************
 * 
 *	View
 * 
****************************************************/

print _render_view('request',array('purchase'=>$purchase, 'supplier_id'=>$supplier_id, 'languages'=>$languages));