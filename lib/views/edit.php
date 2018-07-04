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
 *	\file       htdocs/purchases/lib/views/edit.php
 *      \defgroup   Purchases
 *      \brief      Page to edit a purchase
 *      \version    v 1.0 2017/11/20
 */

/***************************************************
 * 
 *	Prepare data
 * 
****************************************************/

    // == misc
        $now=dol_now();
        $socid = $user->societe_id > 0 ? $user->societe_id : 0;
                
        $form = new Form($db);
        $formproduct = new FormProduct($db);
        if (!empty($conf->projet->enabled))  
            $formproject = new FormProjets($db);
        else 
            $formproject = null;
        $productstatic = new Product($db);
        $orderstatic = new CommandeFournisseur($db);

    // == set order and limit for queries
        $sortfield = GETPOST('sortfield','alpha');
        $sortorder = GETPOST('sortorder','alpha');
        if (!$sortfield) {
            $sortfield = 'p.ref';
        }
        if (!$sortorder) {
            $sortorder = 'ASC';
        }
        
        $limit = GETPOST('limit','int');
            if (empty($limit) || !is_numeric($limit) || $limit < 1) $limit = $conf->liste_limit ;
            
        $page = GETPOST("page",'int');
            if (empty($page) || !is_numeric($page) || $page <0) $page = 0 ;
        
        $offset = $limit * $page ;
        
        if (! empty($conf->global->STOCK_SUPPORTS_SERVICES)) $filtertype='';
        $limit = $conf->global->PRODUIT_LIMIT_SIZE <= 0 ? '' : $conf->global->PRODUIT_LIMIT_SIZE;

    // == load payment types
        $form->load_cache_types_paiements();
        $paytypes = $form->cache_types_paiements;
        
    // == load payment types
        $langs->load('bills');
        $form->load_cache_conditions_paiements();
        $payconditions = $form->cache_conditions_paiements;
        
    // == load currencies and ex-change types with default currency
        $currencies = array();
        $resql = $db->query("SELECT *, m.rowid as id FROM ".MAIN_DB_PREFIX."multicurrency as m JOIN ".MAIN_DB_PREFIX."multicurrency_rate as r ON (m.rowid = r.fk_multicurrency)");
        if ($resql) {
            while($row = $resql->fetch_assoc()){ 
                // = we preferently take the update exchange rate value incoming from the IMF (International Monetary Fund), which is updated each 12h 
                if (isset($rates[$row['code']])) 
                    $currencies[$row['code']] = round( 1 / $rates[$row['code']] , 2 );
                // = if not, then we take the rate value saved at database (lls_multicurrency_rate)
                else
                    $currencies[$row['code']] = round( 1 / floatval($row['rate']) , 2);
                // = Note: in any case, these rates are used only for "not closed" products, ie. until is not created an order for that product. 
                // ------- When is created an order for a product, we save the current rate (frozen rate). 
                // ------- In this way we can look this purchase several months in the future and see the rate in the moment of the order.
            }
        }

    // == load suppliers
        $suppliers = array();
        $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE fournisseur='1' ORDER BY nom");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $suppliers[$row['rowid']] = $row;
        }
        
    // == load prices for these products from all suppliers
        $pricesBYsupplier = array();
        if (count($purchase->products) > 0){
            $resql = $db->query("SELECT fk_product,fk_soc FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product IN (".implode(',',array_keys($purchase->products)).")");
            if ($resql) {
                while($row = $resql->fetch_assoc()){ 
                    if (!isset($pricesBYsupplier[$row['fk_product']])) $pricesBYsupplier[$row['fk_product']] = array();
                    $pricesBYsupplier[$row['fk_product']][$row['fk_soc']] = 1;
                }
            }
        }

    // == load orders linked to this purchase & count how many products there are without being ordered
        $ordersIDs = array();
        $n_products_no_ordered = 0;
        if (count($purchase->products) > 0){
            foreach($purchase->products as $pid=>$p){
                if (!empty($p['order_id'])) $ordersIDs[$p['order_id']] = 1;
                else $n_products_no_ordered++;
            }
        }
        $orders = array();
        if (count($ordersIDs) > 0){
            $resql = $db->query("SELECT rowid,fk_statut,ref FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE rowid IN (".implode(',',array_keys($ordersIDs)).")");
            if ($resql) {
                while($row = $resql->fetch_assoc()){ 
                    $orders[$row['rowid']] = $row;
                }
            }
        }
        
/***************************************************
 * 
 *	View
 * 
****************************************************/
        
    // == browser top title
        $title = $langs->trans('purchasesBriefTitle');
        llxHeader('',$title);
        
    // == misc.
        $moreforfilter = true;
        $var = true;
        $param = '';
        $filtertype=0;
        
?>

<!-- ========= header with section title ========= -->

<?= load_fiche_titre( ($purchase->rowid > 0 ? $langs->trans('purchasesPurchase') : $langs->trans('purchasesNewPurchase')),
                    '<a href="purchase_list.php?mainmenu=commercial&leftmenu=" class="button">'.$langs->trans('purchasesMenuTitle3').'</a>',
                    'title_commercial.png') ?>

