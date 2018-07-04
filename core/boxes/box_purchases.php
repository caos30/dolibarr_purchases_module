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
 *	\file       htdocs/purchases/boxes/box_purchases.php
 *	\ingroup    purchases
 *	\brief      render last X purchases in a box on Dolibarr Dashboard
 *      \version    v 1.0 2017/11/20
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

$langs->load("purchases");


/**
 * Class to manage the box to show last purchases
 */
class box_purchases extends ModeleBoxes
{
	var $boxcode="lastpurchases";
	var $boximg="object_commercial";
	var $boxlabel='purchasesBoxTitle';
	var $depends = array("purchases");

	var $db;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
		global $conf, $user;

		$this->db = $db;
                
		$this->hidden =  !$user->rights->produit || !$user->rights->fournisseur || !$user->rights->societe;
                
                // == PURCHASES_MODULE_DOCUMENT_ROOT
                    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/purchases/core/modules/modPurchases.class.php')){
                        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/purchases');
                        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/purchases');
                    }else{
                        define('PURCHASES_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/purchases');
                        define('PURCHASES_MODULE_URL_ROOT',DOL_URL_ROOT.'/purchases');
                    }
                
	}

	/**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");

		$this->max = $max;

                $this->info_box_head = array('text' => $langs->trans("purchasesBoxTitle",$max));
                
                // == check permissions
                    if (!$user->rights->produit && !$user->rights->fournisseur && !$user->rights->societe){
                        $this->info_box_contents[0][0] = array(
                            'td' => '',
                            'text' => $langs->trans("ReadPermissionNotAllowed"),
                        );
                        return;
                    }
                
                // == load data
                    include_once PURCHASES_MODULE_DOCUMENT_ROOT.'/lib/purchases_purchase.class.php';
                    $purchase = new Purchase($db);
                    $purchases = $purchase->getLatestPurchases(array('max'=>$max));

                    if (!is_array($purchases) || count($purchases)==0){
                        $this->enabled = 1;
                        $this->hidden = false;
                        return;
                    }
                
                // == load projeccts names
                    if (!empty($conf->projet->enabled)){
                        $projects = array();
                        $resql = $db->query("SELECT rowid,title FROM ".MAIN_DB_PREFIX."projet");
                        if ($resql) {
                            while($row = $resql->fetch_assoc()) $projects[$row['rowid']] = $row;
                        }
                    }
                    
                // == render
                    dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
                    $status_picto = array('0'=>'0','1'=>'1','2'=>'3','3'=>'4');
                    $line = 0;
                    foreach ($purchases as $p){

                        // = column: date and link to purchase
                            $url = PURCHASES_MODULE_URL_ROOT.'/purchase_edit.php?mainmenu=commercial&leftmenu=&rowid='.$p['rowid'];
                            
                            $picto_link = "<a href='$url'><img src='theme/".$conf->theme."/img/object_commercial.png' /></a>";
                            $text_link = " <a href='$url'>".$p['ts_create']."</a>";

                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="left"',
                                'text' => $picto_link.$text_link,
                                'asis' => 1,
                            );

                        // = column: project name
                            if (!empty($conf->projet->enabled)){
                                $this->info_box_contents[$line][] = array(
                                    'td' => 'align="left"',
                                    'text' => (isset($projects[$p['fk_project']]) ? $projects[$p['fk_project']]['title'] : '')
                                );
                            }
                            
                        // = column: number of products included
                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="center"',
                                'text' => $p['n_products']. ' '. $langs->trans('purchasesProducts')
                            );

                        // = column: status
                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="right" width="18"',
                                'text' => img_picto($langs->trans('purchasesStatus'.$p['status']),'statut'.$status_picto[$p['status']])
                            );
                            
                        $line++;
                    }

                    //if ($num==0) $this->info_box_contents[$line][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedCustomers"));

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

