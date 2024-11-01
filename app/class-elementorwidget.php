<?php

namespace uAfrica_Shipping\app;

if ( class_exists( '\Elementor\Widget_Base' ) ) {
	class ElementorWidget extends \Elementor\Widget_Base {

		/**
		 * @return string
		 */
		public function get_name() {
			return 'Bob Go Tracking';
		}

		/**
		 * @return string
		 */
		public function get_title() {
			return 'Bob Go Tracking';
		}

		/**
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-progress-tracker';
		}

		/**
		 * @return string[]
		 */
		public function get_categories() {
			return [ 'uafrica' ];
		}

		/**
		 * @return void
		 */
		public function render() {
			echo Shortcode::render();
		}
	}
}
