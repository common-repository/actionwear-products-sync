<?php

namespace AC_SYNC\Includes\Classes\Attribute {

	use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

	class Action_Wear_Attribute
	{

		/**
		 * Represent all attributes that can be associated to a product with own properties
		 */
		const CAMAC_ATTRIBUTES =
			[
				"colore" => [
					"name" => "Colore",
					"selected" => true,
					"is_variation" => true,
					"prop_name" => "nome_colore",
					"slug" => "colore"
				],
				"taglia" => [
					"name" => "Taglia",
					"selected" => true,
					"is_variation" => true,
					"prop_name" => "camac_size",
					"slug" => "taglia"
				],
				"camac_color_group" => [
					"name" => "Gruppo colore",
					"selected" => true,
					"is_variation" => false,
					"prop_name" => "camac_color_group",
					"slug" => "camac_color_group"
				],
				"camac_genere" => [
					"name" => "Genere",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_genere",
					"slug" => "camac_genere"
				],
				"camac_dettagli" => [
					"name" => "Dettagli",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_dettagli",
					"slug" => "camac_dettagli"
				],
				"camac_busto" => [
					"name" => "Busto",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_busto",
					"slug" => "camac_busto"
				],
				"camac_collo" => [
					"name" => "Collo",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_collo",
					"slug" => "camac_collo"
				],
				"camac_fit" => [
					"name" => "Fit",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_fit",
					"slug" => "camac_fit"
				],
				"camac_maniche" => [
					"name" => "Maniche",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_maniche",
					"slug" => "camac_maniche"
				],
				"camac_sostenibile" => [
					"name" => "Sostenibile",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_sostenibile",
					"slug" => "camac_sostenibile"
				],
				"camac_tessuto" => [
					"name" => "Tessuto",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "camac_tessuto",
					"slug" => "camac_tessuto"
				],
				"country_of_manufacture" => [
					"name" => "Prodotto in",
					"selected" => null,
					"is_variation" => false,
					"prop_name" => "country_of_manufacture",
					"slug" => "country_of_manufacture"
				],
			];

		public static function getAttributes()
		{
			$taxonomies = json_decode(get_option('_ACTIONWEAR_SELECTED_TAXONOMIES', "[]"), true);
			$attributes = [];
			foreach (self::CAMAC_ATTRIBUTES as $_attribute_key => $_attribute_value) {
				$attributes[$_attribute_key] = [
					"name" => $_attribute_value["name"],
					"selected" => $_attribute_value["selected"] === null ? in_array($_attribute_key, $taxonomies) : $_attribute_value["selected"],
					"is_variation" => $_attribute_value["is_variation"],
					"prop_name" => $_attribute_value["prop_name"],
					"slug" => $_attribute_value["slug"],
				];
			}
			return $attributes;
		}

		public static function getDefaultAttributes()
		{
			return [
				self::CAMAC_ATTRIBUTES["colore"],
				self::CAMAC_ATTRIBUTES["taglia"],
				self::CAMAC_ATTRIBUTES["camac_color_group"],
			];
		}

		public static function getAttributesWithoutDefaults()
		{
			return array_filter(self::getAttributes(), function ($attribute) {
				return !in_array($attribute, self::getDefaultAttributes());
			});
		}

		public static function getAllSelectedAttributes()
		{
			return array_filter(self::getAttributes(), function ($attribute) {
				return $attribute["selected"];
			});
		}

		/**
		 * Set all attributes in WooCommerce and associate relative terms, depends on selected taxonomies attribute
		 * @return array of Action_Wear_Attribute_Product
		 */
		public static function setAttributes($product)
		{
			$attrs = self::getAttributes();
			$return_attrs = [];
			foreach ($attrs as $attr) {
				Log::write("Processo attributo " . json_encode($attr), Log::INFO, Log::CONTEXT_PRODUCT_CREATION);
				$attrObject = new Action_Wear_Attribute_Product($attr);
				$attrObject->setProduct($product);
				if (!$attrObject->isProcessable())
					continue;
				$attrObject->setOptions();
				$return_attrs[] = $attrObject->getAttribute();
			}
			return $return_attrs;
		}

		public function setSelectedAttributes($selected_attributes)
		{
			return update_option('_ACTIONWEAR_SELECTED_TAXONOMIES', $selected_attributes);
		}
	}
}
