# COmanage Plugin for XNAT User and Project Provisioning

## Description: COmanage Plugin - XnatProvisioner

The **XnatProvisioner** is a COmanage Plugin https://incommon.org/software/comanage.

This Plugin manages users and their project roles on the XNAT imaging platform https://www.xnat.org.

This plugin provisions users to XNAT, but does not remove users from XNAT since XNAT does not support user deletion. This plugin does support enabling/disabling users in XNAT when a user is active/suspended in COmanage. When a CoPerson role is active/suspended in COmanage, the XNAT user is added/removed from the XNAT project.

This plugin provisions projects to XNAT, but does not delete projects if the related COmanage objects are removed. Project deletion in XNAT is left to the XNAT administration to achieve using other methods.

This plugin only manages users' assignment to projects via the default XNAT project groups: Owners, Members and Collaborators. This is achieved by assigning a role to a CO Person via the customised COmanage Extended Types Attributes affiliation.

## Configuring This Plugin
_and associated COmanage objects_
### COmanage Extended Types
- update the COmanage Extended Types Attributes with the following additions.
- select **For Attribute** of type **Affiliation (CO Person Role)** and _FILTER_ for the *Affiliation* attribute list. 
- Add three **Extended Type Attributes** with the following _"Name"/"Display Name"_ pair values:
    - xnatcollaborator/XNAT Collaborator
    - xnatmember/XNAT Member
    - xnatowner/XNAT Owner

    This Plugin uses the string values _xnatcollaborator_, _xnatmember_ and _xnatowner_ and these strings must match these values.

### COmanage Server
- for each XNAT Server, add a COmanage Server object with the following configuration options:
    - Type: HTTP 
    - HTTP Authentication Type: Basic
    - Supply a Username and Password for a local XNAT user with XNAT admin rights and API access.

## COmanage Regular Group
- Create or use a regular group to link a COmanage Server object to a XNAT COmanage Provisioning Target.

### COmanage Provisioning Targets
Thought it is possible to have more than XNAT Provisioner Plugin per XNAT server, it is not adviseable. Each XNAT server should have a single XNAT Provisioner Target. 
- for each XNAT Server, add a _Provisioning Target_ with the following configuration options:
    - Plugin: XnatProvisioner
    - Target XNAT Server: select from the list of servers configured in *COmanage Server* configuration.
    - CoPerson Identifier Type: to use as the XNAT primary user identifier (this should be a unique and persistent attribute for users - email address is not suitable because email addresses change).
    - Services linking group: Services (as projects) assigned to this group are provisioned by this Provisioning Target.
    - XNAT username prefix: this value is added as a prefix to the XNAT username. The value should match the prefix assigned in the XNAT OIDC configuration. Max 6 characters, can be empty.
    - Project ID Prefix: this value is added as a prefix to the XNAT Project ID. Max 6 characters, can be empty.
    - Deliminter for XNAT Project Title: This is the delimiter value used in the XNAT Project *Running Title* to separate the *Project ID* from the *Project Title*. Max 3 characters.

### COmanage Services Objects
- Add a COmanage Services Object with the following configuration options:
    - Name: this will be the XNAT *Project Title*.
    - COU: Select a COU - this will provision those users assocaited to this COU (via _Role Attributes_) to the XNAT Project.
    - Service Group: Select a group that matches the *COmanage Provisioning Targets* _Services linking group_. 
    - Short Label: This is the XNAT *Project ID* (prefixed with the _Project ID Prefix_).

Note: The XNAT _Running Title_ is the concat of _Project ID Prefix_ and the _Short Label_.

### COmanage CO Person Objects
- COmanage CO Person objects are assigned roles to be provisioned to XNAT and to also be assigned to XNAT Projects.
- Select a CO Person and for **Role Attributes** add a new role with the following configuration:
    - Select a child COU that contains **xnat** in the name.
    - Update **Affiliation** and select one of the pre-defined _affiliations_ from this list:
        - XNAT Collaborator
        - XNAT Member
        - XNAT Owner

## Versions
This COmanage Plugin has been tested with the following platform versions:
- COmanage: v4.3.3 and v4.3.4
- XNAT: version 1.8.10.1, build: 52
 