<div class='tabBar'>
    
    <!-- ========= Form with the purchase details (status, project, etc.) ========= -->

    <form action="purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=<?= $purchase->rowid ? $purchase->rowid : '' ?>" 
          method="POST" name="purchase_card_form" id="purchase_card_form">
        <input type="hidden" name="token" value="<?= $_SESSION['newtoken'] ?>" />
        <input type="hidden" name="rowid" value="<?= !empty($purchase->rowid) ? $purchase->rowid : '' ?>" />
        <input type="hidden" name="action" value="save_card" />
        <input type="hidden" name="status" value="<?= $purchase->status ?>" />
        <input type="hidden" name="old_status" value="<?= $purchase->status ?>" />

        <?php
            $codemove=GETPOST('codemove');
            $labelmovement = GETPOST("label") ? GETPOST('label') : $langs->trans("StockTransfer").' '.dol_print_date($now,'%Y-%m-%d %H:%M');
        ?>
        
        <div class="underbanner clearboth"></div>
        
        <table class="border" style="width:100%;">
            <?php if ($purchase->rowid > 0){ ?>
            <tr>
                <td class="titlefield fieldrequired">Id</td>
                <td>#<?= $purchase->rowid ?></td>
            </tr>
            <?php } ?>
            <tr>
                <td class="titlefield"><?= $langs->trans("Label") ?></td>
                <td>
                    <input type="text" name="label" style="min-width:50%;" maxlength="128" 
                           value="<?= $purchase->row_id == 0 ? $langs->trans('purchasesPurchase').' '.date('d/m/Y H:i') : dol_escape_htmltag($transfer->label) ?>">
                </td>
            </tr>
            <?php if (!empty($conf->projet->enabled)) { ?>
            <tr>
                <td class="titlefield"><?= $langs->trans('Project') ?></td>
                <td><?= $formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS) ? $socid :    -1), $purchase->fk_project, 'fk_project', 0, 0, 1, 1) ?></td>
            </tr>
            <?php } ?>
            <tr>
                <td class="titlefield"><?= $langs->trans("Note") ?></td>
                <td>
                    <textarea name="note" style="min-width:80%;height:75px;"><?= strip_tags($purchase->note) ?></textarea>
                </td>
            </tr>
            <?php if ($purchase->rowid > 0){ ?>
            <tr>
                <td class="titlefield fieldrequired"><?= $langs->trans("Status") ?></td>
                <td>
                    <?php
                        $status_picto = array('0'=>'0','1'=>'1','2'=>'3','3'=>'4');
                        print img_picto('','statut'.$status_picto[$purchase->status]). ' '. $langs->trans('purchasesStatus'.$purchase->status) ?>
                </td>
            </tr>
            <?php } ?>
        </table>
        
        <!-- =========== buttons ======== -->

        <br />
        <div class="right">
            
            <!-- save button -->
            <a href="#" class="button" onclick="js_validate_form('purchase_card_form');return false;"><?= $purchase->rowid > 0 ? dol_escape_htmltag($langs->trans('Save')) : dol_escape_htmltag($langs->trans('purchasesCreateButton')) ?></a>
            
            <!-- request quotation by email button -->
            <?php if ($purchase->rowid > 0 && $purchase->n_products > 0 && $n_products_no_ordered > 0){ ?>
            <a href="#" class="button" onclick="js_request_preview();return false;"><?= dol_escape_htmltag($langs->trans('purchasesSendQuoteEmails')) ?></a>
            <?php } ?>
            
            <!-- create purchase orders button -->
            <?php if ($purchase->rowid > 0 && $purchase->n_products > 0 && $n_products_no_ordered > 0){ ?>
            <a href="#" class="button" onclick="js_create_purchase_orders();return false;"><?= dol_escape_htmltag($langs->trans('purchasesCreateOrders')) ?></a>
            <?php } ?>
            
            <!-- delete button -->
            <?php if ($purchase->rowid > 0){ ?>
            <a href="#" class="button butActionDelete" onclick="js_delete_purchase();return false;"><?= dol_escape_htmltag($langs->trans('Delete')) ?></a>
            <?php } ?>
            
            <!-- =========== easter egg - to show the raw data of the element (mainly for dev debug) ======== -->
            
            <a href="#" onclick="return false;" ondblclick="$('#purchases_easter_egg').toggle();return false;" style="text-decoration:none;">&nbsp; &nbsp;</a>
            <div id="purchases_easter_egg" style="text-align: left;margin:2rem;display:none;" class="block">
                <?php 
                        $element_fields = array('rowid','ts_create','fk_user_author','label','note','fk_project','status','s_products','n_products'); 
                        $element = array();
                        foreach ($element_fields as $f) $element[$f] = $purchase->{$f};
                        echo _var_export($element);
                ?>
            </div>
        
        </div>
        
    </form>
    
    <!-- ========= Email confirmation dialog ========= -->
    
    <div id="email_confirmation" class="block" style="display:none;text-align:center;margin:2rem 0;">
        <form id="email_request_form" action="purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=<?= $purchase->rowid ? $purchase->rowid : '' ?>" method="POST">
            <input type="hidden" name="token" value="<?= $_SESSION['newtoken']  ?>">
            <input type="hidden" name="action" value="send_email_requests">

            <table cellspacing='0' cellpadding="5" style="width:auto;margin:1rem auto;text-align:left;">
                <thead>
                    <tr class="liste_titre">
                        <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesSupplier') ?></td>
                        <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesProducts') ?></td>
                        <td class="tagtd maxwidthonsmartphone" style='text-align:center;'><?= $langs->trans('purchasesEmailAddress') ?></td>
                        <td class="tagtd maxwidthonsmartphone">&nbsp;</td>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            <p style='text-align:center;'>
                <a href="#" class="button" onclick="$('#email_request_form').submit();return false;"><?= dol_escape_htmltag($langs->trans('purchasesSendQuoteEmails')) ?></a>
            </p>
        </form>
    </div>
    
    <!-- ========= Order confirmation dialog ========= -->
    
    <div id="order_confirmation" class="block" style="display:none;text-align:center;margin:2rem 0;">
        <table cellspacing='0' cellpadding="5" style="width:auto;margin:1rem auto;text-align:left;">
            <thead>
                <tr class="liste_titre">
                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesSupplier') ?></td>
                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesProducts') ?></td>
                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesAmount') ?></td>
                    <td class="tagtd maxwidthonsmartphone">&nbsp;</td>
                    <td class="tagtd maxwidthonsmartphone" style='text-align:center;'><?= $langs->trans('purchasesAction') ?></td>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    
    <!-- ========= list of products to purchase ========= -->

    <?php if ($purchase->rowid > 0){ ?>
    
    <br />
    <form action="purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=<?= $purchase->rowid ? $purchase->rowid : '' ?>" method="POST" id="purchase_product_form">
        <input type="hidden" name="token" value="<?= $_SESSION['newtoken']  ?>">
        <input type="hidden" name="rowid" value="<?= !empty($purchase->rowid) ? $purchase->rowid : '' ?>" />
        <input type="hidden" name="action" value="add_line">
        <input type="hidden" name="product_id" value="">
        <input type="hidden" name="supplier_id" value="">
        <input type="hidden" name="price_ii" value="">
        <input type="hidden" name="unitprice" value="">

        <div class="titre" style="margin-bottom:0.5rem;margin-top:1rem;"><?= $langs->trans('purchasesProductTable') ?></div>
        <p style="text-align:right;">
            <input type="checkbox" id='hide_prices' onchange="js_toggle_hide_prices();" <?= $n_products_no_ordered==0 ? "checked='checked'" : "" ?> /> &nbsp; 
            <a href='#' onclick="$(this).prev().click();return false;" style='outline:none;'><?= $langs->trans('purchasesHidePriceTables') ?></a></p>
        
        <div class="div-table-responsive-no-max">
            <table class="liste">
                <tr class="liste_titre">
                <?php 
                    print getTitleFieldOfList($langs->trans('Status'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    print getTitleFieldOfList($langs->trans('ProductRef'),0,$_SERVER["PHP_SELF"],'',$param,'','class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    print getTitleFieldOfList($langs->trans('Qty'),0,$_SERVER["PHP_SELF"],'',$param,'','align="center" class="tagtd maxwidthonsmartphone"',$sortfield,$sortorder);
                    print getTitleFieldOfList('',0);
                ?>
                </tr>

                <!-- ========= boxes to add a new line ========= -->

                <?php if ($purchase->status < '3' ){ ?>
                
                <tr id="new_line">
                    
                    <!-- ========= status ========= -->
                    <td class="titlefield" style="width:30px;">
                        &nbsp;
                    </td>
                    
                    <!-- ========= product ========= -->
                    <td class="titlefield">
                        <?= $form->select_produits(!empty($_POST['pid']) ? $_POST['pid'] : '', 'pid', $filtertype, $limit, 0, -1, 2, '', 0, array(), 0, '1', 0, 'maxwidth400', 1) ?>
                    </td>
                    
                    <!-- ========= Quantity ========= -->
                    <td style='text-align:center;'>
                        <input type="text" size="3" class="flat MustBeDecimal" name="n" value="<?= !empty($_POST['n']) ? $_POST['n'] : '' ?>">
                    </td>
                    
                    <!-- ========= Button to add ========= -->
                    <td style='text-align:left;'>
                        <input type="submit" class="button" value="<?= dol_escape_htmltag($langs->trans("purchasesAddProduct")) ?>">
                    </td>
                </tr>
                
                <?php } ?>

                <!-- ========= List of current added lines ========= -->
                
                <?php foreach($purchase->products as $pid => $p){

                    $productstatic->fetch($pid);
                    $current_suppliers = array();
                    if (!empty($p['order_id']))
                        $p_status = '3';
                    else if (isset($p['prices']) && is_array($p['prices']) && count($p['prices'])>0)
                        $p_status = '2';
                    else
                        $p_status = '1';
                    
                ?>

                <!-- ==== SEPARATOR row === -->
                <tr><td colspan="4"><div class="underbanner clearboth"></div></td></tr>
                
                <!-- ==== row with PRODUCT profile === -->
                <tr id='product_<?= $pid ?>_1' data-pid='<?= $pid ?>' data-rel='product_tr' data-pr-ordered='<?= !empty($p['order_id']) ? intval($p['order_id']) : '' ?>'>
                    <td style='text-align:center;'>
                        <span class='classfortooltip product_status_<?= $p_status ?>' title="<?= htmlentities($langs->trans('purchasesProductStatus'.$p_status)) ?>">&nbsp;</span>
                    </td>
                    <td>
                        <a href="<?= DOL_URL_ROOT ?>/product/fournisseurs.php?id=<?= $pid ?>" target='_blank'><?= img_picto('','object_product') ?></a> 
                        <a href="<?= DOL_URL_ROOT ?>/product/fournisseurs.php?id=<?= $pid ?>" target='_blank'><?= $productstatic->ref ?></a>
                        - <span data-f='product_label'><?= $productstatic->label ?></span>
                    </td>
                    <td style='text-align:center;'>
                        <?php if(empty($p['order_id'])){ ?>
                        <input type="text" name="products[<?= $pid ?>][n]" value="<?= $p['n'] ?>" data-f='n' class="MustBeDecimal classfortooltip" 
                               title="<?= str_replace('"','',$langs->trans('Qty')) ?>" style="text-align:right;width:4rem;" />
                        <?php }else{ ?>
                        <input type="hidden" name="products[<?= $pid ?>][n]" value="<?= $p['n'] ?>" data-f='n' />
                        <b><?= $p['n'] ?></b>
                        <?php } ?>
                        <input type="hidden" id="order_id_<?= $pid ?>" name="products[<?= $pid ?>][order_id]" value="<?= !empty($p['order_id']) ? intval($p['order_id']) : '0' ?>" />
                    </td>
                    <td>
                        <?php if (empty($p['order_id'])){ ?>
                        
                        <a href="#" onclick="js_del_line('<?= $pid ?>');return false;" style="display:inline-block;height:25px;float:left;margin:0px 4px;">
                            <?= img_delete($langs->trans("Remove"),'class="classfortooltip"') ?></a>
                        
                        <a href="#" onclick="js_save_lines('<?= $pid ?>');return false;" style="display:inline-block;height:25px;float:left;margin:0px 4px;">
                            <?= img_picto($langs->trans("Save"),'object_energy','class="classfortooltip"') ?></a>
                        <a href="<?= DOL_URL_ROOT ?>/product/stock/product.php?id=<?= $pid ?>" target="_blank" style="display:inline-block;height:25px;float:left;margin:0px 4px;">
                            <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/object_stock.png" title="<?= str_replace('"','',$langs->trans("purchasesCheckStock")) ?>" class="classfortooltip" />
                            
                        <?php }else if (isset($orders[$p['order_id']])){ ?>
                            
                            <a href="<?= DOL_URL_ROOT ?>/fourn/commande/card.php?id=<?= $p['order_id'] ?>" class="button" target="_blank"><?= $langs->trans('purchasesSeeOrder').' <b>'.$orders[$p['order_id']]['ref'].'</b>' ?></a>
                            <?= $orderstatic->LibStatut(intval($orders[$p['order_id']]['fk_statut']), 3, 0) ?>
                            <?= $orderstatic->LibStatut(intval($orders[$p['order_id']]['fk_statut']), 1, 0) ?>
                            
                        <?php }else{ ?>
                            
                            <span class="button" style="background:#888!important;" onclick="return false;"><?= $langs->trans('purchasesDeletedOrder',$p['order_id']) ?></span>
                            
                        <?php } ?>
                    </td>
                </tr>
                
                <!-- ==== row with suppliers offers === -->
                <tr id='product_<?= $pid ?>_2' data-pid='<?= $pid ?>' data-rel='prices_tr' <?= $n_products_no_ordered==0 ? "style='display:none;'" : "" ?>>
                    <td colspan="4">
                        <div class="block" style="width:90%;margin:0.5rem auto;position:relative;">
                            <?php if (!empty($p['order_id'])){ ?>
                            <div style='background-color:rgba(155,155,155,0.4);position:absolute;width:100%;height:100%;top:0;left:0;'></div>
                            <?php } ?>
                            <table cellspacing='0'>
                                <tr class="liste_titre">
                                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesSupplier') ?></td>
                                    <td class="tagtd maxwidthonsmartphone">
                                        <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" title="<?= str_replace('"','',htmlentities($langs->trans('purchasesPayCondBriefTip'))) ?>" />
                                        <?= $langs->trans('purchasesPayCondBrief') ?>
                                    </td>
                                    <td class="tagtd maxwidthonsmartphone">
                                        <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" title="<?= str_replace('"','',htmlentities($langs->trans('purchasesPayCondBriefTip'))) ?>" />
                                        <?= $langs->trans('purchasesPayTypeBrief') ?>
                                    </td>
                                    <td class="tagtd maxwidthonsmartphone">
                                        <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" title="<?= str_replace('"','',htmlentities($langs->trans('purchasesPayCondBriefTip'))) ?>" />
                                        <?= $langs->trans('purchasesDays') ?>
                                    </td>
                                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesUnitPrice') ?></td>
                                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesMinQuant') ?></td>
                                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesUnits') ?></td>
                                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesFinalPrice') ?></td>
                                    <td>&nbsp;</td>
                                    <td class="tagtd maxwidthonsmartphone"><?= $langs->trans('purchasesCreateOrder') ?></td>
                                </tr>
                                <?php if (isset($p['prices']) && is_array($p['prices']) && count($p['prices'])>0){ 
                                        foreach ($p['prices'] as $ii => $pr){ 
                                            if (!isset($suppliers[$pr['sid']])) continue; 
                                            $current_suppliers[$pr['sid']] = 1;
                                            if (empty($suppliers[$pr['sid']]['tooltip'])){
                                                $suppliers[$pr['sid']]['tooltip'] = "<b><u>".$suppliers[$pr['sid']]['nom']."</u></b> "
                                                        .(!empty($suppliers[$pr['sid']]['name_alias']) ? ' - '.$suppliers[$pr['sid']]['name_alias'].')':'').""
                                                        .(!empty($suppliers[$pr['sid']]['siren']) ? '('.$suppliers[$pr['sid']]['siren'].')':'').""
                                                        ."<ul><li><b>".$langs->trans('Phone').":</b> ".$suppliers[$pr['sid']]['phone']."</li>"
                                                        ."<li><b>".$langs->trans('Email').":</b> ".$suppliers[$pr['sid']]['email']."</li>"
                                                        ."<li><b>".$langs->trans('Address').":</b> ".$suppliers[$pr['sid']]['zip']." ".$suppliers[$pr['sid']]['town']."<br />".$suppliers[$pr['sid']]['address']."</li></ul>";
                                            }
                                            $curr = !empty($pr['curr']) ? $pr['curr'] : '-';
                                            $rate = $conf->multicurrency->enabled && !empty($curr) && !empty($currencies[$curr]) ? $currencies[$curr] : '1';
                                            if (!empty($pr['rate'])) $rate = 1 / floatval($pr['rate']);
                                            if (isset($pr['available']) && $pr['available']=='1'){
                                                $style_available = "font-weight:bold;color:#0c0;";
                                            }else if (isset($pr['available']) && $pr['available']=='0'){
                                                $style_available = "font-weight:bold;color:#c00;";
                                            }else if (!empty($pr['emailed'])){
                                                $style_available = "font-weight:bold;color:orange;";
                                            }else{
                                                $style_available = "";
                                            }
                                ?>
                                <tr class='tr_price' id='product_<?= $pid ?>_pr_<?= $ii ?>' style='<?= !empty($p['order_id']) && $ii == $p['order_id'] ? 'background-color:#bfb;' : ''?>'
                                        data-pr-ii="<?= $ii ?>" data-pid="<?= $pid ?>" data-sid="<?= $pr['sid'] ?>" data-msid="<?= md5('pupy'.$pr['sid']) ?>" 
                                        data-n="<?= max(floatval($p['n']),floatval($pr['min'])) ?>"
                                        data-price="<?= max(floatval($p['n']),floatval($pr['min'])) * round(floatval($pr['unitprice']),2) ?>"
                                        data-price-formatted="<?= number_format(max(floatval($p['n']),floatval($pr['min'])) * round(floatval($pr['unitprice']),2),2,'.',',') ?>"
                                        data-curr="<?= $pr['curr'] ?>" data-rate="<?= $conf->multicurrency->enabled && !empty($curr) && !empty($currencies[$curr]) ? $currencies[$curr] : '1' ?>"
                                        data-emailed="<?= !empty($pr['emailed']) ? '1' : '0' ?>"
                                        data-email="<?= !empty($suppliers[$pr['sid']]['email']) ? $suppliers[$pr['sid']]['email'] : '' ?>"
                                        data-lang="<?= !empty($suppliers[$pr['sid']]['default_lang']) ? $suppliers[$pr['sid']]['default_lang'] : '' ?>"
                                    >
                                    <td>
                                        <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" title="<?= htmlentities($suppliers[$pr['sid']]['tooltip']) ?>" />
                                        <a href='<?= DOL_URL_ROOT ?>/fourn/card.php?socid=<?= $pr['sid'] ?>' target='_blank'>
                                            <span data-f='supplier_label'><?= $suppliers[$pr['sid']]['nom']  ?></span>
                                        </a>
                                    </td>
                                    <td><?= $payconditions[$suppliers[$pr['sid']]['cond_reglement_supplier']]['label'] ?></td>
                                    <td><?= $paytypes[$suppliers[$pr['sid']]['mode_reglement_supplier']]['label'] ?></td>
                                    <td style="text-align: center;"><?= !empty($pr['days']) ? $pr['days'] : '?' ?></td>
                                    <td style="text-align: left;white-space: nowrap;">
                                        <input type="text" name="products[<?= $pid ?>][prices][<?= $ii ?>][unitprice]" class="MustBeDecimal"
                                               data-f='unitprice' data-curr='<?= $curr ?>' data-rate='<?= $rate ?>' 
                                               value="<?= price2num($pr['unitprice']) ?>" style="text-align:right;width:3.5rem;<?= $style_available ?>" />
                                        <input type="hidden" name="products[<?= $pid ?>][prices][<?= $ii ?>][unitprice_old]" value="<?= price2num($pr['unitprice']) ?>" />
                                        <?= $conf->multicurrency->enabled ? '<span style="color:#888;">'.$curr.'</span>' : '' ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="text" name="products[<?= $pid ?>][prices][<?= $ii ?>][min]" data-f='min' value="<?= floatval($pr['min']) ?>" 
                                               class="MustBeDecimal" style="text-align:right;width:4rem;<?= $style_available ?>" />
                                        <input type="hidden" name="products[<?= $pid ?>][prices][<?= $ii ?>][min_old]" value="<?= floatval($pr['min']) ?>" />
                                    </td>
                                    <td style="text-align: center;"><span data-f='n'></span></td>
                                    <td style="text-align: right;white-space:nowrap;">
                                        <span data-f='finalprice'></span>
                                        <?= $conf->multicurrency->enabled ? '<span style="color:#888;">'.$conf->currency.'</span>' : '' ?>
                                    </td>
                                    <td>
                                        <a href="#" onclick="js_del_line_supp('<?= $pid ?>','<?= $ii ?>');return false;" style="display:inline-block;height:25px;float:left;margin:0px 2px;">
                                            <?= img_delete($langs->trans("purchasesButton01"),'class="classfortooltip"'. (isset($pr['available']) && $pr['available']=='0' ? ' style="background-color:rgba(255,0,0,0.5);"' : '' )) ?></a>
                                        
                                        <?php if (!empty($pr['rowid'])){ ?>
                                        <a href="<?= DOL_URL_ROOT ?>/product/fournisseurs.php?id=<?= $pid ?>&socid=<?= $pr['sid'] ?>&action=add_price&rowid=<?= $pr['rowid'] ?>" style="display:inline-block;height:25px;float:left;margin:0px 2px;" target='_blank'>
                                            <?= img_edit($langs->trans("purchasesEditBuyPrice"),'class="classfortooltip"') ?></a>
                                        <?php } ?>
                                        
                                        <?php if (!empty($suppliers[$pr['sid']]['email'])){ ?>
                                        <a href="mailto:<?= htmlentities($suppliers[$pr['sid']]['email']) ?>" style="display:inline-block;height:25px;float:left;margin:0px 2px;" target='_blank'>
                                            <?= img_picto($langs->trans("purchasesSendEmail").' ('.$suppliers[$pr['sid']]['email'].')','object_email','class="classfortooltip"') ?></a>
                                        <?php } ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if (isset($pr['available']) && $pr['available']=='0'){ ?>
                                        
                                            <!-- explanation of not availability & comment from supplier -->
                                            
                                            <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" 
                                                title="<?= htmlentities($langs->trans('purchasesInfoMsg02') . (!empty($pr['comment']) ? '<br /><br />'.$langs->trans('purchasesInfoMsg03').': <b>'.$pr['comment'].'</b>' : '')) ?>" />
                                            
                                        <?php }else{ ?>
                                            
                                            <!-- comment from supplier -->
                                            
                                            <?php if (!empty($pr['comment'])){ ?>
                                            <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-1px;" 
                                                title="<?= htmlentities($langs->trans('purchasesInfoMsg03').': <b>'.$pr['comment'].'</b>') ?>" />
                                            <?php } ?>
                                            
                                            <!-- input radio to create order -->
                                            
                                            <?php if (isset($pr['unitprice']) && floatval($pr['unitprice'])>0
                                                && (!isset($pr['available']) || $pr['available']=='1')){ ?>
                                            <input type="radio" name="create_order_<?= $pid ?>" id="create_order_<?= $pid ?>_<?= $ii ?>" style="outline:none;" 
                                                data-pr-ii="<?= $ii ?>" data-pid="<?= $pid ?>" 
                                               <?= isset($p['price_selected']) && $p['price_selected']!='' && $p['price_selected'] == $ii ? "checked='checked'" : "" ?> />
                                            <?php } ?>
                                            
                                        <?php } ?>
                                        
                                    </td>
                                </tr>
                                <?php }}else{ ?>
                                <tr>
                                    <td colspan="10">
                                        <?= $langs->trans('purchasesNoSuppliers') ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </table>
                            
                            <!-- ==== add another supplier === -->
                            <?php if (empty($p['order_id']) && count($suppliers) > count($current_suppliers)){ ?>
                            <p>
                                <select id='supplier_id_<?= $pid ?>' onchange="js_selected_supplier_to_add_price('<?= $pid ?>');">
                                    <option value=''></option>
                                    <?php foreach($suppliers as $sid=>$sup){ 
                                            if (isset($current_suppliers[$sid])) continue;
                                            $existing_price = isset($pricesBYsupplier[$pid][$sid]) ? true : false;
                                            if ($conf->multicurrency->enabled)
                                                $sup_curr =  !empty($sup['multicurrency_code']) ? trim($sup['multicurrency_code']) : $conf->currency;
                                            else
                                                $sup_curr = '';
                                    ?>
                                    <option value="<?= $sid ?>" data-curr='<?= $sup_curr ?>' data-existing-price="<?= $existing_price ? '1':'0' ?>">
                                            <?= ($existing_price ? '* ':'') .$sup['nom']. (!empty($sup_curr) ? ' ('.$sup_curr.')':'') ?></option>
                                    <?php } ?>
                                </select> &nbsp;
                                <span id='span_unitprice_<?= $pid ?>' style="display:none;">
                                    <b><?= $langs->trans('purchasesUnitPrice') ?>:</b> 
                                    <input type="text" id='unitprice_<?= $pid ?>' value="" class="MustBeDecimal" style="text-align:right;width:4rem;" />
                                    <span id='span_curr_<?= $pid ?>' style='color:#888;'></span>
                                </span>
                                <a href="#" class="button" onclick="js_add_line_supp('<?= $pid ?>');return false;"><?= $langs->trans('purchasesAddAsProv') ?></a>
                            </p>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                
                <?php } ?>

            </table>
        </div>

    </form>
    <br />
    
    <?php } ?>
    
    
    <?php /*echo _var_export($purchase->products,'$products').'<hr />'.$purchase->s_products;*/ ?>
    
</div>

<script>
    function js_selected_supplier_to_add_price(pid){
        // == control for unitprice
            var select_obj = $('#supplier_id_'+pid);
            select_obj.removeClass('alertedfield');
            if ($('option:selected', select_obj).attr('data-existing-price')=='0'){
                $('#span_unitprice_'+pid).show();
            }else{
                $('#span_unitprice_'+pid).hide();
            }
        // == currency 
            var supplier_curr = $('#supplier_id_'+pid+' option:selected').attr('data-curr');
            $('#span_curr_'+pid).html(supplier_curr);
            if (supplier_curr != ''){
                $('#span_curr_'+pid).show();
            }
    }
    
    /*
     * Generates a table with the preview of the price requests to the suppliers
     */
    function js_request_preview(){

        /* = toggle dialog box = */
            if ($('#email_confirmation').is(':visible')){
                $('#email_confirmation').slideUp();
                return;
            
            /* = toggle order dialog box = */
            }else if ($('#order_confirmation').is(':visible')){
                $('#order_confirmation').slideUp();
            }
            
        /* = find products marked to be ordered now = */
            var product_order_list = {};
            $('#purchase_product_form tr.tr_price').each(function(){
                var emailed = $(this).attr('data-emailed');
                    if (emailed == '1') return; // = we descard it because it has been emailed
                var pid = $(this).attr('data-pid');
                var order_id = $('#order_id_'+pid).val();
                    if (parseInt(order_id,10) != 0) return; // = we discard it, because it has been ordered
                var product_label = $('#product_'+pid+'_1 span[data-f=product_label]').html();
                var pr_ii = $(this).attr('data-pr-ii');
                var sid = $(this).attr('data-sid');
                var msid = $(this).attr('data-msid');
                var n = $(this).attr('data-n');
                if (!product_order_list[sid]){ 
                    var supplier_label = $(this).closest('tr').find('span[data-f=supplier_label]').html();
                    product_order_list[sid] = {sid:sid, msid:msid, label:supplier_label, products:{} };
                }
                product_order_list[sid]['lang'] = $(this).attr('data-lang');
                product_order_list[sid]['email'] = $(this).attr('data-email');
                product_order_list[sid]['products'][pid] = {pid:pid, pr_ii:pr_ii, n:n ,label:product_label};
            });
            if (Object.keys(product_order_list).length == 0){
                alert("<?= str_replace('"','\"',  html_entity_decode($langs->trans('purchasesInfoMsg04'),ENT_QUOTES)) ?>");
                return;
            }
            
        /* = display table of suppliers to be requested = */
            $('#email_confirmation table tbody').html('');
            $.each(product_order_list,function(k,arr){
                /* product list */
                    var products_html = '<ul style="padding-left:1rem;">';
                    $.each(arr['products'],function(k2,arr2){
                        products_html += '<li>'+parseFloat(arr2['n'])+' <b>x</b> '+arr2['label']+'</li>';
                    });
                    products_html += '</ul>';
                /* email */
                    if (arr['email'] == '')
                        var email_html = '<?= str_replace("'","\'",img_warning('')) ?>';
                    else
                        var email_html = arr['email'];
                /* lang flag */
                    if (arr['lang'] && arr['lang']!='')
                        var form_lang = '&lang='+arr['lang'];
                    else
                        var form_lang = '&lang=<?= $langs->getDefaultLang() ?>';
                /* append to table */
                    var html_tr = "<tr><td><a href='<?= DOL_URL_ROOT ?>/societe/soc.php?socid="+arr['sid']+"' target='_blank'>"+arr['label']+"</a></td>"
                                 +"<td>"+products_html+"</td>"
                                 +"<td style='text-align:center;'>"+email_html+"</td>"
                                 +"<td><a href='public_request.php?rid=<?= $purchase->rowid ?>&mrid=<?= md5('pupy'.$purchase->rowid) ?>&sid="+arr['sid']+"&msid="+arr['msid']+form_lang+"' target='_blank' class='button'><?= str_replace('"','\"',$langs->trans('purchasesForm')) ?></a></td>"
                                 +"</tr>";
                    $('#email_confirmation table tbody').append(html_tr);
            });
            /* display DIV */
            $('#email_confirmation').slideDown();
    }
        
    /*
    * Generate a list of products to be ordered grouped by supplier and each one with a "create order" button
    * so it's like a preview before to create the order. In fact, clicking "create order" this module will create an standard Dolibarr order, in draft mode
     */
    function js_create_purchase_orders(){
            var multicurrency = <?= $conf->multicurrency->enabled ? '1' : '0' ?>;
        /* = toggle dialog box = */
            if ($('#order_confirmation').is(':visible')){
                $('#order_confirmation').slideUp();
                return;
                
            /* = toggle email dialog box = */
            }else if ($('#email_confirmation').is(':visible')){
                $('#email_confirmation').slideUp();
            }
        /* = find products marked to be ordered now = */
            var product_order_list = {};
            $('#purchase_product_form input[type=radio]').each(function(){
                if (!$(this).is(':checked')) return; // = we descard it because is not checked
                var pid = $(this).attr('data-pid');
                var pr_ii = $(this).attr('data-pr-ii');
                var order_id = $('#order_id_'+pid).val();
                if (parseInt(order_id,10) != 0) return; // = we discard it, because it has been ordered
                var product_label = $('#product_'+pid+'_1 span[data-f=product_label]').html();
                
                var trObj = $('#product_'+pid+'_pr_'+pr_ii);
                var sid = $(trObj).attr('data-sid');
                var n = $(trObj).attr('data-n');
                var price = parseFloat($(trObj).attr('data-price'));
                var price_formatted = $(trObj).attr('data-price-formatted');
                var curr = $(trObj).attr('data-curr');
                var rate = parseFloat($(trObj).attr('data-rate'));
                
                if (rate==0) rate=1;
                if (!product_order_list[sid]){ 
                    var supplier_label = $(trObj).find('span[data-f=supplier_label]').html();
                    product_order_list[sid] = {sid:sid, label:supplier_label, products:{}, price:0, curr:curr, rate:rate };
                }
                product_order_list[sid]['products'][pid] = {pid:pid, pr_ii:pr_ii, n:n ,label:product_label, price_formatted:price_formatted, price:price};
                product_order_list[sid]['price'] += price;
            });
            if (Object.keys(product_order_list).length == 0){
                alert("<?= str_replace('"','\"',  html_entity_decode($langs->trans('purchasesInfoMsg04'),ENT_QUOTES)) ?>");
                return;
            }
            
        /* = display table of ready orders = */
            $('#order_confirmation table tbody').html('');
            $.each(product_order_list,function(k,arr){
                /* product list */
                    var products_html = '<ul style="padding-left:1rem;">';
                    $.each(arr['products'],function(k2,arr2){
                        products_html += '<li>'+parseFloat(arr2['n'])+' <b>x</b> '+arr2['label']+'</li>';
                    });
                    products_html += '</ul>';
                /* order button */
                    var button_href = "purchase_edit.php?mainmenu=commercial&leftmenu=&rowid=<?= $purchase->rowid ? $purchase->rowid : '' ?>&action=create_order&sid="+arr['sid'];
                    var button_html = "<a href='"+button_href+"' class='button butActionDelete' target='_blank'><?= dol_escape_htmltag($langs->trans('purchasesCreateOrder')) ?></a>";
                /* append to table */
                    if (arr['curr']!='' && multicurrency == 1){
                        var own_currency_price = arr['price'] * arr['rate'];
                        var curr_html = " <span style='color:#888;'>"+arr['curr']+"</span>";
                        var td_own_currency_price = "<td>("+number_format(own_currency_price,2,'.',',')+" <span style='color:#888;'><?= $conf->currency ?></span>)</td>";
                    }else{
                        var curr_html = "";
                        var td_own_currency_price = "<td>&nbsp;</td>";
                    }
                    var html_tr = "<tr><td><b>"+arr['label']+"</b></td>"
                                 +"<td>"+products_html+"</td>"
                                 +"<td>"+number_format(arr['price'],2,'.',',')+curr_html+"</td>"
                                 + td_own_currency_price
                                 +"<td>"+button_html+"</td></tr>";
                    $('#order_confirmation table tbody').append(html_tr);
            });
            /* display DIV */
            $('#order_confirmation').slideDown();
    }
    
    function js_toggle_hide_prices(){
        if ($('#hide_prices').is(':checked'))
            $('#purchase_product_form tr[data-rel=prices_tr]').hide();
        else
            $('#purchase_product_form tr[data-rel=prices_tr]').show();
    }
    
    function js_delete_purchase(){
        if (confirm("<?= str_replace('"','',html_entity_decode($langs->trans('purchasesDelSure','',0),ENT_QUOTES)) ?>")){
            document.location = 'purchase_edit.php?mainmenu=commercial&action=delete_purchase&rowid=<?= $purchase->rowid ?>';
        }
    }
    
    function js_del_line(pid){
        $('#purchase_product_form input[name=action]').val('del_line');
        $('#purchase_product_form input[name=product_id]').val(pid);
        $('#purchase_product_form').submit();
    }

    function js_save_lines(pid){
        $('#purchase_product_form input[name=action]').val('save_lines');
        $('#purchase_product_form input[name=product_id]').val(pid);
        $('#purchase_product_form').submit();
    }

    function js_del_line_supp(pid,pr_ii){
        
        /* == prepare submit ==*/
            $('#purchase_product_form input[name=product_id]').val(pid);
            $('#purchase_product_form input[name=price_ii]').val(pr_ii);
            
        /* == modal dialog box asking about delete the price from Dolibarr == */
	$("#debug")
                .html('<p><?= str_replace('&#039;',"\'",html_entity_decode($langs->trans('purchasesInfoMsg05'))) ?></p>')
                .dialog({
                        title: '<?= str_replace('&#039;',"\'",html_entity_decode($langs->trans("purchasesButton01"))) ?>',
                        resizable: false,
                        body: 'Here',
                        width: 500,
                        modal: true,
                        buttons: [
                                {
                                    id : 'DeleteOnlyHere',
                                    text : '<?= str_replace('&#039;',"\'",html_entity_decode($langs->trans('purchasesDelOnly'))) ?>',
                                    click : function() {
                                            $('#purchase_product_form input[name=action]').val('del_line_supp');
                                            $('#purchase_product_form').submit();
                                            $(this).dialog("close");
                                    }
                                },
                                {
                                    id : 'DeleteAround',
                                    text : '<?= str_replace('&#039;',"\'",html_entity_decode($langs->trans('purchasesDelToo'))) ?>',
                                    click : function() {
                                            $('#purchase_product_form input[name=action]').val('del_line_supp_and_price');
                                            $('#purchase_product_form').submit();
                                            $(this).dialog("close");
                                    }
                                },
                        ]
                });
        return;
    }

    function js_add_line_supp(pid){
        /* == does this supplier has a price for this product (in database) */
            var existing_price = $('#supplier_id_'+pid+' option:selected').attr('data-existing-price');
            
        /* == display unitprice control == */
            if (!$('#span_unitprice_'+pid).is(':visible') && existing_price=='0' && existing_price!=undefined){
                $('#span_unitprice_'+pid).show();
                return;
            }
        
        /* == process request == */
            var supplier_id = $('#supplier_id_'+pid).val();
                if (supplier_id=='') 
                    $('#supplier_id_'+pid).addClass('alertedfield');
                else
                    $('#supplier_id_'+pid).removeClass('alertedfield');
            var unitprice = $('#unitprice_'+pid).val();
                if (unitprice=='') 
                    $('#unitprice_'+pid).addClass('alertedfield');
                else
                    $('#unitprice_'+pid).removeClass('alertedfield');
        
        /* == check not empty values == */
            if (supplier_id=='' || (existing_price=='0' && unitprice=='')) return;

        /* == submit form == */
            $('#purchase_product_form input[name=action]').val('add_line_supp');
            $('#purchase_product_form input[name=product_id]').val(pid);
            $('#purchase_product_form input[name=supplier_id]').val(supplier_id);
            $('#purchase_product_form input[name=unitprice]').val(unitprice);
            $('#purchase_product_form').submit();
    }

    function js_validate_form(form_id){
        /* prepare */
            var all_fine = true, fine = true, control, c_val, c_name, c_id;
            $(control).removeClass('alertedfield');
            $('#'+form_id+' tr').removeClass('alertedcontainer');
        /* check required fields */
        $('#'+form_id+' .fieldrequired').each(function(){
            /* = input fields = */
                control = $(this).closest('tr').find('input');
                c_val = $(control).val();
                c_name = $(control).attr('name');
                c_id = $(control).attr('id');
                if (c_name!=undefined){
                    if (c_val=='') fine = false;
                    if (!fine){
                        all_fine = false;
                        $(control).addClass('alertedfield');
                        $(control).closest('tr').addClass('alertedcontainer');
                    }
                }
            /* = select fields = */
                control = $(this).closest('tr').find('select');
                c_val = $(control).val();
                c_name = $(control).attr('name');
                c_id = $(control).attr('id');
                if (c_name!=undefined){
                    if (c_val=='' || c_val=='-1') fine = false;
                    if (!fine){
                        all_fine = false;
                        $(control).addClass('alertedfield');
                        $(control).closest('tr').addClass('alertedcontainer');
                    }
                }
        });

        /* set auto UN-ALERT */
            $('.alertedfield').on('click',function(){
                $(this).removeClass('alertedfield');
            });
            $('.alertedcontainer').on('click',function(){
                $(this).removeClass('alertedcontainer');
            });

        /* submit form */
            if (all_fine){
                $('#'+form_id).submit();
            }

    }
    
    function js_update_calculations(){
        $('#purchase_product_form table tr[data-rel=product_tr]').each(function(){
            var pid= $(this).attr('data-pid');
            var n = parseFloat( $(this).find('input[data-f=n]').val() );
            
            /* calculate for each supplier */
            $('#product_'+pid+'_2 table tr').each(function(){
                $(this).find('span[data-f=n]').html( n );
                var min = parseFloat( $(this).find('input[data-f=min]').val() );
                var unitprice = parseFloat( $(this).find('input[data-f=unitprice]').val() );
                var currency_rate = parseFloat( $(this).find('input[data-f=unitprice]').attr('data-rate') );
                var finalprice = Math.max(n,min) * unitprice * currency_rate; 
                finalprice.toFixed(2);
                $(this).find('span[data-f=finalprice]').html( number_format(finalprice,2,'.',',') );
            });
        });
    }
    
    function js_json_mark_price_to_order(pid,pr_ii){
        $("body").css("cursor", "wait");
        var url_json = 'purchase_edit.php?action=json_mark_price_to_order'
                        +'&rowid=<?= $purchase->rowid ?>'  
                        +'&pid=' + pid
                        +'&pr_ii=' + pr_ii;
        //alert(url_json);
        $.getJSON(
            url_json,
            function(data){
                $("body").css("cursor", "default");
            }
        );
    
    }
    
    $(document).ready(function(){
        
        /* == put focus on the box to add a new product line == */
        if ($('#new_line td.titlefield').length){
            $('#new_line td.titlefield').find('label').focus();
        }
        
        /* == triggers for changes on INPUT controls == */
        $('#purchase_product_form input[type=text]').on('keyup',function(){
            if ($(this).attr('data-f') !== undefined){
                js_update_calculations()
            }
        });
        
        /* == triggers for click on radio INPUT controls == */
        $('#purchase_product_form input[type=radio]').on('click',function(){
            var data_name = $(this).attr('data-name');
            var pid = $(this).attr('data-pid');
            var pr_ii = $(this).attr('data-pr-ii');
            if ($(this).attr('data-checked-id') == $(this).attr('data-id')) {
                $(this).removeAttr('checked');
                var new_data_id = '';
            } else {
                var new_data_id = $(this).attr('data-id');
            }
            /* set the new data-id to the buttonset */
                $('#purchase_product_form input[type=radio]').each(function(){
                    if ($(this).attr('data_name',data_name)) $(this).attr('data-checked-id',new_data_id);
                });
            /* use a JSON call to save this change on database */
                if (new_data_id=='')
                    js_json_mark_price_to_order(pid,'');
                else
                    js_json_mark_price_to_order(pid,pr_ii);
        });
        
        /* == assign an data-id and data-name to all RADIO inputs (to be able to work like UI buttonsets) == */
        var data_id = 0;
        var checked_data_id = {}; /* to collect checked radio IDs and afterwards set attribute data-checked-id */
        $('input[type=radio]').each(function(){
            data_id++;
            var data_name = $(this).attr('name');
            data_name.replace('[','_');
            $(this).attr('data-id',data_id).attr('data-name',data_name);
            if ($(this).is(':checked')){
                checked_data_id[data_name] = data_id;
            }
        });

        /* == set data-checked-id for each radio input == */
        $('input[type=radio]').each(function(){
            var data_name = $(this).attr('name');
            data_name.replace('[','_');
            if (checked_data_id[data_name] != undefined){
                $(this).attr('data-checked-id',checked_data_id[data_name]);
            }
        });

        /* == checking fot MustBeDecimal input controls == */
        $(".MustBeDecimal").on("keypress keyup blur",function (event) {
                var value = $(this).val();
                var clean_value = value.replace(/[^0-9\.]/g,''); 
                var cursor_position = $(this).getCursorPosition();
                if (clean_value != value){
                    $(this).val(clean_value).setCursorPosition(cursor_position - 1);
                }
        });

        /* == refresh price calculations == */
        js_update_calculations();
    });
    
    /* == needed for MustBeDecimal == */
    (function ($, undefined) {
            $.fn.getCursorPosition = function() {
                var el = $(this).get(0);
                var pos = 0;
                if('selectionStart' in el) {
                    pos = el.selectionStart;
                } else if('selection' in document) {
                    el.focus();
                    var Sel = document.selection.createRange();
                    var SelLength = document.selection.createRange().text.length;
                    Sel.moveStart('character', -el.value.length);
                    pos = Sel.text.length - SelLength;
                }
                return pos;
            }
            
            $.fn.setCursorPosition = function(pos) {
                this.each(function(index, elem) {
                  if (elem.setSelectionRange) {
                    elem.setSelectionRange(pos, pos);
                  } else if (elem.createTextRange) {
                    var range = elem.createTextRange();
                    range.collapse(true);
                    range.moveEnd('character', pos);
                    range.moveStart('character', pos);
                    range.select();
                  }
                });
                return this;
          };
    })(jQuery);
    

    function number_format (number, decimals, decPoint, thousandsSep) { // eslint-disable-line camelcase
      //  discuss at: http://locutus.io/php/number_format/
      // original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
      // improved by: Kevin van Zonneveld (http://kvz.io)
      // improved by: davook
      // improved by: Brett Zamir (http://brett-zamir.me)
      // improved by: Brett Zamir (http://brett-zamir.me)
      // improved by: Theriault (https://github.com/Theriault)
      // improved by: Kevin van Zonneveld (http://kvz.io)
      // bugfixed by: Michael White (http://getsprink.com)
      // bugfixed by: Benjamin Lupton
      // bugfixed by: Allan Jensen (http://www.winternet.no)
      // bugfixed by: Howard Yeend
      // bugfixed by: Diogo Resende
      // bugfixed by: Rival
      // bugfixed by: Brett Zamir (http://brett-zamir.me)
      //  revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
      //  revised by: Luke Smith (http://lucassmith.name)
      //    input by: Kheang Hok Chin (http://www.distantia.ca/)
      //    input by: Jay Klehr
      //    input by: Amir Habibi (http://www.residence-mixte.com/)
      //    input by: Amirouche
      //   example 1: number_format(1234.56)
      //   returns 1: '1,235'
      //   example 2: number_format(1234.56, 2, ',', ' ')
      //   returns 2: '1 234,56'
      //   example 3: number_format(1234.5678, 2, '.', '')
      //   returns 3: '1234.57'
      //   example 4: number_format(67, 2, ',', '.')
      //   returns 4: '67,00'
      //   example 5: number_format(1000)
      //   returns 5: '1,000'
      //   example 6: number_format(67.311, 2)
      //   returns 6: '67.31'
      //   example 7: number_format(1000.55, 1)
      //   returns 7: '1,000.6'
      //   example 8: number_format(67000, 5, ',', '.')
      //   returns 8: '67.000,00000'
      //   example 9: number_format(0.9, 0)
      //   returns 9: '1'
      //  example 10: number_format('1.20', 2)
      //  returns 10: '1.20'
      //  example 11: number_format('1.20', 4)
      //  returns 11: '1.2000'
      //  example 12: number_format('1.2000', 3)
      //  returns 12: '1.200'
      //  example 13: number_format('1 000,50', 2, '.', ' ')
      //  returns 13: '100 050.00'
      //  example 14: number_format(1e-8, 8, '.', '')
      //  returns 14: '0.00000001'

      number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
      var n = !isFinite(+number) ? 0 : +number
      var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
      var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep
      var dec = (typeof decPoint === 'undefined') ? '.' : decPoint
      var s = ''

      var toFixedFix = function (n, prec) {
        var k = Math.pow(10, prec)
        return '' + (Math.round(n * k) / k)
          .toFixed(prec)
      }

      // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
      s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.')
      if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
      }
      if ((s[1] || '').length < prec) {
        s[1] = s[1] || ''
        s[1] += new Array(prec - s[1].length + 1).join('0')
      }

      return s.join(dec)
    }    
</script>
<div id="debug"></div>

<style>
    input.alertedfield, select.alertedfield, textarea.alertedfield{background-color:yellow!important;}
    .alertedcontainer td, .alertedcontainer td.fieldrequired{color:red!important;}
    .block{padding:0.5rem;background-color:rgba(100,100,100,0.05);border-radius:3px;border:1px rgba(100,100,100,0.2) solid;}
    .product_status_1,.product_status_2,.product_status_3{
        display:inline-block; width:1rem; height:1rem; line-height:1.6rem;
        text-align:center; font-size:1.2rem; font-weight:bold; color: white;
        border:4px rgba(0,0,0,0.08) solid; border-radius:50%;
    }
    .product_status_1{background-color:#da9;}
    .product_status_2{background-color:#99d;}
    .product_status_3{background-color:#9d9;}
</style>

<?php
    // End of page
    $db->close();
    llxFooter('$Date: 2009/03/09 11:28:12 $ - $Revision: 1.8 $');

