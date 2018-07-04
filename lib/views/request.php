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
 *	\file       htdocs/purchases/lib/views/request.php
 *      \defgroup   Purchases
 *      \brief      Web form for accept quotes from a supplier for the products of a purchase
 *      \version    v 1.0 2017/11/20
*/

/***************************************************
 * 
 *	Prepare data
 * 
****************************************************/

    // == misc
        $productstatic = new Product($db);
        $request_uri = '';
        foreach ($_GET as $k=>$v){
            if ($k!=='lang') $request_uri .= $k.'='.$v.'&';
        }
        $products_to_be_ordered = 0;

    // == load supplier
        $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."societe WHERE rowid='$supplier_id' LIMIT 1");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $supplier = $row;
        }
        $curr = !empty($supplier['multicurrency_code']) ? $supplier['multicurrency_code'] : $conf->currency;
        
        
/***************************************************
 * 
 *	View
 * 
****************************************************/
        
        $langs->load('languages');
        $langs->load('admin');
        
    // == browser top title
        $title = $langs->trans('purchasesRequestTitle');
        $ts_create = strtotime($purchase->ts_create);
        $month = $langs->trans('Month'.date('m',$ts_create));
        $subtitle = '<h5>'.str_replace('_',$month,date('d _ Y',$ts_create)).'</h5>';
        llxHeaderPurchasesPublic($title,$subtitle);
        
?>

