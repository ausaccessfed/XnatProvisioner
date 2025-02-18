<?php
/*
 * COmanage Registry CO Dataverse Provisioner Target Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v4.3.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");
App::uses("CoService", "Model");

class CoXnatProvisionerTarget extends CoProvisionerPluginTarget {
  // Define class name for cake
  public $name = "CoXnatProvisionerTarget";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "CoProvisioningTarget",
    "Server",
    "CoGroup"
  );
  
  // Default display field for cake generated views
  public $displayField = "server_id";
  
  // Request Http servers
  public $cmServerType = ServerEnum::HttpServer;
  
  // Instance of CoHttpClient for Xnat server
  protected $Http = null;

  // Pre-defined XNAT Roles available to $xnatDefinedRoles
  static $rolesForXnat = array('xnatmember','xnatcollaborator','xnatowner');

  // Pre-defined value project description 
  // Use a value other than a single space to handle XNAT's refusal to accept an null value for a Project description
  static $emptyDescritptionValue = "-";

      
  // Validation rules for table elements
  public $validate = array(
    'co_provisioning_target_id' => array(
      'rule' => 'numeric',
      'required' => true        // add  ", 'allowEmpty' => false"    ??
    ),
    'server_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'unfreeze' => 'CO'      // add  ", 'allowEmpty' => false"    ??
      )
    ),

    'identifier_type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
                        array('attribute' => 'Identifier.type',
                              'default' => array(IdentifierEnum::ePPN,
                                                 IdentifierEnum::ePTID,
                                                 IdentifierEnum::ePUID,
                                                 IdentifierEnum::Mail,
                                                 IdentifierEnum::OIDCsub,
                                                 IdentifierEnum::OpenID,
                                                 IdentifierEnum::ORCID,
                                                 IdentifierEnum::SamlPairwise,
                                                 IdentifierEnum::SamlSubject,
                                                 IdentifierEnum::UID))),
        'required' => true,
        'allowEmpty' => false
      )
    ),

    'co_group_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
        'unfreeze' => 'CO'
      )
    )
  );

/* We could sanity check the server configuration here, but we'd need to
     pull the HttpServer object to get it. 
*/    
  public function beforeSave($options = array()) {
    return true;
  } 

  /**
   * Provision for the specified CO Person.
   *  
   * @since  COmanage Registry v4.3.4
   * @param  Array                  $coProvisioningTargetData CO Provisioning Target data
   * @param  ProvisioningActionEnum $op                       Registry trans action type triggering provisioning
   * @param  Array                  $provisioningData         Provisioning data, populated with ['CoPerson'] or ['CoGroup'] or ['CoService']?
   * @return Boolean True on success
   * 
   * 
   * check - the need to add this for get & post/put operations
   *     if ($response->code < 200 || $response->code > 299) {
   *   throw new RuntimeException($response->reasonPhrase);
   * }
  */

  public function provision($coProvisioningTargetData, $op, $provisioningData) {
    $this->log("FUNCTION provision - OP = " . print_r($op, true));
    //$this->log("FUNCTION provision: coProvisioningTargetData: " . print_r($coProvisioningTargetData, true));
    //$this->log("FUNCTION provision: provisioningData: " . print_r($provisioningData['CoPersonRole'], true));

    $deleteGroup = false;
    $syncGroup = false;
    $deletePerson = false;
    $syncPerson = false;
    $syncService = false;

    switch($op) {
      case ProvisioningActionEnum::CoGroupAdded:
      case ProvisioningActionEnum::CoGroupUpdated:
      case ProvisioningActionEnum::CoGroupReprovisionRequested:
        $syncGroup = true;
        break;
      case ProvisioningActionEnum::CoGroupDeleted:
        $deleteGroup = true;
        break;
      case ProvisioningActionEnum::CoPersonAdded:
      case ProvisioningActionEnum::CoPersonEnteredGracePeriod:
      case ProvisioningActionEnum::CoPersonExpired:
      case ProvisioningActionEnum::CoPersonPetitionProvisioned:
      case ProvisioningActionEnum::CoPersonPipelineProvisioned:
      case ProvisioningActionEnum::CoPersonReprovisionRequested:
      case ProvisioningActionEnum::CoPersonUnexpired:
      case ProvisioningActionEnum::CoPersonUpdated:
        if ($provisioningData['CoPerson']['status'] == StatusEnum::Deleted) {
          $deletePerson = true;
        } else {
          $syncPerson = true;
        }
        break;
      case ProvisioningActionEnum::CoPersonDeleted:
        $deletePerson = true;
        break;
      case ProvisioningActionEnum::CoServiceUpdated:
      case ProvisioningActionEnum::CoServiceAdded:
      case ProvisioningActionEnum::CoServiceReprovisionRequested:
      //case ProvisioningActionEnum::CoServiceDeleted:
        $syncService = true;
        break;
      default:
        // Ignore all other actions. Note group membership changes
        // are typically handled as CoPersonUpdated events.
        return true;
        break;
    }

    // determine which actions require updates to Xnat
    if ($syncService || $syncPerson) {  
      //revisit to confirm what i should put this here to .... Check session token exists
      $this -> createHttpClient($coProvisioningTargetData, $provisioningData, "notype");
    }

    // if ($deletePerson) {
      // account deleteion is not allowed in XNAT
    // }

    if($syncService) {
      $this -> syncProject($coProvisioningTargetData, $provisioningData); 
    }

    if ( $syncPerson ) {  // check $op status -> PU? 
      $uRoles = array();
      $uRoles = $this -> listUserProjectGroups($coProvisioningTargetData, $provisioningData);

      if (!empty($coProvisioningTargetData)) {
        $this -> syncPerson($coProvisioningTargetData, $provisioningData, $uRoles);
        $this -> syncUserRoles($coProvisioningTargetData, $provisioningData, $uRoles);
      }
    }
  }

  /*
  * Check JSESSION token is valid
  *
  * @since   COmanage Registry v4.3.4
  * @param   Array  $coProvisioningTargetData   CO Provisioning target data
  * @return  boolean
  * @throws  ??
  */

  protected function checkSessionToken($coProvisioningTargetData) {
    $this->log("FUNCTION checkSessionToken");
    if ( empty($coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_estimated_expiration_time']) ||
         empty($coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_jsession']) ) {
      return false;
    }
    // Check if JSESSION token expired and allow for a variance
    // ExpireTime - Now - V > 0 
    $variance = 600000;
    $renewToken = $coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_estimated_expiration_time'] - round(microtime(true)*1000) - $variance;
    if ($renewToken > 0 ) {
      return true;
    } 
    return false;
  }

  /*
  * Create HTTP client connected to XNAT server and setup JSESSION cookie using PHP Curl functions
  *
  * @since   COmanage Registry v4.3.4
  * @param   Array  $coProvisioningTargetData CO Provisioning target data
  * @param   Array  $provisioningData         
  * @param   string $type                     http content type xml|json|notype
  * @return  Array  $CoHttpClient             CoHttpClient or empty array
  * @throws  InvalidArgumentException
  */

  protected function createHttpClient($coProvisioningTargetData, $provisioningData, $type) {
    $this->log("FUNCTION createHttpClient with type: " . print_r($type, true));
    //$this->log("FUNCTION createHttpClient - coProvisioningTargetData: " . print_r($coProvisioningTargetData, true));

    $args = array();
    $args['conditions']['Server.id'] = $coProvisioningTargetData['CoXnatProvisionerTarget']['server_id'];
    $args['conditions']['Server.status'] = SuspendableStatusEnum::Active;
    $args['contain'] = array('HttpServer');
    //$this->log("FUNCTION createHttpClient - args: " . print_r($args, true));

    $CoProvisioningTarget = new CoProvisioningTarget();
    $srvr = $CoProvisioningTarget->Co->Server->find('first', $args);
    //$this->log("FUNCTION createHttpClient - srvr: " . print_r($srvr['Server'], true));
    if (empty($srvr)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $coProvisioningTargetData['CoXnatProvisionerTarget']['server_id'])));
    }

    if ( empty($this -> checkSessionToken($coProvisioningTargetData)) ) {
      //$this->log("FUNCTION createHttpClient - get a new token");
      $jToken = array(); 
      $jToken = $this->getXnatSessionToken($coProvisioningTargetData, $srvr);
      //$this->log("FUNCTION createHttpClient - New jToken: " . print_r($jToken, true));

      if (isset($jToken)) {
        //$this->log("FUNCTION createHttpClient - save token");
        $this -> saveJessionData($coProvisioningTargetData['CoXnatProvisionerTarget'], $jToken);
      }    
    }

    if (isset($coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_jsession'])) {  // is this a suitable check that the token is saved?
      //$this->log("FUNCTION createHttpClient - token IS saved!!");

      $this -> Http = new CoHttpClient();
      $this -> Http->setConfig($srvr['HttpServer']);
      $this -> Http->setConfig($srvr['HttpServer']['username'] = null);   // Enforce sending JSESSION cookie, not username & password
      $this -> Http->setConfig($srvr['HttpServer']['password'] = null);
      $jsession = $coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_jsession'];
      
      if ( ($type == "json") || ($type == "xml") ) {
        $this -> Http->setRequestOptions(array(
          'header' => array(
            'Accept'        => "application/$type",
            'Content-Type'  => "application/$type; charset=UTF-8",
            'cookie'        => "JSESSIONID=$jsession"
          )
        ));
      } 
      if ($type == "notype") {
        $this -> Http->setRequestOptions(array(
          'header' => array(
              'cookie' => "JSESSIONID=$jsession"
          )
        ));
      }
      return $this; 
    }
    return array();     // update to return a suitable error condition here instead of empty array?
  }

  /* 
  * Provision a project to XNAT
  * 
  * @since  COmanage Registry v4.3.4
  * @param  Array $coProvisioningTargetData CO Provisioning target data
  * @param  Array $provisioningData   Provisioning Data
  * @return 
  * @throws InvalidArgumentException
  *
  */

  protected function createProject($coProvisioningTargetData, $provisioningData) {
    $this->log("FUNCTION createProject: ");
    //$this->log("FUNCTION createProject - project id prefix: " . print_r($coProvisioningTargetData['CoXnatProvisionerTarget']['project_id_prefix'], true));
    //$this->log("FUNCTION createProject - project delimiter: " . print_r($coProvisioningTargetData['CoXnatProvisionerTarget']['project_name_delimiter'], true));
    
    $xnatProjectIdPrefix = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_id_prefix'];
    $xnatProjectDelimiter = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_name_delimiter'];

    $xnatProjectId = $xnatProjectIdPrefix . strtolower($provisioningData['CoService']['short_label']);
    $xnatProjectTitle = $provisioningData['CoService']['name'];
    $xnatRunningTitle = $xnatProjectId . $xnatProjectDelimiter . $xnatProjectTitle;
    $xnatDescription =  $provisioningData['CoService']['description'];

    $xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
            <xnat:Project ID='$xnatProjectId' 
              secondary_ID='$xnatRunningTitle' 
              active='1' 
              xmlns:arc='http://nrg.wustl.edu/arc' 
              xmlns:val='http://nrg.wustl.edu/val' 
              xmlns:pipe='http://nrg.wustl.edu/pipe' 
              xmlns:icr='http://icr.ac.uk/icr'
              xmlns:wrk='http://nrg.wustl.edu/workflow' 
              xmlns:scr='http://nrg.wustl.edu/scr' 
              xmlns:xdat='http://nrg.wustl.edu/security' 
              xmlns:cat='http://nrg.wustl.edu/catalog' 
              xmlns:prov='http://www.nbirn.net/prov' 
              xmlns:xnat='http://nrg.wustl.edu/xnat' 
              xmlns:xnat_a='http://nrg.wustl.edu/xnat_assessments' 
              xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
              xsi:schemaLocation='https://xnat-aaf.ais.sydney.edu.au/schemas/workflow.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/catalog.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/repository.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/screeningAssessment.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/project.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/roi.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/protocolValidation.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/xnat.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/assessments.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/birnprov.xsd 
                https://xnat-aaf.ais.sydney.edu.au/schemas/security.xsd'>
              <xnat:name>$xnatProjectTitle</xnat:name>
              <xnat:description>$xnatDescription</xnat:description>
            </xnat:Project>";

    $this -> createHttpClient($coProvisioningTargetData, $provisioningData, "xml");
    $xnatPath = "data/projects";
    //$this->log("jsonMessage: " . print_r($jsonMessage, true));
    $response = $this->Http->post("/" . $xnatPath, $xml);
    $this->log("FUNCTION createProject - RESPONSE CODE: " . print_r($response->code, true));
    if ($response->code < 200 || $response->code > 299) {
      throw new RuntimeException($response->reasonPhrase);
    }
    return;
  }

  /**
   * Find a single project in XNAT
   *
   * @since  COmanage Registry v4.0.0
   * @param  array $coProvisioningTargetData    co Provisioning Target Data
   * @param  array $provisioningData            provisioning Data
   * @return array $xnatProject                 XNat project details or empty array
   * 
   */
  
   protected function findOneXnatProject($coProvisioningTargetData, $provisioningData) {
    $this->log("FUNCTION findXnatProject");
    //$this->log("FUNCTION findXnatProject - coProvisioningTargetData: " . print_r($coProvisioningTargetData, true));
    //$this->log("FUNCTION findXnatProject - provisioningData: " . print_r($provisioningData, true));
    
    if ($coProvisioningTargetData['CoXnatProvisionerTarget']['co_group_id'] == $provisioningData['CoService']['co_group_id']){
      $xnatProjectIdPrefix = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_id_prefix'];
      $this->log("FUNCTION findXnatProject - xnatProjectIdPrefix: " . print_r($xnatProjectIdPrefix, true));

      $xnatProjectId = $xnatProjectIdPrefix . strtolower($provisioningData['CoService']['short_label']);
      //$this->log("FUNCTION findXnatProject - xnatProjectId: " . print_r($xnatProjectId, true));
      $xnatPath = "data/projects/" . $xnatProjectId . "?format=json";     // must have a format otherwise returns html
      $this -> createHttpClient($coProvisioningTargetData, $provisioningData, "notype");
      $xnatProjectList = array();
      $response = json_decode($this->Http->get("/" . $xnatPath), true);
      $this->log("Response: " . print_r($response, true));
      if (!empty($response)) {
        //$this->log("XNAT response: " . print_r($response, true));
        $xnatProjectList = ['ID'           => $response['items'][0]['data_fields']['ID'],
                            'name'         => $response['items'][0]['data_fields']['name'],
                            'secondary_ID' => $response['items'][0]['data_fields']['secondary_ID'] ];
                            
        if (array_key_exists('description', $response['items'][0]['data_fields'])) {
          $xnatProjectList['description'] = $response['items'][0]['data_fields']['description'];
        } else {
          $xnatProjectList['description'] = "";
        }
        $this->log("xnatProjectList: " . print_r($xnatProjectList, true));
        //$this->log("xnatProjectList details: " . print_r($response['items'][0]['data_fields'], true));

      }
      return $xnatProjectList;      
    }
  }

  /*
  * Get a new JSESSION cookie
  *
  * @since   COmanage Registry v4.3.4
  * @param   Array  $coProvisioningTargetData CO Provisioning target data
  * @param   string $type                     http content type xml|json
  * @return  array  $Jsession info or empty
  * @throws  InvalidArgumentException
  */
  protected function getXnatSessionToken($coProvisioningTargetData, $srvr) {
    $this->log("FUNCTION getXnatSessionToken: Getting a NEW TOKEN");

    $aliasToken = null;
    $user = $srvr['HttpServer']['username'];
    $passwd = $srvr['HttpServer']['password'];
    $hostUrl = rtrim($srvr['HttpServer']['serverurl'], '/');
    $url = $hostUrl."/data/services/tokens/issue";
 
    $options = $this->setSomeCurlOptions($url, $user, $passwd);
    $item = curl_init();
    curl_setopt_array($item, $options);
    $aliasData = curl_exec($item);
    curl_close($item);
    $info = curl_getinfo($item);
    //$this->log("curl_getinfo info: " . print_r($info ,true));
    $aliasToken=json_decode($aliasData);
    // insert check here if authN failed or the alias artificat is empty.
    $this->log("Response code: " . print_r($info["http_code"], true));
    //$this->log("---------------------------------------------------------");
    if (($info["http_code"] != 200) || ($aliasToken->alias == null)) {
      return array();
    }

    $url=$hostUrl."/data/JSESSION";
    $options = $this->setSomeCurlOptions($url, $aliasToken->alias, $aliasToken->secret);
    $item = curl_init();
    curl_setopt_array($item, $options);
    $jession = curl_exec($item);
    curl_close($item);
    $info = curl_getinfo($item);

    $this->log("Response code: " . print_r($info["http_code"], true));
    //$this->log("---------------------------------------------------------");
    if (!empty($jession) || ($info["http_code"] != 200)) {
      return array(
        "JSESSION"                => $jession,
        "estimatedExpirationTime" => $aliasToken->estimatedExpirationTime,
        "alias"                   => $aliasToken->alias,
        "secret"                  => $aliasToken->secret
      );
    } else {
      return array();
    }
  }

  /**
   * From COmanage return a user's project roles/groups
   * 
   * @since   COmanage Registry v4.3.4
   * @param array $coProvisioningTargetData
   * @param array $provisioningData
   * @return array $userRoles array of user's COmanage roles
   *
   */

  protected function listUserProjectGroups($coProvisioningTargetData, $provisioningData) {
    $this->log("FUNCTION listUserProjectGroups");
    //$this->log("coProvisioningTargetData: " . print_r($coProvisioningTargetData, true) );
    //$this->log("provisioningData: " . print_r($provisioningData, true) );

    $identifier = null;
    $identifierType = $coProvisioningTargetData['CoXnatProvisionerTarget']['identifier_type'];
    $ids = Hash::extract($provisioningData['Identifier'], '{n}[type='.$identifierType.']');
    if (empty($ids)) {
      throw new RuntimeException(_txt('er.apiprovisioner.id.none', array($identifierType)));
    }
    
    $identifier = $ids[0]['identifier'];
    //$this->log("NIF identifier: " . print_r($identifier, true));
    $xnatDefinedRoles = CoXnatProvisionerTarget::$rolesForXnat;
    
    $userRoles = array();
    $allUserRoles = array();

    $userRoles['Person']['coPersonId'] = $provisioningData['CoPerson']['id'];
    $userRoles['Person']['NIFid'] = $identifier;
    $userRoles['Roles'] = array();
    //$this->log("FUNCTION listUserProjectGroups - roles-before: " . print_r($userRoles, true));

    $allUserRoles['Roles'] = $provisioningData['CoPersonRole'];
    //$this->log("allUserRoles: " . print_r($allUserRoles['Roles'], true));

    if (!empty($allUserRoles['Roles'])) {
      foreach ($allUserRoles['Roles'] as $val) {
        if (in_array($val['affiliation'], $xnatDefinedRoles)) {
          //$this->log("FUNCTION listUserProjectGroups - val " . print_r($val, true));
          $couId = $val['cou_id']; 
          $projectAffiliation = str_replace("xnat", "", strtolower($val['affiliation']));
          $userRoles['Roles'][] = ['couId'              => $couId,
                                   'projectAffiliation' => $projectAffiliation];
        }
      }
    }
    //$this->log("FUNCTION listUserProjectGroups - userRoles-after: " . print_r($userRoles, true));
    return $userRoles;
  }

  /**
   * Save Xnat Jsession token for reuse
   * 
   * @since   COmanage Registry v4.3.4
   * @param string $target Xnat plugin entry to receive new token
   * @param string $data new token info
   * @returns  array
   * @throws  InvalidArgumentException
   * 
  */

  protected function saveJessionData($target, $data) {
    $this->log("FUNCTION saveJessionData");
    //$this->log("FUNCTION saveJessionData - target: " . print_r($target, true));
    //$this->log("FUNCTION saveJessionData - data: " . print_r($data, true));
    $args = array();
    $args['CoXnatProvisionerTarget']['id'] = $target['id'];
    $args['CoXnatProvisionerTarget']['co_provisioning_target_id'] = $target['co_provisioning_target_id'];
    $args['CoXnatProvisionerTarget']['server_id'] = $target['server_id'];
    $args['CoXnatProvisionerTarget']['xnat_alias'] = $data['alias'];
    $args['CoXnatProvisionerTarget']['xnat_estimated_expiration_time'] = $data['estimatedExpirationTime'];
    $args['CoXnatProvisionerTarget']['xnat_jsession'] = $data['JSESSION'];
    $this -> clear();
    $this -> save($args, false);
    
    $args = array();
    $args['conditions']['id'] = $target['id'];
    $check = $this->find("first", $args);
    $this->log("FUNCTION saveJessionData - check save: " . print_r($check, true));
    if (empty($check)) {
      throw new InvalidArgumentException(_txt('er.notfound', array(_txt('ct.http_servers.1'), $check)));
    }
    return;
  }

  /**
  * Sets php curl options
  * @param string $url  target url
  * @param string $user target username
  * @param string $password target password
  * @returns  array
  */
  protected function setSomeCurlOptions($url,$user,$passwd) {
    $this->log("FUNCTION setSomeCurlOptions");
    return array(
      CURLOPT_NOPROGRESS      => true,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_URL             => "$url",
      CURLOPT_USERPWD         => "$user:$passwd",
      CURLOPT_HTTPAUTH        => CURLAUTH_BASIC,
      CURLOPT_POST            => false
    );
  }

 /*
 * Sync a person to XNAT
 * 
 * @since   COmanage Registry v4.3.4
 * @param   $coProvisioningTargetData Array CO Provisioning Target data
 * @param   $provisioningData Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
 * @return  
 * @throws  InvalidArgumentException
 *
 */

 protected function syncPerson($coProvisioningTargetData, $provisioningData, $uRoles) {
  $this->log("FUNCTION syncPerson");
  //$this->log("FUNCTION syncPerson - coProvisioningTargetData: " . print_r($coProvisioningTargetData, true));
  //$this->log("FUNCTION syncPerson - provisioningData: " . print_r($provisioningData, true));
  //$this->log("FUNCTION syncPerson - provisioningData[CoPersonRole]: " . print_r($provisioningData['CoPersonRole'], true));
  $this->log("FUNCTION syncPerson - uRoles: " . print_r($uRoles, true));
  
  //CoPerson details
  $userEmail = $provisioningData['EmailAddress']['0']['mail']; // throw error if missing email address find type offical??
  $firstName = $provisioningData['PrimaryName']['given'];    // throw error if missing firstName??
  $lastName = $provisioningData['PrimaryName']['family'];
  // $this->log("EmailAddress: " . print_r($provisioningData['EmailAddress']['0']['mail'], true));

  $identifier = null;
  $identifierType = $coProvisioningTargetData['CoXnatProvisionerTarget']['identifier_type'];
  $this->log("identifierType: " . print_r($identifierType, true));
  $ids = Hash::extract($provisioningData['Identifier'], '{n}[type='.$identifierType.']');
  if (empty($ids)) {
    throw new RuntimeException(_txt('er.xnatprovisioner.id.none', array($identifierType)));
  }

  $identifier = $ids[0]['identifier'];
  $xnatUsername = $coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_username_prefix'] . $identifier;
  //$this->log("Username identifier: " . print_r($xnatUsername, true));
  $this->log("FUNCTION syncPerson - CoPerson STATUS: " . print_r(_txt('en.status', null, $provisioningData['CoPerson']['status']), true));
  
  $xnatPath = "xapi/users/" . $xnatUsername;
  //$this->log("xnat path: " . print_r($xnatPath, true));
  $this -> createHttpClient($coProvisioningTargetData, $provisioningData, "json");
  $inXnat = array();
  $inXnat = json_decode($this->Http->get("/" . $xnatPath), true);
  
  $this->log("FUNCTION syncPerson - inXnat: " . print_r($inXnat, true));

  $cmProjectsList = array();
  $args = array();
  $item = array();
  $CoService = new CoService;

  foreach ($uRoles['Roles'] as $role) {
    //$this->log("FUNCTION syncPerson - Role: " . print_r($role, true));
    $args['conditions']['CoService.co_id'] = $coProvisioningTargetData['CoXnatProvisionerTarget']['co_id'];
    $args['conditions']['CoService.status'] = "A";
    $args['conditions']['CoService.cou_id'] = $role['couId'];
    $args['conditions']['CoService.co_group_id'] = $coProvisioningTargetData['CoXnatProvisionerTarget']['co_group_id'];
    $args['conditions'][] = 'CoService.short_label IS NOT NULL';   
    $cmProjectsList = $CoService->find('first', $args);  
      // just need to find one project that meets this criteria - it should already be provisioned to XNAT
  }

  //$this->log("FUNCTION syncPerson - what cmProjectsList: " . print_r($cmProjectsList, true));

  //if (empty($cmProjectsList)) {     // only works correclty for the UI, but interferes with auto provisionng
  //  throw new InvalidArgumentException(_txt('er.coperson.group.none'));
  //}

  $response = array();
  if ( (_txt('en.status', null, $provisioningData['CoPerson']['status']) == "Active") ) {  //Active
    if (!$inXnat) {                                                                        // Not in XNAT
      if (!empty($cmProjectsList)) {                                                   // Has valid project roles in CM
        $this->log("CoPerson STATUS: Active CM Roles and NOT in XNAT: ADD user");        
        $xnatPath = "xapi/users";
        $jsonMessage = 
          "{'email': '$userEmail',
            'enabled': true,
            'firstName': '$firstName',
            'lastName': '$lastName',
            'secured': true,
            'username': '$xnatUsername',
            'verified': true}";
        $response = $this->Http->post("/" . $xnatPath, $jsonMessage);
      }
    } else {                  // in Xnat, update user if any Personal info field is different
      if ( ($inXnat['firstName'] != $firstName) || 
           ($inXnat['lastName'] != $lastName) || 
           (!$inXnat['enabled']) || 
           ($inXnat['email'] != $userEmail) ) {
        $xnatPath = "xapi/users/" . $xnatUsername;
        //$this->log("xnatPath: " . print_r($xnatPath, true));
        $jsonMessage = 
        "{'email': '$userEmail',
          'enabled': true,
          'firstName': '$firstName',
          'lastName': '$lastName'}";
        $response = $this->Http->put("/" . $xnatPath, $jsonMessage);
        $this->log("FUNCTION syncPerson - CoPerson STATUS - Active, In XNAT, UPDATE user");
      } else {                                  // remove this ELSE for PROD!!
        $this->log("FUNCTION syncPerson - CoPerson STATUS - Active, In XNAT NO CHANGE NEEDED TO PERSONAL DETAILS");
      }
    }
  } else {              //$coPersonStatus != "Active"
    // Don't delete users from XNat, only remove them from project groups, but don't do that here!!
    // Only disable user in XNAT and update details
    if ($inXnat) {
      $xnatPath = "xapi/users/" . $xnatUsername;
      $jsonMessage = 
        "{'email': '$userEmail',
          'enabled': false,
          'firstName': '$firstName',
          'lastName': '$lastName'}";
      $response = $this->Http->put("/" . $xnatPath, $jsonMessage);
      $this->log("CoPerson STATUS: Inactive, In XNAT, DISABLE  user");
    } 
  }

  if ($response) {
    $this->log("FUNCTION syncPerson - RESULT: " . print_r($response->code, true));
    if ($response->code < 200 || $response->code > 299) {
      throw new RuntimeException($response->reasonPhrase);
    }
  }
  return;
}

  /**
  * Sync Project from COmanage to XNAT
  * @since   COmanage Registry v4.3.4
  * @param   Array $coProvisioningTargetData  CO Provisioner Target data
  * @param   Array $provisioningData CO Service Provisioning data
  * @return  
  * @throws  // InvalidArgumentException   ??
  *
  */
  
  protected function syncProject($coProvisioningTargetData, $provisioningData) {
    $this->log("FUNCTION syncProject");
    $this->log("coProvisioningTargetData: " . print_r($coProvisioningTargetData, true));
    //$this->log("provisioningData: " . print_r($provisioningData, true));
    // CoService conditions necessary for XNAT project creation:
    // ['CoService']['name'] not empty
    // ['CoService']['status'] == Active
    // ['CoService']['cou_id'] not empty
    // ['CoService']['co_group_id'] matches a linked provisioning target ['co_group_id']
    // ['CoService']['identifier_type'] not empty
    // ['CoService']['short_label'] not empty

    $this->log("Groups: provData - TargetData " . print_r($provisioningData['CoService']['co_group_id'] . '+' . $coProvisioningTargetData['CoXnatProvisionerTarget']['co_group_id'], true));

    if (!($provisioningData['CoService']['co_group_id'] == $coProvisioningTargetData['CoXnatProvisionerTarget']['co_group_id'])) {
      throw new InvalidArgumentException(_txt('er.service.group.none'));
    }

    if ( !($provisioningData['CoService']['status'] == SuspendableStatusEnum::Active) ||
         !($provisioningData['CoService']['cou_id']) ||
         !($provisioningData['CoService']['short_label']) || 
         !($provisioningData['CoService']['identifier_type']) || 
         !($provisioningData['CoService']['co_group_id'] == $coProvisioningTargetData['CoXnatProvisionerTarget']['co_group_id']) ) {
      return;
    }

    $updateProject = false;
    $xnatProjectDetail = $this->findOneXnatProject($coProvisioningTargetData, $provisioningData);
    $this->log("FUNCTION syncProject - Found XNAT project Server detail: " . print_r($xnatProjectDetail, true));

    $xnatProjectIdPrefix = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_id_prefix'];
    $xnatProjectDelimiter = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_name_delimiter'];

    $xnatProjectId = $xnatProjectIdPrefix . strtolower($provisioningData['CoService']['short_label']);
    $xnatProjectTitle = $provisioningData['CoService']['name'];
    $xnatRunningTitle = $xnatProjectId . $xnatProjectDelimiter . $xnatProjectTitle;
    $xnatDescription =  $provisioningData['CoService']['description'];
    if (empty($xnatDescription)) {
      $xnatDescription = CoXnatProvisionerTarget::$emptyDescritptionValue;
    }

    if (empty($xnatProjectDetail)) {
      $this -> createProject($coProvisioningTargetData, $provisioningData);
    } else {
      $this->log("FUNCTION syncProject - project already exists, but might need an update!");

      $xnatProjectIdPrefix = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_id_prefix'];
      $xnatProjectDelimiter = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_name_delimiter'];
  
      $xnatProjectId = $xnatProjectIdPrefix . strtolower($provisioningData['CoService']['short_label']);
      $xnatProjectTitle = $provisioningData['CoService']['name'];
      $xnatRunningTitle = $xnatProjectId . $xnatProjectDelimiter . $xnatProjectTitle;

      if ($xnatProjectTitle != $xnatProjectDetail['name'] || $xnatRunningTitle != $xnatProjectDetail['secondary_ID'] ){
        $updateProject = true;
        $xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
                <xnat:Project ID='$xnatProjectId' 
                  secondary_ID='$xnatRunningTitle' 
                  active='1' 
                  xmlns:arc='http://nrg.wustl.edu/arc' 
                  xmlns:val='http://nrg.wustl.edu/val' 
                  xmlns:pipe='http://nrg.wustl.edu/pipe' 
                  xmlns:icr='http://icr.ac.uk/icr'
                  xmlns:wrk='http://nrg.wustl.edu/workflow' 
                  xmlns:scr='http://nrg.wustl.edu/scr' 
                  xmlns:xdat='http://nrg.wustl.edu/security' 
                  xmlns:cat='http://nrg.wustl.edu/catalog' 
                  xmlns:prov='http://www.nbirn.net/prov' 
                  xmlns:xnat='http://nrg.wustl.edu/xnat' 
                  xmlns:xnat_a='http://nrg.wustl.edu/xnat_assessments' 
                  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
                  xsi:schemaLocation='https://xnat-aaf.ais.sydney.edu.au/schemas/workflow.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/catalog.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/repository.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/screeningAssessment.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/project.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/roi.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/protocolValidation.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/xnat.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/assessments.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/birnprov.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/security.xsd'>
                  <xnat:name>$xnatProjectTitle</xnat:name>
                  <xnat:description>$xnatDescription</xnat:description>
                </xnat:Project>";
      }

      if ($updateProject) {
        $this->log("xml: " . print_r($xml, true));
        $this -> createHttpClient($coProvisioningTargetData, $provisioningData, "xml");
        $xnatPath = "data/projects/" . $xnatProjectId;
        $this->log("xnatPath: " . print_r($xnatPath, true));
        $response = $this->Http->put("/" . $xnatPath, $xml);
        $this->log("Response Code XNAT Project update other details: " . print_r($response->code, true));
        //$this->log("---------------------------------------------------------");
        if ($response->code < 200 || $response->code > 299) {
          throw new RuntimeException($response->reasonPhrase);
        }
      }
      
      if ($xnatDescription != $xnatProjectDetail['description']) {
        $updateProject = true;
        $xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
                <xnat:Project ID='$xnatProjectId' 
                  xmlns:arc='http://nrg.wustl.edu/arc' 
                  xmlns:val='http://nrg.wustl.edu/val' 
                  xmlns:pipe='http://nrg.wustl.edu/pipe' 
                  xmlns:icr='http://icr.ac.uk/icr'
                  xmlns:wrk='http://nrg.wustl.edu/workflow' 
                  xmlns:scr='http://nrg.wustl.edu/scr' 
                  xmlns:xdat='http://nrg.wustl.edu/security' 
                  xmlns:cat='http://nrg.wustl.edu/catalog' 
                  xmlns:prov='http://www.nbirn.net/prov' 
                  xmlns:xnat='http://nrg.wustl.edu/xnat' 
                  xmlns:xnat_a='http://nrg.wustl.edu/xnat_assessments' 
                  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
                  xsi:schemaLocation='https://xnat-aaf.ais.sydney.edu.au/schemas/workflow.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/catalog.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/repository.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/screeningAssessment.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/project.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/roi.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/protocolValidation.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/xnat.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/assessments.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/birnprov.xsd 
                    https://xnat-aaf.ais.sydney.edu.au/schemas/security.xsd'>
                  <xnat:description>$xnatDescription</xnat:description>
                </xnat:Project>";
      }
      if ($updateProject) {
        //$this->log("xml: " . print_r($xml, true));
        $this -> createHttpClient($coProvisioningTargetData, $provisioningData, "xml");
        $xnatPath = "data/projects/" . $xnatProjectId;
        $this->log("xnatPath: " . print_r($xnatPath, true));
        $response = $this->Http->put("/" . $xnatPath, $xml);
        $this->log("Response Code XNAT Project update Description: " . print_r($response->code, true));
        $this->log("Response Code XNAT Project update Description: " . print_r($response, true));
        //$this->log("---------------------------------------------------------");
        if ($response->code < 200 || $response->code > 299) {
          throw new RuntimeException($response->reasonPhrase);
        }
      }
    }
  return;
  }
  
  /**
  * Syncs a CM user's valid XNat roles to XNat. - XNat user already exists.
  * 
  * @since   COmanage Registry v4.3.4
  * @param   Array $coProvisioningTargetData  CO Provisioner Target data
  * @param   Array $provisioningData CO Service Provisioning data
  * @param   Array $userRoles CM User details and roles for XNAT
  * @return  
  * @throws  RuntimeException ??
  *
  */

  protected function syncUserRoles($coProvisioningTargetData, $provisioningData, $userRoles) {
    $this->log("FUNCTION syncUserRoles");
    //$this->log("FUNCTION syncUserRoles - coProvisioningTargetData: " . print_r($coProvisioningTargetData, true));
    //$this->log("FUNCTION syncUserRoles - provisioningData[CoPersonRole]: " . print_r($provisioningData['CoPersonRole'], true));
    $this->log("FUNCTION syncUserRoles - userRoles: " . print_r($userRoles, true));
    
    $userXnatGroups = array();
    $xnatProjectIdPrefix = $coProvisioningTargetData['CoXnatProvisionerTarget']['project_id_prefix'];
    $xnatUsername = $coProvisioningTargetData['CoXnatProvisionerTarget']['xnat_username_prefix'] . $userRoles['Person']['NIFid'];
    $this->log("FUNCTION syncUserRoles - xnatUsername: " . print_r($xnatUsername, true));

    // find a Service with ['co_group_id'] that matches $coProvisioningTargetData['co_group_id'] 
    // does the CoPerson Role ['cou_id'] match that for the Service[cou_id]  
    // otherwise skip, there are no userXnatGroups because this user in not in XNAT
    
    $xnatPathUser = "xapi/users/" . $xnatUsername . "/groups?format=json";
    $responseUser = $this->Http->get("/" . $xnatPathUser);
    //$this->log("responseUser: " . print_r($responseUser, true));
    $this->log("USER Response Code: " . print_r($responseUser->code, true));
    if ($responseUser->code < 200 || $responseUser->code > 299) {
      throw new RuntimeException($responseUser->reasonPhrase);
    }
    $userXnatGroups = json_decode($responseUser);
    $this->log("FUNCTION syncUserRoles - userXnatGroups: " . print_r($userXnatGroups, true));

    $validUserRoles = array();
    $found = array();

    if (!empty($userRoles)) {
      $this->log("userRoles details on roles: " . print_r($userRoles['Roles'], true));
      if ($userRoles['Roles']) {
        foreach ($userRoles['Roles'] as $val) {
          $this->log("FUNCTION syncUserRoles - val: " . print_r($val, true));
          $args = array();
          $args['conditions']['CoService.status'] = 'A';
          $args['conditions']['CoService.cou_id'] = $val['couId'];
          $args['conditions']['CoService.co_group_id'] = $coProvisioningTargetData['CoXnatProvisionerTarget']['co_group_id'];
          $args['conditions'][] = 'CoService.short_label IS NOT NULL';
          $found = $this->CoProvisioningTarget->Co->CoService->find('all', $args);

          //$this->log("what found1: " . print_r($found, true));
          
          foreach ($found as $service) {
            //$this->log("what found2: " . print_r($service['CoService'], true));
            $projectId = strtolower($service['CoService']['short_label']);
            //$projectAffiliation = str_replace("xnat", "", strtolower($val['projectAffiliation']));
            $projectAffiliation = $val['projectAffiliation'];
            $validUserRoles[] = $xnatProjectIdPrefix . $projectId . "_" . $projectAffiliation;
          }
        }
      } 
    }
    $this->log("List of User validUserRoles: " . print_r($validUserRoles, true));

    $uniqueArrayList = array();
    //$this->log("validUserRoles: " .print_r($validUserRoles, true));
    //$this->log("CM groups: " . print_r(array_diff($validUserRoles, $userXnatGroups), true));
    //$this->log("XNAT groups: " . print_r(array_diff($userXnatGroups, $validUserRoles), true));

    $uniqueArrayList = array_merge(array_diff($validUserRoles, $userXnatGroups), array_diff($userXnatGroups, $validUserRoles));
    //$this->log("uniqueArrayList: " .print_r($uniqueArrayList, true));
    
    if (!empty($uniqueArrayList)) {
      // process removal of users from xnat groups first
      if (!empty(array_diff($userXnatGroups, $validUserRoles))) {
        foreach ($uniqueArrayList as $val) {
          $this->log("projectId val :" . print_r($val, true));
          $this->log("PROJECT Sync STATUS remove from XNAT: " . print_r(in_array($val, $userXnatGroups), true) ."-" . print_r(in_array($val, $validUserRoles) , true) );

          /*
          if (in_array($val, $userXnatGroups, true) && in_array($val, $validUserRoles, true) ) {
            // value is in both arrays
            $this->log("Already SYNCD, no update!: ......................................." . print_r($val, true));
          }
          */
          
          if (in_array($val, $userXnatGroups, true) && (!in_array($val, $validUserRoles, true))) {
            $this->log("REMOVE FROM XNAT: ......................................." . print_r($val, true));
            $xnatPath = "xapi/users/" . $xnatUsername . "/groups/" . $val;
            //$this->log("xnat path: " . print_r($xnatPath, true));
            $response = $this->Http->delete("/" . $xnatPath);
            if ($response->code < 200 || $response->code > 299) {
              throw new RuntimeException($response->reasonPhrase);
            }
          }
          
          /*
          if (!in_array($val, $userXnatGroups, true) && in_array(!$val, $validUserRoles, true) ) {
            // if comanage doesn't know about an XNat project ignore since this plugin only provisions to XNAT
            $this->log("UNKNOWN Project ignore: ......................................." . print_r($val, true));
          }
          */

        }
      }
      // process addition of users to xnat groups second
      if (!empty(array_diff($validUserRoles, $userXnatGroups))) {
        foreach ($uniqueArrayList as $val) {
          $this->log("projectId val :" . print_r($val, true));
          $this->log("PROJECT Sync STATUS ADD to XNAT: " . print_r(in_array($val, $userXnatGroups), true) ."-" . print_r(in_array($val, $validUserRoles) , true) );

          /*
          if (in_array($val, $userXnatGroups, true) && in_array($val, $validUserRoles, true) ) {
            // value is in both arrays
            $this->log("Already SYNCD, no update!: ......................................." . print_r($val, true));
          }
          */
          
          if (!in_array($val, $userXnatGroups, true) && in_array($val, $validUserRoles, true)) {
            // add user to xnat project group
            $this->log("SEND to XNAT: ......................................." . print_r($val, true));
            $xnatPath = "xapi/users/" . $xnatUsername . "/groups/" . $val;
            //$this->log("xnat path: " . print_r($xnatPath, true));
            $response = $this->Http->put("/" . $xnatPath);
            $this->log("SEND TO XNAT RESPONSE: ". print_r($response->code, true));
            if ($response->code < 200 || $response->code > 299) {
              //$this->log("response body: " . print_r($response->body, true));
              //throw new InvalidArgumentException(_txt('er.coperson.group.none'));
              throw new RuntimeException($response->body);
            }
          }

          /*
          if (!in_array($val, $userXnatGroups, true) && in_array(!$val, $validUserRoles, true) ) {
            // if comanage doesn't know about an XNat project ignore since this plugin only provisions to XNAT
            $this->log("UNKNOWN Project ignore: ......................................." . print_r($val, true));
          }
          */
        }
      }
    }

    return;
  }

}
