<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

header('Content-Type: application/json');

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . "/../class/apiHelper.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnect.class.php";

$jsonData = file_get_contents("php://input");
$jsonrpc = new jsonrpc($jsonData);

if ($jsonrpc->getJsonrpc() != '2.0') {
  throw new Exception(__('Requête invalide. Version JSON-RPC invalide : ', __FILE__) . $jsonrpc->getJsonrpc(), -32001);
}

try {

  $params = $jsonrpc->getParams();
  $method = $jsonrpc->getMethod();

  $skipLog = in_array($method, apiHelper::$_skipLog);

  if (!$skipLog) JCLog::debug('[API] HTTP Received ' . JeedomConnectUtils::hideSensitiveData($jsonData, 'receive'));


  $apiKey = ($method == 'GEOLOC') ? $jsonrpc->getId() : ($params['apiKey'] ?? null);
  /** @var JeedomConnect $eqLogic */
  $eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

  $noEqLogicRequired  = array(
    'GET_PLUGIN_CONFIG',
    'GET_AVAILABLE_EQUIPEMENT',
    'PING',
    'CHECK_AUTHENT',
    'CHECK_USER',
    'VERIF_2FA'
  );
  if (!is_object($eqLogic) && !in_array($method, $noEqLogicRequired)) {
    $hasNewApiKey = apiHelper::isApiKeyRegenerated($apiKey);
    if (!$hasNewApiKey) {
      throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
    } else {
      $result = apiHelper::getApiKeyRegenerated($apiKey);
      JCLog::debug('[API] No answer for ' . $method . ' || Sending new apiKey info -> ' . json_encode($result));
      $jsonrpc->makeSuccess($result);
    }
  }

  $result = apiHelper::dispatch('API', $method, $eqLogic, $params ?? array(), $apiKey);
  if (!$skipLog) JCLog::debug('[API] Send ' . $method . ' -> ' . JeedomConnectUtils::hideSensitiveData(json_encode($result), 'send'));


  if (is_null($result)) {
    return $jsonrpc->makeSuccess();
  }
  return $jsonrpc->makeSuccess($result);
} catch (Exception $e) {

  if ($skipLog) JCLog::debug('[API] HTTP Received ' . JeedomConnectUtils::hideSensitiveData($jsonData, 'receive'));

  $result = apiHelper::raiseException($e->getMessage(), $method);
  // JCLog::error('[API] Send ' . $method . ' -> ' . json_encode($result));
  $jsonrpc->makeSuccess($result);
}