<div class='main_wrapper'>
    
    <!-- ========= box table of products ========= -->
    
    <div style="text-align:center;">
    <?php foreach ($languages as $l){ 
            if ($l == 'ca_ES')
                $flag = 'images/cat.png';
            else
                $flag = DOL_URL_ROOT.'/theme/common/flags/'.strtolower(substr($l,3)).'.png';
    ?>
        <a href="public_request.php?<?= $request_uri . 'lang=' . $l ?>">
            <img src="<?= $flag ?>" title="<?= $langs->trans('Language_'.$l) ?>" /></a> 
    <?php } ?>
    </div>
    
    <!-- ========= box table of products ========= -->
    
    <div>
        <?= $langs->trans('purchasesSupplier') ?>: <b><?= $supplier['nom'] ?></b> 
        <?= !empty($supplier['town']) ? ' ('.$supplier['town'].')' : '' ?>
    </p>
    
    <form id="purchase_product_form" action="public_request.php?rid=<?= $purchase->rowid ?>&mrid=<?= md5('pupy'.$purchase->rowid) ?>&sid=<?= $supplier_id ?>&msid=<?= md5('pupy'.$supplier_id) ?>" method="POST">
        <input type="hidden" name="token" value="<?= $_SESSION['newtoken']  ?>">
        <input type="hidden" name="action" value="save_quotes">
        
        <!-- ========= list of products to quote ========= -->

        <?php if ($purchase->rowid > 0){ ?>


            <div class="titre" style="margin-bottom:0.5rem;margin-top:1rem;position:relative;">
                <?= $langs->trans('purchasesQuotTit1') ?>
                - <?= $langs->trans('purchasesQuotTit2') ?>
                <a href='#' onclick="$('#instructions_div').toggle();return false;" style='position:absolute;right:0;top:0;display:inline-block;outline:none;'>
                    <?= $langs->trans('purchasesQuotTit3') ?>
                </a>
            </div>
            <div id="instructions_div" style="display:none;">
                <ul>
                    <li><b><?= $langs->trans('purchasesQuotCol1') ?>:</b> <?= $langs->trans('purchasesQuotColHelp1') ?></li>
                    <li><b><?= $langs->trans('purchasesQuotCol2') ?>:</b> <?= $langs->trans('purchasesQuotColHelp2') ?></li>
                    <li><b><?= $langs->trans('purchasesQuotCol3') ?>:</b> <?= $langs->trans('purchasesQuotColHelp3') ?></li>
                    <li><b><?= $langs->trans('purchasesQuotCol4') ?>:</b> <?= $langs->trans('purchasesQuotColHelp4') ?></li>
                    <li><b><?= $langs->trans('purchasesQuotCol5') ?>:</b> <?= $langs->trans('purchasesQuotColHelp5') ?></li>
                </ul>
            </div>
            <style>
                #instructions_div{background-color:rgba(255,255,255,0.9);border:1px rgba(0,0,0,0.1) solid;border-radius:5px;padding:1rem;margin: 1rem 0;color:#666;line-height:1.5rem;}
            </style>
            <div class="div-table-responsive-no-max">
                <table class="liste" id="product_table">
                    <thead>
                        <tr class="liste_titre">
                            <td><?= $langs->trans('purchasesProduct') ?></td>
                            <td style="text-align:center;"><?= $langs->trans('purchasesQuotCol1') ?></td>
                            <td style="text-align:center;">
                                <?= $langs->trans('purchasesQuotCol2') ?>
                                <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" 
                                     title="<?= htmlentities($langs->trans('purchasesNotAvProd')) ?>" />
                            </td>
                            <td style="text-align:center;"><?= $langs->trans('purchasesQuotCol3') ?></td>
                            <td style="text-align:center;"><?= $langs->trans('purchasesQuotCol4') ?></td>
                            <td style="text-align:center;">
                                <?= $langs->trans('purchasesQuotCol5') ?>
                                <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" 
                                     title="<?= htmlentities($langs->trans('purchasesMinDelivDays')) ?>" />
                            </td>
                            <td style="text-align:center;">(<?= $langs->trans('purchasesOptional') ?>)</td>
                        </tr>
                    </thead>

                    <!-- ========= List of products assigned to this supplier in this purchase ========= -->

                    <tbody>
                    <?php $j=0; foreach($purchase->products as $pid => $p){

                            // = get current price data for this supplier
                                if (empty($p['prices']) || !is_array($p['prices']) || count($p['prices'])==0) continue;
                                $price = array();
                                foreach ($p['prices'] as $pr){
                                    if (!empty($pr['sid']) && $pr['sid'] == $supplier_id) $price = $pr;
                                }
                                if (count($price)==0) continue;

                                $pr = $price;

                                $productstatic->fetch($pid);
                                $label = $productstatic->label;
                                
                                $available = !isset($pr['available']) || $pr['available']=='1' ? '1' : '0';
                                $ordered = !empty($p['order_id']) ? true : false;
                                if (!$ordered) $products_to_be_ordered++;
                                
                                $j++;

                    ?>

                        <!-- ==== row with PRODUCT profile === -->

                        <tr id='product_<?= $pid ?>_1' data-pid='<?= $pid ?>' data-rel='product_tr' data-order-id='<?= !empty($p['order_id']) ? intval($p['order_id']) : '' ?>'
                            class='product_tr_<?= $j % 2 ? 'odd':'even' ?> <?= $ordered ? 'tr_disabled':'' ?>'>
                            <td>
                                <?= $label ?>
                                <input type="hidden" name="products[<?= $pid ?>][label]" value="<?= str_replace('"','',$label) ?>" />
                                <input type="hidden" name="products[<?= $pid ?>][rowid]" value="<?= $pr['rowid'] ?>" />
                            </td>
                            <td style='text-align:center;'>
                                <input type="hidden" name="products[<?= $pid ?>][n]" value="<?= $p['n'] ?>" data-f='n' />
                                <b><?= $p['n'] ?></b>
                            </td>
                            <td style='text-align:center;'>
                                <?php if ($ordered){ ?>
                                    <b><?= $available == '1' ? $langs->trans('Yes') : $langs->trans('No') ?></b>
                                <?php }else{ ?>
                                    <input type="hidden" name="products[<?= $pid ?>][availability]" id="availability_<?= $pid ?>" value="<?= $available ?>" data-f="availability" />
                                    <p class="onoff">
                                        <input type="checkbox" <?= $available == '1' ? 'checked="checked"':'' ?> id="checkbox_<?= $pid ?>" onclick="js_availability_changed();" />
                                        <label for="checkbox_<?= $pid ?>"></label>
                                    </p>
                                <?php } ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($ordered){ ?>
                                    <b><?= !empty($pr['unitprice']) ? intval($pr['unitprice']) : '' ?> <?= $supplier['multicurrency_code'] ?></b>
                                <?php }else{ ?>
                                    <input type="text" style="width:3rem;text-align:right;" name="products[<?= $pid ?>][unitprice]" data-f="unitprice" class="MustBeDecimal"
                                           value="<?= !empty($pr['unitprice']) ? intval($pr['unitprice']) : '' ?>" /> <?= $supplier['multicurrency_code'] ?>
                                <?php } ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($ordered){ ?>
                                    <b><?= max(1,intval($pr['min'])) ?></b>
                                <?php }else{ ?>
                                    <input type="text" style="width:2rem;text-align:right;" name="products[<?= $pid ?>][min]" data-f="min" class="MustBeDecimal"
                                           value="<?= max(1,floatval($pr['min'])) ?>" /> &nbsp; &nbsp;
                                <?php } ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($ordered){ ?>
                                    <b><?= max(1,intval($pr['days'])) ?></b>
                                <?php }else{ ?>
                                    <input type="text" style="width:1.5rem;text-align:right;" name="products[<?= $pid ?>][days]" data-f="days" class="MustBeDecimal"
                                           value="<?= max(1,intval($pr['days'])) ?>" /> &nbsp; &nbsp;
                                <?php } ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($ordered){ ?>
                                    <img src="<?= DOL_URL_ROOT ?>/theme/<?= $conf->theme ?>/img/info.png" class="classfortooltip" style="margin-bottom:-5px;" 
                                         title="<?= htmlentities($langs->trans('purchasesOrderedPrice')) ?>" />
                                <?php }else{ ?>
                                    <a href="#" class="button" style="white-space:nowrap;" onclick="$('#product_<?= $pid ?>_2').toggle();return false;"><?= $langs->trans('purchasesAddComment') ?></a>
                                <?php } ?>
                            </td>
                        </tr>

                        <!-- ==== row with hidden control for add COMMENT === -->

                        <tr id='product_<?= $pid ?>_2' data-pid='<?= $pid ?>' data-rel='product_tr_comment' data-order-id='<?= !empty($p['order_id']) ? intval($p['order_id']) : '' ?>'
                            style="display:none;" class='product_tr_<?= $j % 2 ? 'odd':'even' ?>'>
                            <td style="text-align:right;color:#666;font-size:0.9em;"><?= $langs->trans('purchasesAddComment') ?> (<?= $langs->trans('purchasesOptional') ?>)</td>
                            <td colspan="6">
                                <input type="text" style="width:90%;font-size:0.9em;" name="products[<?= $pid ?>][comment]" data-f="comment"
                                       value="<?= isset($pr['comment']) ? htmlspecialchars($pr['comment']) : '' ?>" />
                            </td>
                        </tr>
                        
                    <?php } ?>

                    </tbody>

                </table>
            </div>

        <br />

        <!-- ========= Form with extra info ========= -->

        <div id="supplier_dialog" style="display:none;margin-bottom:3rem;">
                            
            
            <div class="titre" style="margin-bottom:0.5rem;margin-top:1rem;">
                <?= $langs->trans('purchasesCommToBuyer') ?>
            </div>

            <div class="border" style="width:100%;background-color:white;padding:1rem;">
                <textarea name="comment" style="width:100%;" placeholder="<?= str_replace('"','&quot;',$langs->trans('purchasesWriteCommHere')) ?>"></textarea>
            </div>
                            
            <div class="titre" style="margin-bottom:0.5rem;margin-top:1rem;">
                <?= $langs->trans('purchasesSellerContactData') ?>
            </div>

            <div class="border" style="width:100%;background-color:white;padding:1rem;">
                <table class="border" style="width:100%;">
                    <tr>
                        <td class="titlefield"><?= $langs->trans('purchasesCompanyTitle') ?></td>
                        <td><?= $supplier['nom'] ?></td>
                    </tr>
                    <tr>
                        <td class="titlefield"><?= $langs->trans('purchasesPhone') ?></td>
                        <td><input type="text" name="supplier[phone]" style="width:90%;" value="<?= htmlspecialchars(strip_tags($supplier['phone'])) ?>" /></td>
                    </tr>
                    <tr>
                        <td class="titlefield"><?= $langs->trans('purchasesEmail') ?></td>
                        <td><input type="text" name="supplier[email]" style="width:90%;" value="<?= htmlspecialchars(strip_tags($supplier['email'])) ?>" /></td>
                    </tr>
                    <tr>
                        <td class="titlefield"><?= $langs->trans('purchasesAddress') ?></td>
                        <td><input type="text" name="supplier[address]" style="width:90%;" value="<?= htmlspecialchars(strip_tags($supplier['address'])) ?>" /></td>
                    </tr>
                    <tr>
                        <td class="titlefield"><?= $langs->trans('LDAPFieldZip') ?></td>
                        <td><input type="text" name="supplier[zipcode]" style="width:100px;" value="<?= htmlspecialchars(strip_tags($supplier['zip'])) ?>" /></td>
                    </tr>
                    <tr>
                        <td class="titlefield"><?= $langs->trans('CompanyTown') ?></td>
                        <td><input type="text" name="supplier[town]" style="width:50%;" value="<?= htmlspecialchars(strip_tags($supplier['town'])) ?>" /></td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($products_to_be_ordered > 0){ ?>
        
        <!-- ========= Buttons ========= -->

        <p style="text-align:center;margin:0.5rem;margin-bottom:2rem;">
            <a href="#" class="button" onclick="js_show_dialog('supplier_dialog');return false;"><?= $langs->trans('purchasesExtraInfo') ?></a>
            <a href="#" class="button butActionDelete" onclick="js_validate_form('purchase_product_form');return false;"><?= $langs->trans('purchasesSubmit') ?></a>
        </p>
        
        <?php }else{ ?>
        
        <p style="color:red;font-weight:bold;text-align:center;"><?= $langs->trans('purchasesOldRequest') ?></p>
        
        <?php } ?>

        
        <?php } ?>
    
    </form>
    
    
    
    <?php /*echo _var_export($purchase->products,'$products').'<hr />'.$purchase->s_products;*/ ?>
    
