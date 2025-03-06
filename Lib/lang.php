<?php
/**
 * COmanage Registry API Provisioner Plugin Language File
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
 * @since         COmanage Registry v4.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
  
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_xnat_provisioner_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_xnat_provisioner_targets.1'  => 'XNAT Provisioner Target',
  'ct.co_xnat_provisioner_targets.pl' => 'XNAT Provisioner Targets',
  
  // Error messages
  'er.xnatprovisioner.id.none'        => 'No identifier of type %1$s found for CO Person',
  'er.coserver.id.none'              => 'No id found for this service',
  'er.service.group.none'             => 'This provisioner is not assigned to this service/project',
  'er.coperson.group.none'            => 'This provisioner is not assigned to this person',
  
  // Plugin texts
  'pl.xnatprovisioner.co_group_id'                 => 'Services linking group',
  'pl.xnatprovisioner.co_group_id.desc'            => 'Services assigned to this group are provisioned by this Provisioning Target.',
  'pl.xnatprovisioner.identifier_type'             => 'CoPerson Identifier Type',
  'pl.xnatprovisioner.identifier_type.desc'        => 'The CO Person Identifier of this type will be used for the XNAT username. <br>Chose carefully as XNAT usernames cannot be changed or updated after provisioning to XNAT!',
  'pl.xnatprovisioner.project_id_prefix'           => 'XNAT Project ID Prefix',
  'pl.xnatprovisioner.project_id_prefix.desc'      => 'Prefix XNAT project ID with this value, leave blank for no prefix. <br>Max 6 characters, can be empty.',
  'pl.xnatprovisioner.project_name_delimiter'      => 'Deliminter for XNat Project title',
  'pl.xnatprovisioner.project_name_delimiter.desc' => 'Deliminter value placed between COUs for XNat Project title, leave blank for no deliminter. <br>Max 3 characters, can be empty.',
  'pl.xnatprovisioner.server'                      => 'Target XNAT Server',
  'pl.xnatprovisioner.server.desc'                 => 'Select a target XNAT Server.',
  'pl.xnatprovisioner.xnat_alias'                  => 'XNAT API Alias',
  'pl.xnatprovisioner.xnat_alias.desc'             => 'When set, displays the current XNAT API Alias',
  'pl.xnatprovisioner.xnat_modified'               => 'XNAT API Token last modified',
  'pl.xnatprovisioner.xnat_modified.desc'          => 'When set, displays last modified time (UTC) for the XNAT API Token',
  'pl.xnatprovisioner.xnat_username_prefix'        => 'XNAT username prefix',
  'pl.xnatprovisioner.xnat_username_prefix.desc'   => 'This prefix is appended to the CILogon identifier used for XNAT usernames. <br>Max 6 characters, can be empty.',
  'pl.xnatprovisioner.usage'                       => 'XNAT Provisioner Plugin version: v0.3.9 <br>COmanage: v4.3.x/v4.4.x <br>  XNAT: v1.8.10.1, build: 52',
  'pl.xnatprovisioner.usage.desc'                  => 'Service Config conditions necessary for XNAT project creation: <br>
                                                      [Service][Name] = NOT empty <br>
                                                      [Service][Status] == Active <br>
                                                      [Service][COU] COPerson assigned COU Role  <br>
                                                      [Service][Service Group] matches the Provisioning Target\'s "Services linking group" <br>
                                                      [Service][Short Label] NOT empty'

    //'pl.xnatprovisioner.mode'                        => 'Protocol Mode',  
);
