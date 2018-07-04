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
 *	\file       htdocs/purchases/lib/purchases_purchase.lib.php
 *      \ingroup    purchases
 *      \brief      Purchase class to create/edit/get/etc... records from 'llx_purchases' table
 *      \version    v 1.0 2017/11/20
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


class Purchase extends CommonObject
{
    var $db;							//!< To store db handler
    var $error;							//!< To return error code (or message)
    var $errors=array();				//!< To return several error codes (or messages)

    public $element='purchase';
    public $table_element='purchases';
    public $picto='purchases';
    
    var $rowid;
    var $ts_create;
    var $fk_user_author;
    var $fk_project;
    var $label = '';
    var $note = '';
    var $status = '0'; // 0->draft, 1->validated-not-delivered, 2->delivered
    
    var $products = array();
    var $s_products = '';
    var $n_products = 0;
    
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function __construct($DB) 
    {
        global $conf;
        $this->db = $DB;
        
        return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create()
    {
    	global $conf, $langs, $user;
        $error=0;

        // Clean parameters
            if (empty($this->fk_project)) $this->fk_project = 0;
            if (empty($this->label)) $this->label = $langs->trans('purchasesPurchase').' '.date('d/m/Y H:i');
            if (empty($this->note)) $this->note = '';
            if (empty($this->status)) $this->status = '0';

        // Check parameters
            if (empty($user->id))
            {
                    $this->error = "ErrorBadParameter";
                    dol_syslog(get_class($this)."::create Try to create a purchaser with an empty parameter (user, ...)", LOG_ERR);
                    return -3;
            }

        // Insert request
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."purchases (";

            $sql.= "fk_user_author,";
            $sql.= "fk_project,";
            $sql.= "label,";
            $sql.= "note,";
            $sql.= "status";
		
            $sql.= ") VALUES (";
        
            $sql.= " '".$user->id."',";
            $sql.= " '".intval($this->fk_project)."',";
            $sql.= " '".$this->db->escape($this->label)."',";
            $sql.= " '".$this->db->escape($this->note)."',";
            $sql.= " '".$this->status."'";

            $sql.= ")";

            $this->db->begin();

            dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
                
        // run SQL
            $resql=$this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

                    if (! $error)
            {
                $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."purchases");

            }

        // Commit or rollback
            if ($error)
                    {
                            foreach($this->errors as $errmsg)
                            {
                        dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
                        $this->error.=($this->error?', '.$errmsg:$errmsg);
                            }	
                            $this->db->rollback();
                            return -1*$error;
                    }
                    else
                    {
                            $this->db->commit();
                return $this->rowid;
                    }
    }