</div>

<script>
    function js_show_dialog(dialog_id){
        if (dialog_id=='conditions_dialog')
            var other_dialog_id = 'supplier_dialog';
        else
            var other_dialog_id = 'conditions_dialog';
            
        if ($('#'+dialog_id).is(':visible')){
            $('#'+dialog_id).fadeOut();
        }else{
            if ($('#'+other_dialog_id).is(':visible')){
                $('#'+other_dialog_id).hide();
            }
            $('#'+dialog_id).fadeIn();
        }
        
    }
    
    function js_availability_changed(){
        $('#product_table tbody tr').each(function(){
            var pid = $(this).attr('data-pid');
            var rel = $(this).attr('data-rel');
            
            var order_id = $(this).attr('data-order-id');
            if (order_id!='') return;
            
            if (rel != 'product_tr') return;
            var checked = $('#checkbox_'+pid).is(':checked');
            if (!checked){
                $('#availability_'+pid).val('0');
                $(this).addClass('tr_disabled');
                $(this).find('input[type=text]').attr('disabled','disabled').removeClass('alertedfield');
                $(this).removeClass('alertedcontainer');
                $('#product_'+pid+'_2').slideDown();
            }else{
                $('#availability_'+pid).val('1');
                $(this).removeClass('tr_disabled');
                $(this).find('input[type=text]').removeAttr('disabled');
                if ($('#product_'+pid+'_2').is(':visible') && $('#product_'+pid+'_2 input[data-f=comment]').val()=='') 
                    $('#product_'+pid+'_2').hide();
                else if ($('#product_'+pid+'_2 input[data-f=comment]').val()!='')
                    $('#product_'+pid+'_2').show();
            }
        });
    }
    
    function js_validate_form(form_id){
        
        /* prepare */
            var all_fine = true, pid, availability, fVal;
            $('#'+form_id+' input').removeClass('alertedfield');
            $('#product_table tr').removeClass('alertedcontainer');
            
        /* check required fields (unitprices and minimum quantity for each product AVAILABLE) */
        $('#product_table tbody tr').each(function(){
                pid = $(this).attr('data-pid');
                availability = $('#availability_'+pid).val(); 
                if (availability=='0') return;
            /* = input fields = */
                $(this).find('input[type=text]').each(function(){
                    if ($(this).attr('data-f')!='unitprice' && $(this).attr('data-f')!='mins') return; /* only check these fields */
                    fVal = parseFloat($(this).val());
                    if (isNaN(fVal) || fVal <= 0){
                        all_fine = false;
                        $(this).addClass('alertedfield');
                        $(this).closest('tr').addClass('alertedcontainer');
                    }
                });
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
            }else{
                alert("<?= str_replace('"','\"',  html_entity_decode($langs->trans('purchasesErrorMsg05'),ENT_QUOTES)) ?>");
            }

    }
    
    $(document).ready(function(){
    
        $(".MustBeDecimal").on("keypress keyup blur",function (event) {
                var value = $(this).val();
                var clean_value = value.replace(/[^0-9\.]/g,''); 
                var cursor_position = $(this).getCursorPosition();
                if (clean_value != value){
                    $(this).val(clean_value).setCursorPosition(cursor_position - 1);
                }
        });
        
        /* == to disable the not available rows when loading page == */
        js_availability_changed();
        
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
    
</script>
<div id="debug"></div>

<style>
    input.alertedfield, select.alertedfield, textarea.alertedfield{background-color:yellow!important;}
    .alertedcontainer td, .alertedcontainer td.fieldrequired{color:red!important;}
    .block{padding:0.5rem;background-color:rgba(100,100,100,0.05);border-radius:3px;border:1px rgba(100,100,100,0.2) solid;}
    .product_status_1,.product_status_2,.product_status_3{
        display:inline-block; width:1.5rem; height:1.5rem; line-height:1.5rem;
        text-align:center; font-size:1.2rem; font-weight:bold; color: white;
        border:4px rgba(0,0,0,0.08) solid; border-radius:50%;
    }
    .product_status_1{background-color:#da9;}
    .product_status_2{background-color:#99d;}
    .product_status_3{background-color:#9d9;}
    .main_wrapper{max-width:900px;margin:0 auto;}
    @media (max-width: 900px) {
        /*table td {display:inline-block;}*/
    }
    .tr_disabled *{color:#999;}
    .product_tr_odd td{background-color:rgba(0,0,0,0.05);}
    .product_tr_even td{background-color:rgba(0,0,0,0.10);}
    .button{margin-bottom:0.3rem;}
    h1{margin-bottom:0;}
    h5{margin:0;font-weight:normal;color:#666;font-size:1em;}
    
    /* == ONOFF control == */
    
    .onoff {
      display: -moz-inline-stack;
      display: inline-block;
      vertical-align: middle;
      *vertical-align: auto;
      zoom: 1;
      *display: inline;
      position: relative;
      cursor: pointer;
      width: 55px;
      height: 30px;
      line-height: 30px;
      font-size: 1em;
      font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    }
    .onoff label {
      position: absolute;
      top: 0px;
      left: 0px;
      width: 100%;
      height: 100%;
      cursor: pointer;
      background: #aaa;
      border-radius: 5px;
      font-weight: bold;
      color: #FFF;
      -webkit-transition: background 0.3s, text-indent 0.3s;
      -moz-transition: background 0.3s, text-indent 0.3s;
      -o-transition: background 0.3s, text-indent 0.3s;
      transition: background 0.3s, text-indent 0.3s;
      text-indent: 27px;
      -webkit-box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4) inset;
      -moz-box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4) inset;
      box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4) inset;
    }
    .onoff label:after {
      content: '<?= str_replace("'",'',$langs->trans('No')) ?>';
      display: block;
      position: absolute;
      top: 0px;
      left: 0px;
      width: 100%;
      font-size: 1em;
      line-height: 2rem;
      text-align:left;
      color: white;
      text-shadow: 0px 1px 0px rgba(255, 255, 255, 0.35);
      z-index: 1;
    }
    .onoff label:before {
      content: '';
      width: 15px;
      height: 24px;
      border-radius: 3px;
      background: #FFF;
      position: absolute;
      z-index: 2;
      top: 3px;
      left: 3px;
      display: block;
      -webkit-transition: left 0.3s;
      -moz-transition: left 0.3s;
      -o-transition: left 0.3s;
      transition: left 0.3s;
      -webkit-box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4);
      -moz-box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4);
      box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4);
    }
    .onoff input:checked + label {
      background: #378b2c;
      text-indent: 8px;
    }
    .onoff input:checked + label:after {
        content: '<?= str_replace("'",'',  html_entity_decode($langs->trans('Yes'))) ?>';
      color: white;
    }
    .onoff input:checked + label:before {
      left: 37px;
    }    
    
</style>

<?php
    // End of page
    $db->close();
    llxFooterPurchasesPublic('$Date: 2009/03/09 11:28:12 $ - $Revision: 1.8 $');

