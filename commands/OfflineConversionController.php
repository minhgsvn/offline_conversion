<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use app\models\OfflineConversionsConverter;
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

/**
 * Offline Conversion Integration
 *
 * This class use to handle offline conversion integration
 *
 * @author
 * @since 2.0
 */
class OfflineConversionController extends Controller
{
	const MAX_EVENTS_UPLOAD = 2000;
	/**
	 * This command use to upload json data to an Offline Event Set
	 *
	 * @param $igError: Ignore Error
	 */
	public function actionUploadEvents($igError = false) {

		$accessToken = Yii::$app->params['accessToken'];
		$eventSetId = Yii::$app->params['eventSetId'];
		$endPoint = Yii::$app->params['endPoint'];

		if (empty($accessToken) || empty($eventSetId) || empty($endPoint)) {
			echo "Please check parameters \n";
			exit;
		}

		// Get data from URL source
		$jsonContent = $this->getDataFromUrl($endPoint);
		$content = json_decode($jsonContent, true);
		$eventData = ArrayHelper::getValue($content, 'data', []);
		if (empty($eventData)) {
			echo "Don't have any data to process \n";
			exit;
		}

		// Get mapping rule
		try {
			$mappingFile = Yii::getAlias('@app/config/mappings.json');
			$mappingContent = file_get_contents($mappingFile);
			$mappingJson = json_decode($mappingContent, true);
			$mapping = ArrayHelper::getValue($mappingJson, 'mapping', []);
			if (empty($mapping)) {
				echo "Can not use empty mapping \n";
				exit;
			}
		} catch (ErrorException $e) {
			echo $e->getMessage();
			exit;
		}

		// Data processing
		$conversionData = $this->dataProcess($eventData, $mapping, self::MAX_EVENTS_UPLOAD, $igError);

		if (!empty($conversionData)) {
			$totalEvents = count($eventData);
			echo "Total events data need process: $totalEvents \n";

			$facebook = new Facebook(array(
				'app_id' => Yii::$app->params['appId'],
				'app_secret' => Yii::$app->params['secretKey'],
				'default_graph_version' => Yii::$app->params['apiVersion'],
			));

			foreach ($conversionData['data'] as $eventSet) {
				$params['data'] = $eventSet;
				$params['upload_tag'] = 'Test Upload';
				// Upload facebook
				try {
					$response = $facebook->post("/$eventSetId/events", $params, $accessToken);
					$result = $response->getDecodedBody();
					echo "Event Set Id $eventSetId : Number of data row are processed : {$result['num_processed_entries']} \n";
				} catch(FacebookResponseException $e) {
					echo 'Graph returned an error: ' . $e->getMessage();
				} catch(FacebookSDKException $e) {
					echo 'Facebook SDK returned an error: ' . $e->getMessage();
				}
			}
		}
		exit;
	}

	/**
	 * Data processing
	 *
	 * @param $data
	 * @param $mapping
	 * @param $maxUpload : max of total data for every time call API
	 * @param bool $igError
	 * @return array|bool
	 */
	protected function dataProcess($data, $mapping, $maxUpload, $igError = false) {
		$convertedData = [];
		$uploadTime = 0;
		$errorData = [];
		$counter = 0;
		foreach ($data as $row) {
			if ($counter == $maxUpload) {
				$counter = 0;
				$uploadTime++;
			}
			$result = OfflineConversionsConverter::run($mapping, $row);
			if (!empty($result)) {
				$convertedData[$uploadTime][] = $result;
				$counter++;
			} elseif (!$igError) {
				return false;
			} else {
				$errorData[] = $row;
			}
		}

		$dataProcessed = [
			'data' => $convertedData,
			'error' => $errorData
		];
		return $dataProcessed;
	}

	/**
	 * @param string $url
	 * @return mixed|string
	 */
	function getDataFromUrl($url = "") {
		try {
			$ch = curl_init();
			// define options
			$optArray = array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
			);
			// apply those options
			curl_setopt_array($ch, $optArray);
			// execute request and get response
			$result = curl_exec($ch);
		} catch (Exception $ex) {
			$result = "";
		}
		return $result;
	}
}
