<?php
/*
Plugin Name: Curs BNR
Plugin URI: 
Description: Cursul BNR oferit de http://www.bnro.ro
Author: Aurel Canciu
Version: 1.0
Author URI: https://github.com/relu
License: GPLv2 or later
*/


/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
?>
<?php



function cbnr_get_xml() {
	$xmldoc = simplexml_load_file('http://www.bnro.ro/nbrfxrates.xml');
	
	return $xmldoc->Body;
}

function cbnr_get_rates() {
	$xml = cbnr_get_xml();
	
	if (false === $xml)
		return false;
	
	$rates = array();
	
	foreach ($xml->Cube->Rate as $rate) {
		$currency = (string) $rate->attributes()->currency;
		$rates[$currency] = (double) $rate;
	}
	
	return $rates;
}

function cbnr_the_exchange($attrs = '') {
	$rates = cbnr_get_rates();
		
	if (is_array($attrs)) {
		foreach ($attrs as &$atr)
			$atr = strtoupper($atr);
			
		unset ($atr);
		
		$rates = wp_array_slice_assoc($rates, $attrs);
	}
	
	if (empty($rates))
		return false;
		
	if (! is_array($attrs) || ! in_array('NO-CSS', $attrs)) :
?>
	<style type="text/css">
		@import url('<?php echo plugins_url('style.css', __FILE__); ?>');
	</style>
<?php
	endif;
?>

	<div id="cbnr">
		<h4>Curs Valutar <img src="<?php echo cbnr_get_icon_url('ron'); ?>"></h4>
<?php
	foreach ($rates as $key => $value) {
?>

		<div class="cbnr_row">
			<div class="cnbr_flag"><img src="<?php echo cbnr_get_icon_url($key); ?>" title="<?php echo $key; ?>"></div>
			<div class="cbnr_currency"><?php echo $key; ?></div>
			<div class="cbnr_value"><?php echo $value; ?> RON</div>
		</div>

<?php
	}
?>
		<span class="cbnr_credits">Curs oferit de <a href="http://www.bnro.ro">Banca Națională a României</a></span>
	</div>
<?php
}

function cbnr_get_icon_url($currency) {
	$base_dir = trailingslashit(dirname(__FILE__));
	$icon = 'icons/' . strtolower($currency) . '.png';
		
	if (file_exists($base_dir . $icon ))
		$icon_src = plugins_url($icon, __FILE__);
	else
		$icon_src = '';
		
	return $icon_src;
}

add_shortcode('curs_bnr', 'cbnr_the_exchange');
?>
