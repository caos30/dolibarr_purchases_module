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
 *	\file       htdocs/purchases/lib/views/list.php
 *      \defgroup   Purchases
 *      \brief      View with the list of purchases (paginated, sortable, filters, etc...)
 *      \version    v 1.0 2017/11/20
 */

/***************************************************
 * 
 *	Prepare data
 * 
****************************************************/

    // == incoming and default data
        $id = GETPOST('id','int');
        
        $limit = GETPOST('limit','int');
            if (empty($limit) || !is_numeric($limit) || $limit < 1) $limit = $conf->liste_limit ;
            
        $page = GETPOST("page",'int');
            if (empty($page) || !is_numeric($page) || $page <0) $page = 0 ;
        
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        $offset = $limit * $page;
            
        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        
            if (! $sortfield) $sortfield = "rowid";
            if (! $sortorder) $sortorder = "DESC";
            
    // == object fields
        $arrayfields=array(
            'rowid'=>array('label'=>$langs->trans("ID"), 'checked'=>1, 'style'=>'width:4rem;'),
            'ts_create'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
            'label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
            'fk_project'=>array('label'=>$langs->trans("Project"), 'checked'=>1),
            'n_products'=>array('label'=>$langs->trans("Products"), 'checked'=>1,  'style'=>'width:4rem;'),
            'status'=>array('label'=>$langs->trans("Status"), 'checked'=>1),
        );
        
    // == load purchases
        
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."purchases";
        
        // == WHERE filter
            $where = array();
            foreach ($fsearch as $ff=>$vv){
                $where[] = natural_search($ff, $vv, 0, 1);
            }
            if (count($where)>0) $sql .= ' WHERE '.implode(' AND ',$where);

        // == ORDER
            $sql.= ' ORDER BY ';
            $listfield = explode(',',$sortfield);
            foreach ($listfield as $key => $value) 
                $sql.= $listfield[$key].' '.$sortorder.',';
            $sql.= ' rowid DESC ';
            
        // == Count total nb of records
            $nbtotalofrecords = '';
            if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)){
                $sql_count = "SELECT count(rowid) as n FROM ".MAIN_DB_PREFIX."purchases ";
                if (count($where)>0) $sql_count .= ' WHERE '.implode(' AND ',$where);
                $resql = $db->query($sql_count);
                $row = $resql->fetch_assoc(); 
                $nbtotalofrecords = isset($row['n']) ? intval($row['n']) : 0;
            }
            
        // == LIMIT
            $sql .= $db->plimit($limit+1, $offset);
            
        // == run query
            $purchases = array();
            $resql = $db->query($sql);
            if ($resql) {
                //while($obj = $db->fetch_object($resql)) $purchases[$obj->rowid] = $obj;
                while($row = $resql->fetch_assoc()) $purchases[] = $row;
            }

    // == load projects
        $projects = array();
        $resql = $db->query("SELECT rowid,title FROM ".MAIN_DB_PREFIX."projet");
        if ($resql) {
            while($row = $resql->fetch_assoc()) $projects[$row['rowid']] = $row;
        }

    // == load products names, if needed
        if (!empty($arrayfields['n_products']['checked'])){
            $products = array();
            $resql = $db->query("SELECT rowid,label FROM ".MAIN_DB_PREFIX."product");
            if ($resql) {
                while($row = $resql->fetch_assoc()) $products[$row['rowid']] = $row['label'];
            }
        }
        
    // == fetch optionals attributes and labels
        /*
        $extrafields = new ExtraFields($db);
        $extralabels = $extrafields->fetch_name_optionals_label('projet');
        $search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');
        */
        
    // == param for Action bar
        $param='';
        if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
        if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
        
        // == add GETPOST search params
        foreach ($fsearch as $ff=>$vv){
            $param.='&search_'.$ff.'='.urlencode($vv);
        }
        
        $form = new Form($db);
        $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
        
    // == mass actions (i.e. delete a group of lines)
        $arrayofmassactions=array(
	    'delete'=>$langs->trans("Delete")
	);
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

        
        
/***************************************************
 * 
 *	View
 * 
****************************************************/
        
    // == browser top title
        $help_url = '';
        llxHeader('',$langs->trans('purchasesBriefTitle'),$help_url);
        //echo _var_export($purchases,'$purchases');die();
        
    // == misc
        $moreforfilter = true;
        $status_picto = array('0'=>'0','1'=>'1','2'=>'3','3'=>'4');
        $new_button = '<a href="purchase_edit.php?mainmenu=commercial&leftmenu=" class="button">'.$langs->trans('purchasesNewPurchase').'</a>';
