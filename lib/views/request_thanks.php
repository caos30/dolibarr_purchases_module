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
 *	\file       htdocs/purchases/lib/views/request_thanks.php
 *      \defgroup   Purchases
 *      \brief      Page to give thanks to supplier after submit their quotes in the quotation web form
 *      \version    v 1.0 2017/11/20
 */

/***************************************************
 * 
 *	View
 * 
****************************************************/
        
    // == browser top title
        $title = $langs->trans('purchasesRequestTitle');
        llxHeaderPurchasesPublic($title);
        
?>

<div class='main_wrapper'>
    
    <h1 class="success_message">
        <?= $langs->trans('purchasesQuotationThanks') ?>
        <br /><br />
        <img src="images/obicon.png" />
    </h1>
    
</div>

<style>
    .main_wrapper{max-width:800px;margin:0 auto;}
    .success_message{
        text-align: center;color:#0AC5AB;font-weight:bold;margin-top:5rem;
        padding:2rem;
        background-color: rgba(0,0,0,0.05);
        border: 1px rgba(0,0,0,0.1) solid;
        border-radius:5px;
    }
</style>

<div id="debug"></div>

<?php
    // End of page
    $db->close();
    llxFooterPurchasesPublic('$Date: 2009/03/09 11:28:12 $ - $Revision: 1.8 $');

