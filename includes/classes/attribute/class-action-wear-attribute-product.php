<?php

namespace AC_SYNC\Includes\Classes\Attribute {

	use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

	class Action_Wear_Attribute_Product
	{

		private $attribute;
		private $raw_attribute;
		private $product;

		public function __construct($attr)
		{
			$this->raw_attribute = $attr;
			$this->attribute = new \WC_Product_Attribute();
			$name = ($attr["is_variation"] === true || $attr["selected"] === true) ? "pa_" . $attr["slug"] : $attr["name"];
			$this->attribute->set_id(wc_attribute_taxonomy_id_by_name($name));
			$this->attribute->set_name($name);
			$this->attribute->set_position(0);
			$this->attribute->set_visible(true);
			$this->attribute->set_variation($attr["is_variation"]);
		}

		public function getAttribute()
		{
			return $this->attribute;
		}

		public function setProduct($product)
		{
			$this->product = $product;
			return $this;
		}

		public function getProduct()
		{
			return $this->product;
		}

		public function isProcessable()
		{
			// is always processable if is one of nome_colore, camac_size, camac_color_group
			if (in_array($this->raw_attribute["prop_name"], ["nome_colore", "camac_size", "camac_color_group"])) return true;
			return $this->product->{$this->raw_attribute["prop_name"]} !== false;
		}

		public function getTerms()
		{
			$ids = [];
			$terms = [];
			$prefix = $this->raw_attribute["selected"] === true ? "pa_" : "";

			// if attributes is flagged as variation or camac_color_group, get terms from simple products
			if ($this->raw_attribute["is_variation"] === true || $this->raw_attribute["prop_name"] === "camac_color_group") {
				foreach ($this->product->simple_products as $simple) {
					$term_value = $simple->{$this->raw_attribute["prop_name"]};
					if (empty($term_value)) continue;
					$term_value = $this->cleanTermValueByAttribute($term_value);
					if (!in_array($term_value, $terms)) $terms[] = $term_value;
				}
			}
			// else get terms from product
			else {
				$term_value = $this->product->{$this->raw_attribute["prop_name"]};
				if (empty($term_value)) return;
				$term_value = $this->cleanTermValueByAttribute($term_value);
				if (!is_array($term_value)) $term_value = [$term_value];
				foreach ($term_value as $term_value_single) if (!in_array($term_value_single, $terms)) $terms[] = $term_value_single;
			}

			if (!$this->raw_attribute["selected"]) return $terms;

			foreach ($terms as $term) {
				if (empty($term)) continue;
				$wp_term = get_term_by("name", $term, $prefix . $this->raw_attribute["slug"], ARRAY_A);
				if ($wp_term === false) {
					$wp_term = wp_insert_term($term, $prefix . $this->raw_attribute["slug"]);
				}
				if (is_wp_error($wp_term)) {
					Log::write($wp_term->get_error_message() . " - dentro getTerms - " . json_encode($term) . " - slug: " . $this->raw_attribute["slug"], Log::ERROR, Log::CONTEXT_PRODUCT_CREATION);
					continue;
				}
				$ids[] = $wp_term["term_id"];
			}
			return $ids;
		}

		public function cleanTermValueByAttribute($term_value)
		{
			if ($this->raw_attribute["prop_name"] === "camac_color_group") {
				// check if the array is in 0 position and override it
				if (is_array($term_value[0])) $term_value = $term_value[0];
				$term_value = implode("_", $term_value);
				$term_value = str_replace("#", "", $term_value);
			}
			return $term_value;
		}

		public function setOptions()
		{
			$this->attribute->set_options($this->getTerms());
		}
	}
}
