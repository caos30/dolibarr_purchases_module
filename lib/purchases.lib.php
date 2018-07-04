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
 *	\file       htdocs/purchases/lib/purchases.lib.php
 *      \ingroup    purchases
 *      \brief      Mix of functions for PURCHASES module
 *      \version    v 1.0 2017/11/20
 */

$linktohelp='EN:Module_purchases_En|CA:Modul_purchases|ES:Modulo_purchases';

function _render_view($viewname,Array $vars){
    global $langs, $db, $conf, $user;
    // == passed vars
        if (count($vars)>0){ 
            foreach($vars as $__k__=>$__v__){
                ${$__k__} = $__v__;
            }
        }
    // == we save a copy of the content already existing at the output buffer (for no interrump it)
        $existing_render = ob_get_clean( );
    // == we begin a new output
        ob_start( );
        include(dirname(__FILE__).'/views/'.$viewname.'.php');
    // == we get the current output
        $render = ob_get_clean( ); 
    // == we re-send to output buffer the existing content before to arrive to this function ;)
        ob_start( );
        echo $existing_render;

        return $render;
}


function _var_export($arr, $title=''){
        if ($title!='' && phpversion() > '5.3.0' && class_exists('Tracy\Debugger')){
            eval("Tracy\Debugger::barDump(\$arr,\$title);");
        } 
            
        $html = !empty($title) ? '<h3>'.$title.'</h3>' : '';
	$html .= "\n<div style='margin-left:100px;font-size:10px;font-family:sans-serif;'>";
	if (is_array($arr)){
            if (count($arr)==0){
                $html .= "&nbsp;";	
            }else{
                $ii=0;
                foreach ($arr as $k=>$ele){
                    $html .= "\n\t<div style='float:left;'><b>$k <span style='color:#822;'>&rarr;</span> </b></div>"
                            ."\n\t<div style='border:1px #ddd solid;font-size:10px;font-family:sans-serif;'>"._var_export($ele)."</div>";
                    $html .= "\n\t<div style='float:none;clear:both;'></div>";
                    $ii++; 
                }
            }
	}else if ($arr===NULL){ 
            $html .= "&nbsp;";
	}else if ($arr === 'b:0;' || substr($arr,0,2)=='a:'){
            $uns = @unserialize($arr);
            if (is_array($uns))
                $html .= htmlspecialchars($arr).'<br /><br />'._var_export($uns).'<br />';
            else
                $html .= htmlspecialchars($arr);
        }else{
            $html .= htmlspecialchars($arr);
        }
	$html .= "</div>";
	return $html;
}

/**
 * Show header for public visitors (not authenticated)
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderPurchasesPublic($title, $subtitle='', $head="", $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $conf, $mysoc;

        // == head 
            top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers
        
        // == body
            print '<body id="mainbody" class="publicnewmemberform" style="margin-top: 10px;">';

        // == extra script/style
            print ' <!-- JS CODE TO ENABLE tipTip on all object with class classfortooltip -->
                    <script type="text/javascript">
                        jQuery(document).ready(function () {
                                jQuery(".classfortooltip").tipTip({maxWidth: "700px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
                        });
                    </script>';
            
        // = show_logo
            if ($mysoc->logo) {
                    if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
                            $urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file=thumbs/'.urlencode($mysoc->logo_small);
                    }
            }

            if (!$urllogo && (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png')))
            {
                    $urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
            }

            print '<div style="text-align:center"><img alt="Logo" id="logosubscribe" style="max-height:100px;" title="" src="'.$urllogo.'"/>'
                  .'<h1>'.$title.'</h1>'.(!empty($subtitle) ? $subtitle : '').'</div>';
            print '<br>';

        print '<style>'
                . '.wrapper{margin-left: 50px; margin-right: 50px;}'
                . '@media (max-width: 600px) {.wrapper{margin-left: 10px; margin-right: 15px;}}'
                . '</style>';
            
	print '<div class="wrapper">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterPurchasesPublic()
{
	print '</div>';

	printCommonFooter('public');

	dol_htmloutput_events();

	print "</body>\n";
	print "</html>\n";
}


