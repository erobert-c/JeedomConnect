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

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
require_once dirname(__FILE__) . "/../class/apiHelper.class.php";
require_once dirname(__FILE__) . "/../class/JeedomConnectActions.class.php";

ob_end_clean();


function sse($data = null) {
  if (!is_null($data)) {
    echo "data:" . json_encode($data);
    echo "\r\n\r\n";
    if (@ob_get_level() > 0) for ($i = 0; $i < @ob_get_level(); $i++) @ob_flush();
    @flush();
  }
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
ignore_user_abort(true);


$apiKey = init('apiKey');
/** @var \JeedomConnect */
$eqLogic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

try {
  if (!is_object($eqLogic)) {
    JCLog::debug("Can't find eqLogic");
    throw new Exception(__("Can't find eqLogic", __FILE__), -32699);
  }
  $id = rand(0, 1000);
  JCLog::debug("eventServer init client #" . $id);


  $config = $eqLogic->getConfig(true);
  $lastReadTimestamp = time();
  $step = 0;

  sse(
    json_encode(array('infos' => array(
      'cmdInfo' => apiHelper::getCmdInfoData($config, false),
      'scInfo' => apiHelper::getScenarioData($config, false, false),
      'objInfo' => apiHelper::getObjectData($config, false)
    )))
  );

  $eqLogic->setConfiguration('sessionId', $id);
  $eqLogic->setConfiguration('connected', 1);
  $eqLogic->setConfiguration('scAll', 0);
  $eqLogic->setConfiguration('appState', 'active');
  $eqLogic->save(true);

  while (true) {
    /** @var \JeedomConnect */
    $logic = eqLogic::byLogicalId($apiKey, 'JeedomConnect');

    if (!is_object($logic)) {
      throw new Exception("EqLogic not found anymore");
    }

    if (connection_aborted() || connection_status() != CONNECTION_NORMAL) {
      JCLog::debug("eventServer connexion closed for client #" . $id);
      if ($logic->getConfiguration('sessionId', 0) == $id) {
        $logic->setConfiguration('connected', 0);
        $eqLogic->setConfiguration('appState', 'background');
        $logic->save();
      }
      die();
    }

    $actions = JeedomConnectActions::getAllActions($apiKey);
    if (count($actions) > 0) {
      $result = array(
        'type' => 'ACTIONS',
        'payload' => array()
      );
      foreach ($actions as $action) {
        array_push($result['payload'], $action['value']['payload']);
      }
      JCLog::debug("send action to #{$id}  " . json_encode(array($result)));
      sse(json_encode(array($result)));
      JeedomConnectActions::removeActions($actions);
      sleep(1);
    }

    $sendInfo = false;

    if ($logic->getConfiguration('appState') == 'active') {
      $newConfig = apiHelper::lookForNewConfig(eqLogic::byLogicalId($apiKey, 'JeedomConnect'), $config['payload']['configVersion']);
      if ($newConfig != false && $newConfig['payload']['configVersion'] != $config['payload']['configVersion']) {
        JCLog::debug("eventServer send new config : " .  $newConfig['payload']['configVersion'] . ", old=" .  $config['payload']['configVersion']);
        $config = $newConfig;
        sse(
          json_encode(array('infos' => array(
            'cmdInfo' => apiHelper::getCmdInfoData($config, false),
            'scInfo' => apiHelper::getScenarioData($config, false, false),
            'objInfo' => apiHelper::getObjectData($config, false)
          )))
        );
        sse(json_encode(array($newConfig)));
        //sleep(1);
      }

      $data = apiHelper::getEventsFull($eqLogic, $lastReadTimestamp);

      foreach ($data as $res) {
        if (key_exists('payload', $res)) {
          if (is_array($res['payload']) && count($res['payload']) == 0) {
            $sendInfo = false;
          } else {
            $sendInfo = true;
          }
          break;
        }
      }

      if ($sendInfo) {
        //JCLog::debug("eventServer send ".json_encode($data));
        sse(json_encode($data));
        $step = 0;
        $lastReadTimestamp = $data[0]['payload'];
      }
    }
    if (!$sendInfo) {
      $step += 1;
      if ($step == 5) {
        //JCLog::debug("eventServer heartbeat to #" . $id);
        sse(json_encode(array('event' => 'heartbeat')));
        $step = 0;
      }
    }
    sleep(1);
  }
} catch (Exception $e) {
  // JCLog::error('on sse ' . $e->getMessage());
  $result = apiHelper::raiseException($e->getMessage(), 'SSE');
  sse(json_encode($result));
}