    /**
     *      \brief      Create a new purchase taking a list of products from URL
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function createWithProducts()
    {

        // Check parameters
            if (empty($_GET['products'])){
                    $this->error = "ErrorBadParameter";
                    dol_syslog(get_class($this)."::createWithProducts() Try to create a purchaser without a product list (products GET variable)", LOG_ERR);
                    return -3;
            }
            
        // Clean parameters
            if (!empty($_GET['fk_project']) && intval($_GET['fk_project'])>0) $this->fk_project = intval($_GET['fk_project']);
            
        // create a new purchase
            $rowid = $this->create();

        // Products
            $ex = explode('_',trim($_GET['products']));
            if (is_array($ex) && count($ex)>0){
                foreach ($ex as $str){
                    $ex2 = explode('-',$str);
                    if (!is_array($ex2) || count($ex2)!=2 || floatval($ex2[1])==0) continue;
                    $this->addProductLine(array('pid'=>intval($ex2[0]), 'n'=>floatval($ex2[1])));
                }
            }
            
        return $rowid;
    }
    
    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."purchases";
        $sql.= " WHERE rowid = ".$id;
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $row = $resql->fetch_assoc();
                if(is_array($row)){
                    foreach ($row as $f=>$v) $this->{$f} = $v;
                }
                /*
                $this->rowid = $obj->rowid;
                $this->ts_create = $obj->pattern;
                 * 
                 */
            }
            $this->db->free($resql);
            
            $this->unserializeProducts();
            
            return 1;
        }
        else
        {
      	    $this->error = "Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    

    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
        $error=0;

        // Check parameters
            /*
            if (empty($this->fk_depot1) || empty($this->fk_depot2))
            {
                    $this->error = "ErrorBadParameter";
                    dol_syslog(get_class($this)."::create Try to create a purchase  with an empty parameter (user,... )", LOG_ERR);
                    return -3;
            }
            */
        // Clean parameters
            if (empty($this->fk_project)) $this->fk_project = 0;
            if (empty($this->label)) $this->label = $langs->trans('purchasesPurchase').' '.date('d/m/Y H:i');
            if (empty($this->note)) $this->note = '';
            if (empty($this->status)) $this->status = '0';
            
            $this->n_products = !is_array($this->products) ? 0 : count($this->products);
            if ($this->n_products > 0) $this->s_products = serialize($this->products); else $this->s_products = serialize(array());

        // Update request
            $sql = "UPDATE ".MAIN_DB_PREFIX."purchases SET ";

            $sql.= "label='".$this->db->escape($this->label)."', ";
            $sql.= "note='".$this->db->escape($this->note)."', ";
            $sql.= "fk_project=".intval($this->fk_project).",";
            $sql.= "status='".$this->status."',";
            $sql.= "s_products='".$this->db->escape($this->s_products)."', ";
            $sql.= "n_products=".$this->n_products;
        
            $sql.= " WHERE rowid=".$this->rowid;
            
            $this->db->begin();

            dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        
        // run query
            $resql = $this->db->query($sql);
            if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
            if ($error)
            {
                foreach($this->errors as $errmsg)
                {
                    dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
                    $this->error.=($this->error?', '.$errmsg:$errmsg);
                }	
                $this->db->rollback();
                return -1*$error;
            }
            else
            {
                $this->db->commit();
                return 1;
            }		
    }
  
  
    /**
    *   \brief      Delete object in database
    *	\param      user        	User that delete
    *   \param      notrigger	    0=launch triggers after, 1=disable triggers
    *	\return		int				<0 if KO, >0 if OK
    */
    function delete($user, $notrigger=0){
        
        global $conf, $langs;
        $error=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."purchases";
        $sql.= " WHERE rowid=".$this->rowid;

        $this->db->begin();

        dol_syslog(get_class($this)."::delete sql=".$sql);
        
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
            if ($error){
                    foreach($this->errors as $errmsg){
                        dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
                        $this->error.=($this->error?', '.$errmsg:$errmsg);
                    }	
                    $this->db->rollback();
                    return -1*$error;
            }else{
                    $this->db->commit();
                    return 1;
            }
    }
    
    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function getLatestPurchases($vars)
    {
    	global $langs;
        
        $max = !empty($vars['max']) ? intval($vars['max']) : 20;
        
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."purchases";
        $sql.= " ORDER BY rowid DESC";
        $sql.= " LIMIT ".$max;
        
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);

        $elements = array();
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                while($row = $resql->fetch_assoc()){
                    if (is_array($row)) $elements[] = $row;
                } 
            }
            $this->db->free($resql);
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
        }
        
        return $elements;
    }
    
    /**
     *    \brief      Add a new product to the current purcharse
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function addProductLine($vars)
    {
    	global $langs, $conf;
        
        // == load existing purchase prices
            $prices = array();
            $sql = "SELECT *, p.rowid as id FROM ".MAIN_DB_PREFIX."product_fournisseur_price as p"
                  ." LEFT JOIN ".MAIN_DB_PREFIX."societe as s"
                  ." ON (p.fk_soc = s.rowid)"
                  ." WHERE fk_product = ".$vars['pid']
                    ;
            $resql = $this->db->query($sql);
            if ($resql) {
                while($row = $resql->fetch_assoc()){
                    $curr = !empty($row['multicurrency_code']) ? $row['multicurrency_code'] : $conf->currency;
                    $prices[] = array('sid'=>$row['fk_soc'], 'rowid'=>$row['id'], 'unitprice'=>$row['unitprice'], 
                        'tva'=>$row['tva_tx'], 'days'=>intval($row['delivery_time_days']), 'min'=>floatval($row['quantity']),
                        'curr'=>$curr);
                }
            }

        // == add a new element to Products array
            if (!isset($this->products[$vars['pid']])) $this->products[$vars['pid']] = array();
            $this->products[$vars['pid']]['pid'] = $vars['pid'];
            $this->products[$vars['pid']]['n'] = floatval($vars['n']);
            $this->products[$vars['pid']]['prices'] = $prices;
            
        // == update status of the purchase
            if ($this->status == '0') $this->status = '1';
        
        // == save at database
            $this->n_products = count($this->products);
            $result = $this->update();
        
            if ($result < 0) 
                dol_print_error($this->db,$this->error);
            else
                $_SESSION['EventMessages'][] = array("RecordModifiedSuccessfully",null,'mesgs');
        
    }

    /**
     *    \brief      Remove a supplier price for a product in this purchase (optionally it removes too the price on Dolibarr)
     *    \param      $vars          Array of data
     *    \return     
     */
    function removeProductSupplierLine($vars)
    {
    	global $langs, $user;
        
        $new_products = array();
        foreach($this->products as $pid=>$p){
            if ($pid == trim($vars['pid'])){
                $new_prices = array();
                foreach($p['prices'] as $ii => $pr){
                    if ($ii == $vars['pr_ii']){
                        if ($vars['action'] == 'del_line_supp_and_price'){
                            // delete also the record ond Dolibarr of this price of this supplier for this product
                            if (!empty($pr['rowid']) && intval($pr['sid'])>0){
                                require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
                                $objectProdFourn = new ProductFournisseur($this->db);
                                $objectProdFourn->fetch(intval($pr['sid']));
                                $result = $objectProdFourn->remove_product_fournisseur_price($pr['rowid']);
                                if($result <= 0){
                                        $error++;
                                        $_SESSION['EventMessages'][] = array($objectProdFourn->error, $objectProdFourn->errors, 'errors');
                                }
                            }
                        }
                        continue;
                    }
                    $new_prices[] = $pr;
                }
                $p['prices'] = $new_prices;
            }
            $new_products[$pid] = $p;
        }
        $this->products = $new_products;

        $result = $this->update();

        if ($result < 0) {
            dol_print_error($db,$purchase->error);
        }else{
            $_SESSION['EventMessages'][] = array("DeleteLine",null,'mesgs');
        }
        
    }
    
    /**
     *    \brief      Add a new product to the current purcharse
     *    \param      $vars          Array of data
     *    \return     
     */
    function addProductSupplierLine($vars)
    {
    	global $langs, $user, $conf;
        
        // == get the tva_tx value for this supplier
            $objsoc = new Societe($this->db);
            $objsoc->fetch($vars['sid']);
            $default_vat = get_default_tva($objsoc, $objsoc, $vars['pid'], 0);
            $curr = !empty($objsoc->multicurrency_code) ? $objsoc->multicurrency_code : $conf->currency;

        // == load existing purchase prices for this supplier & product
            $this_supplier_prices = array();
            $resql = $this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product = ".$vars['pid']." AND fk_soc = ".$vars['sid']);
            if ($resql) {
                while($row = $resql->fetch_assoc()){
                    $this_supplier_prices[] = array('sid'=>$row['fk_soc'], 'rowid'=>$row['rowid'], 'unitprice'=>$row['unitprice'], 
                        'tva'=>$row['tva_tx'], 'days'=>intval($row['delivery_time_days']), 'min'=>floatval($row['quantity']),
                        'curr'=>$curr);
                }
            }

        // == we add a new purchase price for this supplier & product if there are none
            if (count($this_supplier_prices)==0){ 
                    $unitprice = floatval($vars['unitprice']);
                    
                // == add to database a new record for this price in the table product_fournisseur_price
                    $tva_tx = $default_vat != '' ? $default_vat : '16.000';
                    $now = dol_now();
                    $sql = "INSERT ".MAIN_DB_PREFIX."product_fournisseur_price "
                            ."(datec, fk_product, fk_soc, ref_fourn, fk_user, price, quantity, unitprice, tva_tx, entity, delivery_time_days) "
                            ." VALUES ('".$this->db->idate($now)."', ".$vars['pid'].", ".$vars['sid'].", '".$vars['pid'].'_'.time()."',".$user->id
                            .",".floatval($vars['unitprice']).", 1 ,".floatval($vars['unitprice']).", $tva_tx, ".($conf->entity).", 0)"; 
                    $resql = $this->db->query($sql);
                    if (! $resql) { 
                        $_SESSION['EventMessages'][] = array("Error ".$this->db->lasterror(),null,'errors');
                        $price_rowid = '';
                    }else{
                        $price_rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."product_fournisseur_price");
                    }

                // == add price element to array to be added to the products of this purchase
                    $this_supplier_prices[] = array('sid'=>$vars['sid'], 'rowid'=>$price_rowid, 'unitprice'=>$unitprice, 
                            'tva'=>$default_vat, 'days'=>'', 'min'=>'1', 'curr'=>$curr);
            }
            
        // == rebuild array of products adding this supplier, and their prices for this product if there are
            $new_products = array();
            foreach($this->products as $pid=>$p){
                if ($pid == trim($vars['pid'])){
                    foreach ($this_supplier_prices as $pr) $p['prices'][] = $pr;
                    
                }
                $new_products[$pid] = $p;
            }
            $this->products = $new_products;

            $result = $this->update();

            if ($result < 0) {
                dol_print_error($this->db,$this->error);
            }else{
                $_SESSION['EventMessages'][] = array("RecordModifiedSuccessfully",null,'mesgs');
            }
    }
        
    /**
     *    \brief      Save products incoming through _POST
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function savePostedProducts()
    {
    	global $langs;
        
        if (!isset($_POST['products']) || empty($_POST['action'])) return;

        // == check the incoming array products and use it to update the array on purchase object
            $update_doli_prices = array();
            foreach ($_POST['products'] as $pid => $p){
                if (!isset($this->products[$pid])) continue;
                $n = floatval($p['n']);
                if ($n==0 || $n < 0) {
                    unset($this->products[$pid]);
                }else{
                    $this->products[$pid]['n'] = $n;
                    if (!isset($p['prices']) || !is_array($p['prices'])) continue;
                    foreach ($p['prices'] as $ii=>$pr){
                        if (!isset($this->products[$pid]['prices'])) $this->products[$pid]['prices'] = array();
                        if (!isset($this->products[$pid]['prices'][$ii])) continue;
                        $this->products[$pid]['prices'][$ii]['unitprice'] = max(0,floatval($pr['unitprice']));
                        $this->products[$pid]['prices'][$ii]['min'] = max(0,floatval($pr['min']));
                        if ($pr['unitprice'] != $pr['unitprice_old'] || $pr['min'] != $pr['min_old'] && !empty($this->products[$pid]['prices'][$ii]['rowid']))
                            $update_doli_prices[$this->products[$pid]['prices'][$ii]['rowid']] = array('pid'=>$pid, 'sid'=>$this->products[$pid]['prices'][$ii]['sid'], 
                                'unitprice'=>$pr['unitprice'], 'min'=>$pr['min']);
                    }
                }
            }
            
        // == save at database
            $this->n_products = count($this->products);
            $result = $this->update();
        
            if ($result < 0) 
                dol_print_error($this->db,$this->error);
            else
                $_SESSION['EventMessages'][] = array("RecordModifiedSuccessfully",null,'mesgs');
            
        // == update doli buy prices if there are changes
            if (count($update_doli_prices)>0){
                foreach ($update_doli_prices as $rowid=>$arr){
                    $sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price "
                                               ." SET quantity=".floatval($arr['min'])
                                               .", unitprice=".floatval($arr['unitprice'])
                                               .", price=".(floatval($arr['unitprice']) * floatval($arr['min']))
                                               ." WHERE rowid=".$rowid;
                    $resql = $this->db->query($sql);
                    
                    if (! $resql) { dol_print_error($this->db,$this->error);}
                }
            }
        
    }
    
    /**
     *    \brief      Create an order with the selected products and supplier
     *    \param      vars        array containing different params. By now we only put in $rates array
     *    \return     int         <0 if KO, >0 if OK
     */
    function createOrder($vars)
    {
    	global $langs, $user, $conf;
        
        if (empty($_GET['sid'])) return;
        
        require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
            
        $supplier_id = intval($_GET['sid']);
        $rates = $vars['rates'];

        // == get the supplier
            $supplier = new Fournisseur($this->db);
            $ret = $supplier->fetch($supplier_id);
            if ($ret < 0){
                $_SESSION['EventMessages'][] = array("purchasesErrorMsg03",null,'errors');
                return -1;
            }

        // == get the products & prices to be ordered
            $order_products = array();
            foreach($this->products as $pid=>$p){
                if (!empty($p['order_id'])) continue;
                if (!isset($p['price_selected']) || trim($p['price_selected'])==''
                        || !isset($p['prices']) || !is_array($p['prices']) || count($p['prices'])==0
                        || !isset($p['prices'][$p['price_selected']])
                        || intval($p['prices'][$p['price_selected']]['sid'])!=$supplier_id) {
                    continue;
                }
                $order_products[$pid] = array_merge( array('pid'=>$pid, 'n'=>$p['n']) + $p['prices'][$p['price_selected']]);
            }
            
            if (count($order_products)==0) return -1;
            
        // == load products records
            $DBproducts = array();
            $resql = $this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."product WHERE rowid IN (".implode(',',array_keys($order_products)).")");
            if ($resql) {
                while($row = $resql->fetch_assoc()) $DBproducts[$row['rowid']] = $row;
            }

        // == create the order
            $this->db->begin();
            $order = new CommandeFournisseur($this->db);
            $order->socid = $supplier_id;
            $order_id = $order->create($user);
        
            if ($this->fk_project && $this->fk_project > 0)
            {
                $order->setProject($this->fk_project);
            }
            if ($supplier->multicurrency_code && !empty($supplier->multicurrency_code) )
            {
                $order->setMulticurrencyCode($supplier->multicurrency_code);
                if ($supplier->multicurrency_code != $conf->currency
                        && !empty($rates[$supplier->multicurrency_code])){
                    $order->setMulticurrencyRate($rates[$supplier->multicurrency_code]);
                }
            }

        // == add product lines to the order
            foreach($order_products as $pid=>$p)
            {
                $desc = !empty($DBproducts[$pid]['description']) ? $DBproducts[$pid]['description'] : $DBproducts[$pid]['label'];
                $qty = max(floatval($p['min']),floatval($p['n']));
                $localtax1_tx = 0; 
                $localtax2_tx = 0;
                $remise_percent = 0;
                $result = $order->addline($desc, floatval($p['unitprice']), $qty, floatval($p['tva']), $localtax1_tx, $localtax2_tx, $pid, $p['rowid']);
                
                if ($result < 0) {
                    $_SESSION['EventMessages'][] = array("purchasesErrorMsg04",null,'errors');
                    $_SESSION['EventMessages'][] = array($order->error,null,'errors');
                    $this->db->rollback();
                    return -1;
                }
            }
            $this->db->commit();
                        
        // == save the order_id to the products ordered, and the currency exchange rate
            foreach($order_products as $pid=>$p){
                $this->products[$pid]['order_id'] = $order_id;
                $this->products[$pid]['rates'] = array();
                if (!empty($supplier->multicurrency_code) && is_array($rates) && !empty($rates[$supplier->multicurrency_code])){
                    foreach($this->products[$pid]['prices'] as $ii=>$pr){
                        if (!empty($pr['curr']) && !empty($rates[$pr['curr']])){
                            $this->products[$pid]['prices'][$ii]['rate'] = $rates[$pr['curr']];
                            $this->products[$pid]['rates'][$pr['curr']] = $rates[$pr['curr']];
                        }
                    }
                }
            }
            
        // == change the status
            if (count($order_products)>0 && $this->status < '3'){
                // = check if there are products pending to be ordered
                    $n_products_no_ordered = 0;
                    foreach ($this->products as $p){
                        if (empty($p['order_id'])) $n_products_no_ordered++;
                    }
                // = change status
                    if ($n_products_no_ordered > 0)
                        $this->status = '2';
                    else
                        $this->status = '3';
            }
            $this->update();
            
            return $order_id;
            
    }
    
    /**
     *    \brief      Send email requests to suppliers to give quotation about some products included in this purchase process
     *    \param      
     *    \return     int         number of errors
     */
    function sendEmailRequests()
    {
    	global $langs, $user, $conf, $mysoc;
        
        // == load suppliers
            $suppliers = array();
            $resql = $this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur='1'");
            if ($resql) {
                while($row = $resql->fetch_assoc()) $suppliers[$row['rowid']] = $row;
            }

        // == load products
            $products = array();
            $resql = $this->db->query("SELECT rowid,label FROM ".MAIN_DB_PREFIX."product WHERE rowid IN (".implode(',',  array_keys($this->products)).")");
            if ($resql) {
                while($row = $resql->fetch_assoc()) $products[$row['rowid']] = $row;
            }

        // == build array of suppliers and the products needed from them (not emailed yet)
            $requests = array();
            if (count($this->products) > 0){
                foreach ($this->products as $pid=>$p){
                    if (isset($products[$pid]) && is_array($p['prices']) && count($p['prices'])>0
                            && empty($p['order_id'])){
                        foreach ($p['prices'] as $ii=>$pr){
                            if (!empty($pr['sid']) && isset($suppliers[$pr['sid']]) && !empty($suppliers[$pr['sid']]['email']) && empty($pr['emailed'])){
                                if (!isset($requests[$pr['sid']])) $requests[$pr['sid']] = array();
                                $requests[$pr['sid']][$pid] = max($p['n'], $pr['min']);
                                $this->products[$pid]['prices'][$ii]['emailed'] = '1';
                            }
                        }
                    }
                }
                // == save the 'emailed' changes 
                $this->update();
            }
        
        // == send emails

            if (count($requests)>0){
                
                include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
                
                $sent = array();
                $url = $this->getModuleURL();
                foreach ($requests as $sid=>$list){
                    // = target
                        if (!preg_match('/@/',$suppliers[$sid]['email'])) continue;
                        $target = $suppliers[$sid]['email'];
                    // = body
                        $url_lang = $suppliers[$sid]['default_lang'] && !empty($suppliers[$sid]['default_lang']) 
                                        && preg_match('/_/',$suppliers[$sid]['default_lang']) ? $suppliers[$sid]['default_lang'] : $langs->getDefaultlang();
                        $url_this = $url.'/public_request.php?'
                                    .'rid='.$this->rowid.'&mrid='.md5('pupy'.$this->rowid)
                                    .'&sid='.$sid.'&msid='.md5('pupy'.$sid).'&lang='.$url_lang;
                        $body = "<div style='font-family:sans-serif;'><p>".$langs->trans("purchasesEmailRequestPart1")."</p>";
                        $body .= "\n<ul>";
                        foreach ($list as $pid=>$n) $body .= "\n<li>$n <b>x</b> ".$products[$pid]['label']."</li>";
                        $body .= "\n</ul>";
                        $body .= "<p>".str_replace("<a>","<a href='$url_this'>",$langs->trans("purchasesEmailRequestPart2"))."</p>";
                        $body .= $this->emailFooter() . "</div>";
                    // = subject
                        $subject = html_entity_decode(str_replace('&#039;',"'",$langs->trans("purchasesQuotationMailSubject",$suppliers[$sid]['nom'])));
                    // = send email
                        $replyto = $conf->notification->email_from;
                        $isHTML = 1;
                        $mailfile = new CMailFile($subject, $target, $replyto, $body, array(),array(),array(),'','',0,$isHTML);
                        if ($mailfile->sendfile()){
                            $sent[] = $target;
                        }else{
                            $msg = $langs->trans("ErrorFailedToSendMail",$replyto,$target).':<br />'.$mailfile->error.'<br />';
                            $_SESSION['EventMessages'][] = array($msg,null,'errors');
                        }
                        unset($mailfile);
                }
                if (count($sent)>0) {
                    $_SESSION['EventMessages'][] = array($langs->trans("purchasesInfoMsg01",count($sent)).': '.implode(', ',$sent),null,'mesgs');
                }
            }
    }
    
    /**
     *    \brief      Save quotation data incoming from supplier quotation request form, and send email to admin user 
     *    \param      
     *    \return     int         number of errors
     */
    function saveQuotationForm()
    {
    	global $langs, $user, $conf;
        
        $error = 0;
        
        //echo _var_export($_GET,'$_GET')._var_export($_POST,'$_POST')._var_export($_FILES,'$_FILES'); echo _var_export($this->products,'$purchase->products'); die();
        
        // == save supplier data
            $vsupplier = array();
            if (!empty($_POST['supplier']['phone'])) $vsupplier[] = "phone='".$this->db->escape($_POST['supplier']['phone'])."'";
            if (!empty($_POST['supplier']['email']) && preg_match('/@/',$_POST['supplier']['email'])) $vsupplier[] = "email='".$this->db->escape($_POST['supplier']['email'])."'";
            if (!empty($_POST['supplier']['address'])) $vsupplier[] = "address='".$this->db->escape($_POST['supplier']['address'])."'";
            if (!empty($_POST['supplier']['zipcode'])) $vsupplier[] = "zip='".$this->db->escape($_POST['supplier']['zipcode'])."'";
            if (!empty($_POST['supplier']['town'])) $vsupplier[] = "town='".$this->db->escape($_POST['supplier']['town'])."'";
            if (count($vsupplier) > 0){
                $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET ".implode(',',$vsupplier)." WHERE rowid=".$_GET['sid'];
                $this->db->begin();
                dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if (!$resql) { 
                    $error++; $this->errors[]="Error ".$this->db->lasterror(); 
                    $this->db->rollback();
                }else{
                    $this->db->commit();
                }                    
            }
        
        // == save data regarding to products on purchase record (purchases module)
        $priceRecords = array();
        if (!empty($_POST['products']) && is_array($_POST['products']) && count($_POST['products'])>0){
            foreach ($_POST['products'] as $pid=>$p){
                if (!isset($this->products[$pid])) continue;
                if (!isset($this->products[$pid]['prices']) || !is_array($this->products[$pid]['prices']) || count($this->products[$pid]['prices'])==0) continue;
                if (!empty($this->products[$pid]['order_id'])) continue; // we don't save changes on products that has been already ordered
                foreach ($this->products[$pid]['prices'] as $ii=>$pr){
                    if (empty($pr['sid']) || empty($pr['rowid']) || $pr['rowid'] != $p['rowid']) continue;
                    if ($p['availability']=='1'){
                        $this->products[$pid]['prices'][$ii]['available'] = '1';
                        $this->products[$pid]['prices'][$ii]['unitprice'] = floatval($p['unitprice']);
                        $this->products[$pid]['prices'][$ii]['min'] = floatval($p['min']);
                        $this->products[$pid]['prices'][$ii]['days'] = floatval($p['days']);
                        $this->products[$pid]['prices'][$ii]['comment'] = trim(strip_tags($p['comment']));
                        
                        $priceRecords[$pr['rowid']] = $this->products[$pid]['prices'][$ii];
                    }else{
                        $this->products[$pid]['prices'][$ii]['available'] = '0';
                        if (floatval($p['unitprice'])>0) $this->products[$pid]['prices'][$ii]['unitprice'] = floatval($p['unitprice']);
                        if (floatval($p['min'])>0) $this->products[$pid]['prices'][$ii]['min'] = floatval($p['min']);
                        if ($p['days']!='') $this->products[$pid]['prices'][$ii]['days'] = floatval($p['days']);
                        $this->products[$pid]['prices'][$ii]['comment'] = trim(strip_tags($p['comment']));
                    }
                }
            }
            $this->update();
        }

        // == save data regarding to products on supplier record price for each product (product_fournisseur_price, Dolibarr table)
            if (count($priceRecords)>0){
                
                $this->db->begin();
                
                foreach ($priceRecords as $pr_rowid=>$pr){
                    $unitprice = floatval($pr['unitprice']);
                    $min = floatval($pr['min']);
                    $days = $pr['days']!='' ? intval($pr['days']) : '';
                    $sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price SET "
                            ." unitprice=".$unitprice.","
                            ." price=".($unitprice*$min).","
                            .($days!='' ? " delivery_time_days=".$days."," : "")
                            ." quantity=".$min
                            ." WHERE rowid=".$pr_rowid;
                    $resql = $this->db->query($sql);
                    if (!$resql) { $error++; $this->errors[]="Error ".$this->db->lasterror();}
                }
                
                if ($error){
                        $this->db->rollback();
                }else{
                        $this->db->commit();
                }                    
            }
                    
        // == send email to the user managing this purchase
            $user_purchase = $this->getUser();
            if (!empty($user_purchase['email']) && preg_match('/@/',$user_purchase['email'])){
                // == prepare: get supplier data
                    $supplier = array();
                    $resql = $this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE rowid=".$_GET['sid']);
                    if ($resql) {
                        while($row = $resql->fetch_assoc()) $supplier = $row;
                    }
                    $curr = !empty($supplier['multicurrency_code']) ? $supplier['multicurrency_code'] : $conf->currency;
                // = target
                    $target = $user_purchase['email'];
                // = body
                    $url_this = $this->getModuleURL().'/purchase_edit.php?mainmenu=commercial&leftmenu=&rowid='.$this->rowid;
                    $phone = !empty($supplier['phone']) ? ' ('.$supplier['phone'].')' : '';
                    $body = "<div style='font-family:sans-serif;'><p>".$langs->trans("purchasesEmailAlertPart1",'<b>'.$supplier['nom'].'</b>',$phone)."</p>";
                    $body .= "\n<ul>";
                    foreach ($_POST['products'] as $pid=>$p){
                        if ($p['availability']=='1'){
                            $body .= "\n<li style='margin:1rem;'>".  floatval($p['n'])." <b>x</b> ".$p['label']
                                    ."<br />Precio unitario: <b>".number_format(floatval($p['unitprice']),2,'.',',')."</b> ".$curr
                                    ." (&rarr; ".number_format(floatval($p['n'])*floatval($p['unitprice']),2,'.',',')." $curr)"
                                    .(!empty($p['comment']) ? '<br />'.$langs->trans('purchasesInfoMsg03').': <b>'.$p['comment'].'</b>' : '')."</li>";
                        }else{
                            $body .= "\n<li style='margin:1rem;'><span style='text-decoration:line-through;'>".  floatval($p['n'])." <b>x</b> ".$p['label']."</span>"
                                    .(!empty($p['comment']) ? '<br />'.$langs->trans('purchasesInfoMsg03').': <b>'.$p['comment'].'</b>' : '')."</li>";
                        }
                    }
                    $body .= "\n</ul>";
                    if (!empty($_POST['comment'])){
                        $body .= "\n<p><b>".$langs->trans('purchasesInfoMsg03').":</b></p><blockquote>".$_POST['comment']."</blockquote>";
                    }
                    $body .= "<p>".str_replace("<a>","<a href='$url_this'>",$langs->trans("purchasesEmailAlertPart2"))."</p>";
                    $body .= $this->emailFooter() . "</div>";

                // = subject
                    $subject = html_entity_decode(str_replace('&#039;',"'",$langs->trans("purchasesQuotationAlertMailSubject",$supplier['nom'])));
                // = send email
                    $replyto = $conf->notification->email_from;
                    $isHTML = 1;
                    include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
                    $mailfile = new CMailFile($subject, $target, $replyto, $body, array(),array(),array(),'','',0,$isHTML);
                    $mailfile->sendfile();
                    unset($mailfile);
            }
            
        return $error;
    }
    
    /**
     *		\brief		Unserialize s_products
     */
    function unserializeProducts()
    {
        $products = array();
        if (!empty($this->s_products)){
            $arr = @unserialize($this->s_products);
            if (is_array($arr)){
                foreach ($arr as $arr2){
                    if (!empty($arr2['pid'])) $products[$arr2['pid']] = $arr2;
                }
            }
        }
        $this->products = $products;
        $this->n_products = count($products);
    }
   
    /*
     * get admin user of a purchase
     */
    function getUser(){
        $user_purchase = array();
        $resql = $this->db->query("SELECT * FROM ".MAIN_DB_PREFIX."user WHERE rowid=".$this->fk_user_author);
        if ($resql) {
            while($row = $resql->fetch_assoc()) $user_purchase = $row;
        }
        return $user_purchase;
    }
    
    /*
     * return a footer for emails
     */
    function emailFooter(){
        global $conf, $mysoc;
        
        $footer = "<p>&nbsp;</p><table cellpadding='15' style='border-top:3px #ccc solid;border-bottom:3px #ccc solid;'><tr><td>";
        
        if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini)) {
            $footer .= '<img src="'.str_replace('/purchases','',$this->getModuleURL()).'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('/thumbs/'.$mysoc->logo_mini).'">';
        }
            
        $footer .= '</td><td>'
                    .'<b>'.$conf->global->MAIN_INFO_SOCIETE_NOM.'</b>'
                    .($conf->global->MAIN_INFO_SOCIETE_ADDRESS != '' ? '<br />'.$conf->global->MAIN_INFO_SOCIETE_ADDRESS : '')
                    .($conf->global->MAIN_INFO_SOCIETE_TEL != '' ? '<br />'.$conf->global->MAIN_INFO_SOCIETE_TEL : '')
                    .($conf->global->MAIN_INFO_SOCIETE_MAIL != '' ?'<br />'.$conf->global->MAIN_INFO_SOCIETE_MAIL: '')
                    .'</td></tr></table>';
        return $footer;
    }
    
    /*
     * return absolute URL for this module
     * it returns an address without a last slash: https://dolibar.me/purchases
     */
    function getModuleURL(){
        return  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://').
                (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
                (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
                ($https && $_SERVER['SERVER_PORT'] === 443 ||
                    $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
                    substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }
    
}
?>
