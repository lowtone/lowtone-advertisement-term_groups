<?php
/*
 * Plugin Name: Advertisement Groups for Terms
 * Plugin URI: http://wordpress.lowtone.nl/plugins/advertisement-term_groups/
 * Description: Pair advertisement groups with terms.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2014, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\advertisement\term_groups
 */

namespace lowtone\advertisement\term_groups {

	use lowtone\content\packages\Package,
		lowtone\wp\terms\meta\Meta;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone", "lowtone\\wp"),
			Package::INIT_ACTIVATE => function() {

				// Install term meta
				
				Meta::install();

			},
			Package::INIT_SUCCESS => function() {

				add_action("load-edit-tags.php", function() {

					$addInput = function($term) {
						$settings = settings($term->term_id);

						echo '<tr class="lowtone advertisement term_groups">' . 
							'<th scope="row" valign="top"><label for="description">' . 
								__("Advertisement Group", "lowtone_advertisement_term_groups") . 
								'</label></th>' . 
							'<td>' .
							'<select name="lowtone_advertisement_term_groups[group_id]">' . 
							'<option value="-1">' . __("— Select —", "lowtone_advertisement_term_groups") . '</option>' . 
							implode(array_map(function($term) use ($settings) {
								return sprintf(
										'<option value="%s"%s>%s</option>', 
										$term->term_id, 
										(isset($settings["group_id"]) && $term->term_id == $settings["group_id"] ? ' selected="selected"' : ""),
										$term->name
									);
							}, get_terms("ad_group", array("orderby" => "term_group", "hide_empty" => false)))) . 
							'</select>' . 
							'<p class="description">' . __("If an advertisement group is selected the advertisement widgets and shortcodes on the page for this term will be restricted to this group.", "lowtone_advertisement_term_groups") . '</p>' . 
							'</td>' .
							'</tr>';
					};

					foreach (array("edit_category_form_fields", "edit_link_category_form_fields", "edit_tag_form_fields") as $action) 
						add_action($action, $addInput);

				}, 200);

				add_action("edit_term", function($termId) {
					if (!isset($_POST["lowtone_advertisement_term_groups"]))
						return;

					$properties = array(
							Meta::PROPERTY_TERM_ID => $termId,
							Meta::PROPERTY_META_KEY => "_lowtone_advertisement_term_groups",
						);
					
					$meta = Meta::first($properties) ?: new Meta($properties);

					$meta->{Meta::PROPERTY_META_VALUE} = $_POST["lowtone_advertisement_term_groups"];

					$meta->save();
				});

				add_filter("lowtone_advertisement_fetch_options", function($options) {
					if (!(is_tax() || is_category() || is_tag()))
						return $options;

					$term = get_queried_object();

					$settings = settings($term->term_id);

					if ($settings["group_id"] < 1) 
						return $options;

					$options["include_groups"] = array($settings["group_id"]);

					$options["exclude_groups"] = NULL;

					return $options;
				});

			}
		));

	// Functions
	
	function settings($termId) {
		$meta = Meta::first(array(
				Meta::PROPERTY_TERM_ID => $termId,
				Meta::PROPERTY_META_KEY => "_lowtone_advertisement_term_groups",
			));

		return array_merge(array(
				"group_id" => -1,
			), $meta ? $meta->{Meta::PROPERTY_META_VALUE} : array());
	}

}