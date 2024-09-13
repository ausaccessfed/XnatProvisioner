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
  'er.coservier.id.none'              => 'No id found for this service',
  
  // Plugin texts
  'pl.xnatprovisioner.identifier_type'             => 'Identifier Type',
  'pl.xnatprovisioner.identifier_type.desc'        => 'If specified, the CO Person Identifier of this type will be appended to the request URL',
  //'pl.xnatprovisioner.include_attributes'        => 'Include Attributes',
  //'pl.xnatprovisioner.include_attributes.desc'   => 'If true, all attributes will be included in the message, otherwise only a URL to the subject is included',
  'pl.xnatprovisioner.mode'                        => 'Protocol Mode',
  'pl.xnatprovisioner.xnat_alias'                  => 'XNAT Alias',
  'pl.xnatprovisioner.xnat_alias.desc'             => 'When set, displays the current XNAT API Alias',
  'pl.xnatprovisioner.xnat_modified'               => 'XNAT Token last modified',
  'pl.xnatprovisioner.xnat_modified.desc'          => 'When set, displays last modified time (UTC) for the XNAT API Token',
  'pl.xnatprovisioner.project_id_prefix'           => 'Project ID Prefix',
  'pl.xnatprovisioner.project_id_prefix.desc'      => 'Prefix XNAT project ID with this value, leave blank for no prefix. Max 6 characters',
  'pl.xnatprovisioner.project_name_delimiter'      => 'Deliminter for XNat Project title',
  'pl.xnatprovisioner.project_name_delimiter.desc' => 'Deliminter value placed between COUs for XNat Project title, leave blank for no deliminter. Max 2 characters'
);
