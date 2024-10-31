<?php

namespace PopupBuilderBlock\Config;

defined('ABSPATH') || exit;

class PostMeta {
	// class initilizer method
	public function __construct() {
		add_action("gutenkit/post-meta/list", array($this, "set_pro_meta_list"));
	}

	/**
	 * Retrieves the popup settings.
	 *
	 * @return array An associative array containing the popup settings.
	 */
	public static function get_popup_settings() {
		return [
			'openTrigger' => ['type' => 'string'],
			'displayCondition' => ['type' => 'array'],
		];
	}

	/**
	 * Returns an array of default data for the popup configuration.
	 *
	 * @return array The default data for the popup configuration.
	 */
	public static function popup_default_data() {
		return [
			'openTrigger' => 'none',
			'displayCondition' => [],
		];
	}

	// register post meta
	public function set_pro_meta_list($lists) {
		$popup_settings = self::get_popup_settings();
		$popup_settings_default_data = self::popup_default_data();

		$lists = array_merge($lists, [
			"gutenkit_popup_settings" => [
				"post_type" => "gutenkit-popup",
				"args" => [
					'type'              => 'object',
					'default'           => $popup_settings_default_data,
					'single'            => true,
					'show_in_rest'      => [
						'schema' => [
							'type'       => 'object',
							'properties' => $popup_settings,
							'additionalProperties' => array(
								'type' => 'object',
							),
						]
					],
				],
			],
		]);

		return $lists;
	}
}
