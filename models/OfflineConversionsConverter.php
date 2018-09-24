<?php
namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class OfflineConversionsConverter extends Model 
{
	/**
	 * Convert data row of original to data format of Facebook
	 *
	 * @param $mapping
	 * @param $data
	 * @return array
	 */
    public static function run($mapping, $data)
    {
        $conversionKeys = self::getOfflineConversionKeys();
        $conversionData = [];
        $defaultEvents = ["ViewContent", "Search", "AddToCart", "AddToWishlist", "InitiateCheckout", "AddPaymentInfo", "Purchase", "Lead", "CompleteRegistration", "Other"];
        foreach ($mapping as $fbkey => $fileKey) {
            if (in_array($fbkey, $conversionKeys)) {
                $value = ArrayHelper::getValue($data, $fileKey, "");
                switch ($fbkey) {
                    case "event_name":
                        $value = in_array($value, $defaultEvents) ? $value : $fileKey;
                        break;
                    case "event_time":
                        $value = is_numeric($value) ? $value : strtotime($value);
                        break;
					case "currency":
						$value = empty($value) ? $fileKey : $value;
						break;
					case "content_ids":
						// get sku of every product item
						$temp = [];
						if (is_array($value)) {
							foreach ($value as $item) {
								$temp[] = $item['sku'];
							}
						}
						$value = $temp;
						break;
					case "custom_data.event_source":
						$value = $fileKey;
						break;
                    default :
                        break;
                }
                $conversionData[$fbkey] = $value;
            }
        }
		$stdData = self::generateData($conversionData);

        // hash data for match_keys
		$matchKeys = $stdData['match_keys'];
		foreach($matchKeys as $key => $value) {
			$stdData['match_keys'][$key] = hash('sha256', $value);
		}
        return $stdData;
    }

	/**
	 * Explode data to array with . in keys
	 *
	 * @param $data
	 * @return array
	 */
	protected static function generateData($data)
	{
		$temp = [];
		$removeKeys = [];
		foreach ($data as $key => $value) {
			$subKeys = explode(".", $key);
			if (count($subKeys) > 1) {
				list($newKey, $subKey) = $subKeys;
				$temp[$newKey][$subKey] = $value;
				$removeKeys[] = $key;
			}
		}
		foreach ($removeKeys as $key) {
			unset($data[$key]);
		}

		return array_merge($data, $temp);
	}

	/**
	 * Offline Conversion Facebook keys
	 *
	 * @return array
	 */
    protected static function getOfflineConversionKeys() {
        return [
            "match_keys.email",
            "match_keys.phone",
            "match_keys.gen",
            "match_keys.doby",
            "match_keys.dobm",
            "match_keys.dobd",
            "match_keys.ln",
            "match_keys.fn",
            "match_keys.fi",
            "match_keys.ct",
            "match_keys.st",
            "match_keys.zip",
            "match_keys.country",
            "match_keys.madid",
            "match_keys.extern_id",
            "match_keys.lead_id",
            "event_name",
            "event_time",
            "currency", 
            "value",
            "content_type",
            "content_ids",
            "order_id",
            "item_number",
            "custom_data.event_source",
            "custom_data.action_type",
            "custom_data.email_type",
            "custom_data.email_provider",
			"custom_data.category",
			"custom_data.location"
        ];
    }
}