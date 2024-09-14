# COmanage Plugin for XNAT User and Project Provisioning

## Description: COmanage Plugin - XnatProvisioner

The **XnatProvisioner** is a COmanage Plugin https://incommon.org/software/comanage.
This Plugin manages users and their project roles on the XNAT imaging platform https://www.xnat.org.

This plugin provisions users to XNAT, but does not remove users from XNAT since XNAT does not support user deletion. This plugin does support enabling/disabling users in XNAT when a user is enabled/disabled/suspended in COmanage.

This plugin provisions projects to XNAT, but does not delete projects if the related COmanage objects are removed. Project deletion in XNAT is left to the XNAT administration to achieve using other methods.

This plugin only manages users' assignment to projects via the default XNAT project groups: Owners, Members and Collaborators. This is achieved by assigning a role to a CO Person via the customised role affiliation.

## Configuring This Plugin

### COmanage Extended Types
- update the COmanage Extended Types Attributes with the following additions.
- select **For Attribute** of type **Affiliation (CO Person Role)** and _FILTER_ for the attribute list. 
- Add three **Extended Type Attributes** with the following _"Name"/"Display Name"_ pair values:
    - xnatcollaborator/XNAT Collaborator
    - xnatmember/XNAT Member
    - xnatowner/XNAT Owner

    This Plugin uses the string name values _xnatcollaborator_, _xnatmember_ and _xnatowner_ and these strings must match these values.

### COmanage Server
- for each XNAT Server, add a COmanage Server object with the following configuration options:
    - Type: HTTP 
    - HTTP Authentication Type: Basic
    - Supply a Username and Password for a local XNAT user with XNAT admin rights and API access.

### COmanage Provisioning Targets
- for each XNAT Server, add a _Provisioning Target_ with the following configuration options:
    - Plugin: XnatProvisioner
    - Identifier Type: to use a the XNAT primary user identifier (this should be a unique and persistent attribute for users - email address is not suitable because email addresses change).
    - Project ID Prefix: fror XNAT project IDs. This value is appended to the COU id that is the child COU project COmanage object. Max 6 characters.
    - Deliminter for XNAT Project Title: This is the delimiter value used in the XNAT Project Running Title to separate the parent COU name and the child COU name. Max 2 characters.

### COmanage COU
- for each project to be managed as an XNAT Project this COmanage plugin uses nested COmanage COUs: a parent and a child COU. A parent can have multiple child COUs representing different XNAT Projects. The Parent COU's name is the project Name, the child COU is the XNAT project. These COU name values are used to label XNAT Project _Titles_ and _Running Titles_.
A XNAT Project ID is the COManage child COU (containing the string xnat) prefixed with the **Project ID Prefix**. 
- Create the followin COUs:
    - Add a COU to represent the Project Name as a parent COU 
    - add one or more child COUs as the XNAT Project(s). The child COU must contain the string "xnat" in its name (upper or lowercase).

### COmanage Services Objects
- Create a COManage Service Object for each child COU that represents an XNAT project. Each XNAT project to be managed uses a COmanage Services Object to link a child COU to an XNAT Project.
- Add a COmanage Services Object with the following configuration options:
    - Select the COU object to attach to the service, the COU object that represents an XNAT project (ie: the child COU with a name that includes the "xnat" string).

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
COmanage: v4.3.4
XNAT: version 1.8.10.1, build: 52
 