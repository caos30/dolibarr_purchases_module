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
 *	\file       htdocs/purchases/purchase_edit.php
 *      \defgroup   purchases 
 *      \brief      Manage the edition of a purchase
 *      \version    v 1.0 2017/11/20
 */

    // == ACTIVATE the ERROR reporting
    ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1);

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
include_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases_purchase.class.php';
require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases.lib.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");
$langs->load("projects");
$langs->load("purchases");

$user->getrights('purchases');

// == Security check
    $result=restrictedArea($user,'fournisseur&produit');

// == Get parameters
    $action = GETPOST('action','alpha');
    $purchase_id = GETPOST('rowid', '0');
    
    $id_product = GETPOST('productid', 'int');
    $batch = GETPOST('batch');
    $qty = GETPOST('qty');

// == Load currency exchange rates (from the default currency to the other currencies)
    if ($conf->multicurrency->enabled){
        require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/currency_exchange_rates.class.php';
        $exchangeRates = new CurrencyExchangeRates();
        $rates = $exchangeRates->getRates();
    }else{
        $rates = array();
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

if ($action == 'delete_purchase') {
    
        if (!$purchase->rowid){
            $_SESSION['EventMessages'][] = array("purchasesErrorMsg04",null,'errors');
        }else{
            $purchase->delete($user);
            $_SESSION['EventMessages'][] = array("RecordDeleted",null,'mesgs');
        }
        // == redirect to list
        header("Location: purchase_list.php?mainmenu=commercial"); die();
    
    
}else if ($action == 'save_card') {

        // == prepare purchase card

            $purchase->fk_user_author = $user->id;
            if (!empty($_POST['label']))
                $purchase->label = $_POST['label'];

            if (!empty($_POST['note']))
                $purchase->note = $_POST['note'];

            if (!empty($_POST['fk_project']))
            $purchase->fk_project = intval($_POST['fk_project']);

            if (isset($_POST['status']))
                $purchase->status = $_POST['status'];

        // == run query on database

            if ($purchase->rowid > 0){
                $result = $purchase->update();
                $new_purchase = false;
            }else{
                $result = $purchase->create(NULL);
                $new_purchase = true;
            }

        // == response to user

            if ($result < 0){
                $_SESSION['EventMessages'][] = array($purchase->error,null,'errors');
            }else if ($new_purchase){
                $_SESSION['EventMessages'][] = array("RecordCreatedSuccessfully",null,'mesgs');
            }else{
                $_SESSION['EventMessages'][] = array("RecordModifiedSuccessfully",null,'mesgs');
            }
            
        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();

    
}else if ($action == 'add_line') {
    
        if (empty($_POST['pid'])){
            $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'product'),null,'errors');
        }else if (empty($_POST['n'])){
            $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'n'),null,'errors');
        }else{
            $purchase->addProductLine(array(
                            'pid' => $_POST['pid'],
                            'n' => $_POST['n'],
                        ));
        }
        
        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();
    
    
}else if ($action == 'save_lines') {
    
        $purchase->savePostedProducts();

        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();
    
            
}else if ($action == 'add_line_supp') {
    
        if (empty($_POST['product_id']) || empty($_POST['supplier_id'])){
            $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'product'),null,'errors');
        }else{
            $purchase->addProductSupplierLine(array(
                            'pid' => $_POST['product_id'],
                            'sid' => $_POST['supplier_id'],
                            'unitprice' => $_POST['unitprice']
                        ));
        }
        
        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();

            
}else if ($action == 'del_line') {
    
        if (empty($_POST['product_id'])){
            $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'product'),null,'errors');
        }else{

            $new_products = array();
            foreach($purchase->products as $pid=>$p){
                if ($pid != trim($_POST['product_id'])) $new_products[$pid] = $p;
            }
            $purchase->products = $new_products;

            $result = $purchase->update();

            if ($result < 0) {
                $_SESSION['EventMessages'][] = array($purchase->error,null,'errors');
            }else{
                $_SESSION['EventMessages'][] = array("DeleteLine",null,'mesgs');
            }
        }
        
        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();
    
    
}else if ($action == 'del_line_supp' || $action == 'del_line_supp_and_price') {
    
        if (empty($_POST['product_id']) || !isset($_POST['price_ii']) || $_POST['price_ii']==''){
            $_SESSION['EventMessages'][] = array($langs->trans("ErrorGlobalVariableUpdater2",'product'),null,'errors');
        }else{
            $vars = array('pid'=>trim($_POST['product_id']), 'pr_ii'=>trim($_POST['price_ii']), 'action'=>$action);
            $purchase->removeProductSupplierLine($vars);
        }

        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();
    
}else if ($action == 'json_mark_price_to_order') {
    
        if (!empty($_GET['pid']) && isset($purchase->products[$_GET['pid']])){
            $purchase->products[$_GET['pid']]['price_selected'] = $_GET['pr_ii'];
            $purchase->update();
        }
        echo json_encode(array('ok'=>'1')); 
        die();

}else if ($action == 'send_email_requests') {
    
        if (empty($_GET['rowid'])){
            $_SESSION['EventMessages'][] = array("purchasesErrorMsg01",null,'errors');
        }else{
            $purchase->sendEmailRequests();
        }

        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();
        
    
}else if ($action == 'create_order') {
    
        if (empty($_GET['sid'])){
            $_SESSION['EventMessages'][] = array("purchasesErrorMsg01",null,'errors');
        }else{

            // == process request
                $order_id = $purchase->createOrder(array('rates'=>$rates));

            // == redirection to order page
                if ($order_id > 0){
                    header("Location: ".DOL_URL_ROOT."/fourn/commande/card.php?id=".$order_id); die();
                }else{
                    $_SESSION['EventMessages'][] = array("purchasesErrorMsg02",null,'errors');
                }
        }
    
        // == redirect to the "same" URL 
            header("Location: purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=".$purchase->rowid); die();
    
}else{
    
        $_SESSION['parsedData'] = "";
        $_SESSION['toConciliate'] = "";
        
}
    
    
/***************************************************
 * 
 *	View
 * 
****************************************************/

print _render_view('edit',array('purchase'=>$purchase, 'rates'=>$rates));