?>

    <!-- ========= header with section title & pagination controls ========= -->

    <form method="POST" id="purchase_searchFormList" name="searchFormList" action="<?= $_SERVER["PHP_SELF"] ?>">
        <input type="hidden" name="token" value="<?= $_SESSION['newtoken'] ?>" />
	<input type="hidden" name="formfilteraction" id="formfilteraction" value="list" />
        <input type="hidden" name="action" value="list" />
        <input type="hidden" name="sortfield" value="<?= $sortfield ?>" />
        <input type="hidden" name="sortorder" value="<?= $sortorder ?>" />
    
    
    <?php print_barre_liste($langs->trans('purchasesBriefTitle'), $page, $_SERVER["PHP_SELF"], $param, 
                        $sortfield, $sortorder, $massactionbutton, count($purchases), $nbtotalofrecords, 
                        'title_commercial.png', 0, $new_button, '', $limit); ?>
    
    <!-- ========= action bar ========= -->
    
    <?php if (empty($action) && $id > 0) { ?>
    <!--
    <div class="tabsAction">
        
    <?php   if ($user->rights->stock->mouvement->creer) { ?>            
            <a class="butAction" href="<?= $_SERVER["PHP_SELF"].'?id='.$id.'&action=correction' ?>">
                <?= $langs->trans("StockCorrection") ?></a>
    <?php       } ?>

    <?php   if ($user->rights->stock->mouvement->creer) { ?>
            <a class="butAction" href="<?= $_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert' ?>">
                <?= $langs->trans("StockTransfer") ?></a>
    <?php   } ?>

    </div><br />
    -->
    
    <?php } ?>

    <!-- ========= table list ========= -->
    
    <div class="underbanner clearboth"></div>
    
    <div class="div-table-responsive">
        
    <table class="tagtable liste <?= $moreforfilter ? "listwithfilterbefore":"" ?>">
        <thead>
            
    <!-- ========= header first row (column titles) ========= -->
    
        <tr class="liste_titre">
            <?php 
                // == field columns
                foreach($arrayfields as $f=>$field){
                    if (!empty($field['checked'])){
                        $align = in_array($f,array('status','n_products')) ? 'center' : 'left';
                        print_liste_field_titre($field['label'],$_SERVER["PHP_SELF"],$f,'',$param,'style="text-align:'.$align.';"',$sortfield,$sortorder); 
                    }
                }

                // == action column
                print print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
                
            ?>
        </tr>
    
    <!-- ========= header second row (filters) ========= -->
    
        <tr class="liste_titre">
            <?php 
                // == field columns
                foreach($arrayfields as $f=>$field){
                    if (!empty($field['checked'])){
                        $align = in_array($f,array('status')) ? 'center' : 'left';
                        print '<td class="liste_titre" align="'.$align.'">';
                        
                        if ($f=='n_products')
                            print '';
                        else if ($f=='rowid')
                            print '<input class="flat" style="'.(!empty($field['style']) ? $field['style']:'width:80%;').'" type="text" name="search_'.$f.'" value="'.(!empty($fsearch[$f]) ? dol_escape_htmltag($fsearch[$f]) : '').'" '.(!empty($field['param']) ? $field['param'].' ' : '').'/>';
                        else if ($f=='status')
                            print $form->selectarray('search_'.$f,  
                                            array(  '0'=>$langs->trans("purchasesStatus0"), 
                                                    '1'=>$langs->trans("purchasesStatus1"), 
                                                    '2'=>$langs->trans("purchasesStatus2"), 
                                                    '3'=>$langs->trans("purchasesStatus3"))
                                            ,$fsearch['status'], 1);
                        else
                            print '<input class="flat" style="'.(!empty($field['style']) ? $field['style']:'width:80%;').'" type="text" name="search_'.$f.'" value="'.(!empty($fsearch[$f]) ? dol_escape_htmltag($fsearch[$f]) : '').'" '.(!empty($field['param']) ? $field['param'].' ' : '').'/>';
                        
                        print '</td>';
                    }
                }
                
                // == action column
                print '<td class="liste_titre" align="middle">'
                        .$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1)
                        .'</td>';
                
            ?>
        </tr>
    </thead>
    
    <!-- ========= body rows ========= -->
    
    <tbody>
        
    <?php if (is_array($purchases) && count($purchases)>0){
            foreach ($purchases as $ii=>$ele){ 
                if (!is_array($ele)) continue; 
                if ($ii >= $limit) continue; /* this is because we musn't to include the last row */
                // = prepare list of products
                    $html_list_products = '';
                    if (!empty($ele['n_products']) && !empty($arrayfields['n_products']['checked'])){
                        $a_products = unserialize($ele['s_products']);
                        foreach ($a_products as $p)
                            $html_list_products .= '<li>'.intval($p['n']).' <b>x</b> '
                                                    .(isset($products[$p['pid']]) ? str_replace('"','',$products[$p['pid']]) :'pid #'.$p['pid'])
                                                    .(!empty($p['order_id']) ? ' ('.$langs->trans('purchasesORDERED').')' :'')
                                                    .'</li>';
                        $html_list_products = '<ul>'.$html_list_products.'</ul>';
                    }
    ?>
        <tr>
            <?php
                foreach($arrayfields as $f=>$field){ //  use this to render fancy tooltips stored on title attribute of a link class="classfortooltip"
                    if (!empty($field['checked'])){
                        if ($f=='rowid'){
                            print '<td><a href="purchase_edit.php?mainmenu=commercial&leftmenu=&rowid='.$ele[$f].'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_bill.png" border="0"></a>';
                            print ' <a href="purchase_edit.php?mainmenu=commercial&leftmenu=&rowid='.$ele[$f].'">#'.$ele['rowid'].'</a></td>';
                            
                        }else if ($f=='label'){
                            print '<td>'.(isset($ele[$f]) ? $ele[$f] : '')
                                .(!empty($ele['note']) ? ' <img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" title="'.str_replace('"','',  htmlentities($ele['note'])).'" class="classfortooltip" style="margin-bottom:-5px;" /> ' : '')
                                .'</td>';
                            
                        }else if ($f=='fk_project'){
                            if (empty($ele[$f])){
                                print '<td>&nbsp;</td>';
                            }else if (isset($projects[$ele[$f]])){
                                print '<td><a href="#" onclick="js_filter_by(\''.$f.'\',\''.$ele[$f].'\');return false;"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" title="'.str_replace('"','',$langs->trans('purchasesProjectFilter')).'" class="classfortooltip" border="0"></a>';
                                print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$ele[$f].'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_project.png" title="'.str_replace('"','',$langs->trans('purchasesVisitProject')).'" class="classfortooltip" border="0"></a>';
                                print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$ele[$f].'" title="'.str_replace('"','',$langs->trans('purchasesVisitProject')).'" class="classfortooltip">'.$projects[$ele[$f]]['title'].'</a></td>';
                            }else{
                                print '<td>#'.$ele[$f].'</td>';
                            }
                        }else if ($f=='status'){
                            if (!isset($ele[$f]))
                                print '<td style="text-align:center;">&nbsp;</td>';
                            else
                                print '<td style="text-align:center;">'.img_picto($langs->trans('purchasesStatus'.$ele[$f]),'statut'.$status_picto[$ele[$f]]).'</td>';
                        }else if ($f=='n_products'){
                            print '<td style="text-align:center;">'
                                    .($html_list_products!='' ? ' <img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" title="'.htmlentities($html_list_products).'" class="classfortooltip" style="margin-bottom:-5px;" /> ' : '')
                                    .(isset($ele[$f]) ? intval($ele[$f]) : '').'</td>';
                        }else{
                            print '<td>'.(isset($ele[$f]) ? $ele[$f] : '').'</td>';
                        }
                        
                    }
                }
                
                print '<td>&nbsp;</td>';
            ?>
        </tr>
        
    <?php }} ?>
    
    </tbody>
    
    </table>
        
    </div>
    
    </form>
        
    <!-- MODULE VERSION & USER GUIDE LINK -->
    <?php 
        require_once PURCHASES_MODULE_DOCUMENT_ROOT.'/core/modules/modPurchases.class.php';
        $module = new modPurchases($db); 
        $user_lang = substr($langs->defaultlang,0,2);
    ?>
    <div style="margin: 2rem 0;color: #ccc;display: inline-block;border-top: 1px #ccc solid;border-bottom: 1px #ccc solid;background-color: rgba(0,0,0,0.05);padding: 0.5rem;">
        <span class="help">Purchases <?= $module->version ?> 
           &nbsp; | &nbsp; <a href="https://wiki.dolibarr.org/index.php/Module_Purchases<?= $user_lang == 'es' ? '':''?>" target="_blank"><?= $langs->trans('purchasesUserGuide') ?></a>
        </span>
    </div>
    
    <script>
        function js_filter_by(fieldname,fieldvalue){
            $('#purchase_searchFormList input[name=search_'+fieldname+']').val(fieldvalue);
            $('#purchase_searchFormList').submit();
        }
    </script>
    <?php

    // End of page
    $db->close();
    llxFooter('$Date: 2009/03/09 11:28:12 $ - $Revision: 1.8 $');

