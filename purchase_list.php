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
 *	\file       htdocs/purchases/purchase_list.php
 *      \defgroup   purchases 
 *      \brief      Manage the list of purchases
 *      \version    v 1.0 2017/11/20
 */

    // == ACTIVATE the ERROR reporting
    ini_set('display_errors',1);ini_set('display_startup_errors',1);ini_set('error_reporting', E_ALL);
    
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
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
include_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
include_once DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases_purchase.class.php';
require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases.lib.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");
$langs->load("projects");
$langs->load("purchases");

$user->getrights('purchases');

// == Get parameters
    /*
    $account = GETPOST('account', 'int');
    $myparam = GETPOST('myparam', 'str') ? GETPOST('myparam', 'str') : '';
    */

// == GETPOST search params
    $fsearch = array();
    
    if (!GETPOST("button_removefilter_x") && !GETPOST("button_removefilter") && !GETPOST("button_removefilter.x")){ // Both test are required to be compatible with all browsers
        foreach ($_GET as $ff=>$vv){
            if (preg_match('/^(search_)(.*)/',$ff,$matches) && $vv!=''){
                if ($matches[2] != 'status' ||  $vv != '-1') $fsearch[$matches[2]] = $vv;
            } 
        }
        foreach ($_POST as $ff=>$vv){
            if (preg_match('/^(search_)(.*)/',$ff,$matches) && $vv!=''){
                if ($matches[2] != 'status' ||  $vv != '-1') $fsearch[$matches[2]] = $vv;
            } 
        }
    }
    
// == Security check
    $result=restrictedArea($user,'fournisseur&produit');

// == SESSION MESSAGES (this is from this module)
    if (!empty($_SESSION['EventMessages']) && is_array($_SESSION['EventMessages']) && count($_SESSION['EventMessages'])>0){
        foreach($_SESSION['EventMessages'] as $arr){
            setEventMessages($arr[0],$arr[1],$arr[2]);
        }
    }
    $_SESSION['EventMessages'] = array();
    

if (!empty($_POST)){
    //echo _var_export($_GET,'$_GET')._var_export($_POST,'$_POST')._var_export($_FILES,'$_FILES');    die();
}

/***************************************************
 * 
 *	Actions
 * 
****************************************************/
    
if ($_REQUEST["action"] == 'delete') {


}else {
    $_SESSION['parsedData'] = "";
    $_SESSION['toConciliate'] = "";
}
    
    
/***************************************************
 * 
 *	View
 * 
****************************************************/


print _render_view('list',array('fsearch'=>$fsearch));
