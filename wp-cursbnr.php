<?php
/**
Plugin Name: Curs BNR
Plugin URI: https://github.com/relu/wp-cursbnr
Description: Cursul BNR oferit de http://www.bnro.ro
Author: Aurel Canciu
Version: 1.1
Author URI: https://github.com/relu
License: GPLv2 or later
*/

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
?>
<?php

/**
 * Registers the [curs_bnr] shortcode
 * Accepts currency codes (ex. USD, EUR) as attributes
 * and an optional 'nocss' attribute to disable the
 * default css styling
 */
add_shortcode('curs_bnr', 'cbnr_the_exchange');

/**
 * Register the widget
 */
add_action('widgets_init', create_function('', 'register_widget("CursBNR_Widget");'));

/**
 * Get XML from bnro.ro as SimpleXML
 */
function cbnr_get_xml() {
	$xmldoc = simplexml_load_file('http://www.bnro.ro/nbrfxrates.xml');

	return $xmldoc->Body;
}

/**
 * Extract rates
 */
function cbnr_get_rates() {
	$xml = cbnr_get_xml();

	if (false === $xml)
		return false;

	$rates = array();

	foreach ($xml->Cube->Rate as $rate) {
		$currency = (string) strtolower($rate->attributes()->currency);
		$rates[$currency] = (double) $rate;
	}

	return $rates;
}

/**
 * Output the exchange rates
 * This is the shortcode callback function
 */
function cbnr_the_exchange($attrs = '') {
	$rates = cbnr_get_rates();

	if (is_array($attrs)) {
		foreach ($attrs as &$atr)
			$atr = strtolower($atr);

		unset ($atr);

		$rates = wp_array_slice_assoc($rates, $attrs);
	}

	if (empty($rates))
		return;

	$html = '';

	if (! is_array($attrs) || ! in_array('nocss', $attrs))
		$html .= '<style type="text/css">@import url("' . plugins_url('style.css', __FILE__) . '");</style>';

	$html .= '<div class="cbnr">';
	$html .= '	<h3>Curs Valutar <img src="' . cbnr_get_icon_url('ron') . '"></h3>';
	$html .= '	<div class="cbnr_date">' . date('j F Y') . '</div>';

	foreach ($rates as $key => $value) {
		$html .= '	<div class="cbnr_row">';
		$html .= '		<div class="cnbr_flag"><img src="' . cbnr_get_icon_url($key) . '" title="' . strtoupper($key) . '"></div>';
		$html .= '		<div class="cbnr_currency">' . strtoupper($key) . '</div>';
		$html .= '		<div class="cbnr_value">' . $value . ' RON</div>';
		$html .= '	</div>';
	}
		$html .= '	<span class="cbnr_credits">Curs oferit de <a href="http://www.bnro.ro">Banca Națională a României</a></span>';
	$html .= '</div>';

	return $html;
}

/**
 * Helper function to get the country flag icon for every exchange rate
 */
function cbnr_get_icon_url($currency) {
	$base_dir = trailingslashit(dirname(__FILE__));
	$icon = 'icons/' . strtolower($currency) . '.png';

	if (file_exists($base_dir . $icon ))
		$icon_src = plugins_url($icon, __FILE__);
	else
		$icon_src = '';

	return $icon_src;
}

/**
 * The widget
 */
class CursBNR_Widget extends WP_Widget {
	function __construct() {
		parent::WP_Widget(
			'cursbnr_widget',
			'Curs BNR',
			array('description' => 'Cursul oficial al Băncii Naționale Române')
		);
	}

	function widget($args, $instance) {
		extract($args);

		$currencies = implode(' ', (array) $instance['currencies']);
		$nocss = ($instance['nocss']) ? ' nocss' : '';

		echo $before_widget;

		echo do_shortcode('[curs_bnr ' . $currencies . $nocss . ']');

		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['currencies'] = $new_instance['currencies'];
		$instance['nocss'] = $new_instance['nocss'];

		return $instance;
	}

	function form($instance) {
		$rates = cbnr_get_rates();

		if (! empty($rates) && is_array($rates)) :
?>
		<h3><?php _e('Selectați valutele'); ?></h3>
<?php
			foreach ($rates as $key => $value) :
				$currency = strtolower($key);
				$checked = (in_array($currency, (array) $instance['currencies'])) ? 'checked="checked"' : '';
?>
				<p>
					<label for="<?php echo $this->get_field_id($currency); ?>">
						<input id="<?php echo $this->get_field_id($currency); ?>" type="checkbox" name="<?php echo $this->get_field_name('currencies'); ?>[]" value="<?php echo $currency; ?>" <?php echo $checked; ?>>
						<img src="<?php echo cbnr_get_icon_url($currency); ?>" />
						<?php echo strtoupper($key); ?>
					</label>
				</p>
<?php
			endforeach;
		endif;

		$nocsschecked = ($instance['nocss']) ? 'checked="checked"' : '';
?>
		<h3><?php _e('Stlizare CSS'); ?></h3>
		<p>
			<label for="<?php echo $this->get_field_id('nocss'); ?>">
				<input id="<?php echo $this->get_field_id('nocss'); ?>" type="checkbox" name="<?php echo $this->get_field_name('nocss'); ?>" <?php echo $nocsschecked; ?>>
				<?php _e('Exclude stilizare CSS prestabilită'); ?>
			</label>
		</p>
<?php
	}
}

?>
