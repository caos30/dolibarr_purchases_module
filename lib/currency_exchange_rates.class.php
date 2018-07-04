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
 *	\file       htdocs/purchases/lib/currency_exchange_rates.class.php
 *      \ingroup    purchases
 *      \brief      PHP class to get and return exchange rates, retrieved from IFM
 *      \version    v 1.0 2017/11/20
 */

class CurrencyExchangeRates 
{
    
    var $currencies = array(
        'Chinese Yuan' => 'CNY',
        'Euro' => 'EUR',
        'Japanese Yen' => 'JPY',
        'U.K. Pound Sterling' => 'GBP',
        'U.S. Dollar' => 'USD',
        'Algerian Dinar' => 'DZD',
        'Australian Dollar' => 'AUD',
        'Bahrain Dinar' => 'BHD',
        'Botswana Pula' => 'BWP',
        'Brazilian Real' => 'BRL',
        'Brunei Dollar' => 'BND',
        'Canadian Dollar' => 'CAD',
        'Chilean Peso' => 'CLP',
        'Colombian Peso' => 'COP',
        'Czech Koruna' => 'CZK',
        'Danish Krone' => 'DKK',
        'Hungarian Forint' => 'HUF',
        'Icelandic Krona' => 'ISK',
        'Indian Rupee' => 'INR',
        'Indonesian Rupiah' => 'IDR',
        'Iranian Rial' => 'IRR',
        'Israeli New Sheqel' => 'ILS',
        'Kazakhstani Tenge' => 'KZT',
        'Korean Won' => 'KRW',
        'Kuwaiti Dinar' => 'KWD',
        'Libyan Dinar' => 'LYD',
        'Malaysian Ringgit' => 'MYR',
        'Mauritian Rupee' => 'MUR',
        'Mexican Peso' => 'MXN',
        'Nepalese Rupee' => 'NPR',
        'New Zealand Dollar' => 'NZD',
        'Norwegian Krone' => 'NOK',
        'Rial Omani' => 'OMR',
        'Pakistani Rupee' => 'PKR',
        'Philippine Peso' => 'PHP',
        'Polish Zloty' => 'PLN',
        'Qatar Riyal' => 'QAR',
        'Russian Ruble' => 'RUB',
        'Saudi Arabian Riyal' => 'SAR',
        'Singapore Dollar' => 'SGD',
        'South African Rand' => 'ZAR',
        'Sri Lanka Rupee' => 'LKR',
        'Swedish Krona' => 'SEK',
        'Swiss Franc' => 'CHF',
        'Thai Baht' => 'THB',
        'Trinidad And Tobago Dollar' => 'TTD',
        'Tunisian Dinar' => 'TND',
        'U.A.E. Dirham' => 'AED',
        'Peso Uruguayo' => 'UYU',
        'Bolivar Fuerte' => 'BOB',
    );
    
    /**
     *      \brief      Constructor
     *      \param      
     */
    function __construct() 
    {
        return 1;
    }

	
    /**
     *      \brief      getRates, from remote IMF (by default) or from local if the last data is enough close
     *      \param      curr                Currency from which we have to return their exchange rates
     *      \param      url                 By default the IMF url with today date
     *      \param      refresh_seconds	    How many seconds must pass until to force the call to the IMF again (12h by default)
     *      \return     array         	
     */
    function getRates($curr = 'MXN', $url = '', $refresh_seconds = 43200)
    {
        
        // = directory to save data
            $dir = DOL_DOCUMENT_ROOT.'/../documents/purchases';
            if (!is_dir($dir)) mkdir($dir);
            
        // = default data source is the International Monetary Fund
            if ($url == '') $url = 'http://www.imf.org/external/np/fin/data/rms_mth.aspx?SelectDate='.date('Y-m-d').'&reportType=REP&tsvflag=Y';
        
        // = read the timestamp of the last call to IMF, and decide if we need to load again remote data
            $ts_now = time();
            $get_remote = false;
            if (!file_exists($dir.'/ExchangeCurrenciesRates.txt')){
                $get_remote = true;
            }else{
                $last_ts = file_get_contents($dir.'/ExchangeCurrenciesRates.txt');
                if ($ts_now - intval($last_ts) > intval($refresh_seconds)) $get_remote = true;
            }

        // = get remote file
            if ($get_remote){
                $content = file_get_contents($url);
                file_put_contents($dir.'/ExchangeCurrenciesRates.tsv', $content); // tabulator separated
                file_put_contents($dir.'/ExchangeCurrenciesRates.txt', $ts_now); // current timestamp
            }
                            
        // = read data file and get USD exchange rates
            $handler = fopen($dir.'/ExchangeCurrenciesRates.tsv', 'r');
            $USDrates = array();
            while (($buffer = fgets($handler, 4096)) !== false) {
                $ex = explode("\t",$buffer);
                $curr_name = trim(str_replace('(1)','',$ex[0]));
                if (count($ex) < 3 || $ex[0]=='Currency' || !isset($this->currencies[$curr_name])) continue;
                $n = count($ex);
                if (floatval($ex[$n-1])>0)         $rate = floatval($ex[$n-1]);
                else if (floatval($ex[$n-2])>0)    $rate = floatval($ex[$n-2]);
                else if (floatval($ex[$n-3])>0)    $rate = floatval($ex[$n-3]);
                else                            continue;
                //== in the IMF file some currencies come with a "(1)" in their name indicating that the exchange is inverted
                if (preg_match("/\(1\)$/",trim($ex[0]))) 
                    $USDrates[$this->currencies[$curr_name]] = 1 / $rate;
                else
                    $USDrates[$this->currencies[$curr_name]] = $rate;
            }
            
        // = calculate rates for the requested currency if it's not USD
            if (!isset($USDrates[$curr])) return array();
            if ($curr == 'USD') return $USDrates;
            
            $rates = array();
            $rates[$curr] = 1;
            $rates['USD'] = 1 / $USDrates[$curr];
            foreach($USDrates as $cod=>$r){
                if ($cod == 'USD' || $cod == $curr) continue;
                $rates[$cod] = $rates['USD'] * $r;
            }
            
            return $rates;

    }
    
}
?>
