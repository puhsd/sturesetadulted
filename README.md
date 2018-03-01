# Student Password Reset

This password reset utility is intended to be used in case when there is a single AD domain and multiple Google Domains.
The primary Google Domain can use Google Password Sync for password synchronization. The secondary would need to use a tool like this.

# Setup

## Set password

Set password website uses AD credentials to reset password in Active Directory Only. In order to be able to reset the password, a user must have permissions to reset passwords. This section will create and set the random password.

### Suggested Setup

#### Organizational Unit OU setup

The users whose passwords needs to be sync need to be under the same organizational unit. This information needs to be placed in index.php file under LDAP_BASE_OU.
The example of the line is

     define('LDAP_BASE_OU', "OU=AdultEd,OU=Accounts,DC=PUHSD,DC=ORG");


#### Assigning permissions

All the users that need to have permissions to reset password need to be a part of the same group in AD. That group needs to have permissions to reset password on LDAP_BASE_OU.

*Instruction on how to give password reset permissions*

1. Open Active Directory Users and Computers
2. Navigate to the LDAP_BASE_OU
3. Right click and click on *Delegate Control...*
4. on **Welcome to the Delegation of Control Wizard** click *Next >*
5. Click on *Add...* and select the group
6. Click *Next >*
7. On the **Tasks to Delegate** screen, check *Reset user passwords and force password change at next logon* and click **Next >**.
8. **Finish**


## Reset Password

This section allows users to resets their password in AD and Google. In order to work, the SELF permissions might need to be set on LDAP_BASE_OU to reset password.

*Instruction on how to give password reset permissions in AD*

1. Open Active Directory Users and Computers
2. Navigate to the LDAP_BASE_OU
3. Right click and click on *Delegate Control...*
4. on **Welcome to the Delegation of Control Wizard** click *Next >*
5. Click on *Add...* and type SELF then click **Check Name** and **OK**
6. Click *Next >*
7. On the **Tasks to Delegate** screen, select **Create a custom task to delegate** and click **Next >**.
8. Select **Only the following objects in the folder** and check *account objects*
9. On **Permissions** screen, check Property-specific and select the following permissions

  - Change Password
  - Reset Password
  - Read userAccountControl
  - Write userAccountControl

10. Click **Next >** and **Finish**


### Google setup

For this section to work GoogleAPI needs to be configured. Please refer to Google instructions on how to Turn on the Directory API

https://developers.google.com/admin-sdk/directory/v1/quickstart/php


# Additional notes

## PHP Requirements

For Google API to work php composer needs to be installed.

https://getcomposer.org/download/

## OS installation and references

- [Ubuntu](https://www.ubuntu.com/server).
- [How To Install Nginx on Ubuntu 16.04](https://www.digitalocean.com/community/tutorials/how-to-install-nginx-on-ubuntu-16-04)
- [Web Certificate using Let's Encript and Nginx](https://www.digitalocean.com/community/tutorials/how-to-secure-nginx-with-let-s-encrypt-on-ubuntu-16-04)


## Completing Google Setup

Before using the website quickstart.php needs to be run fist. On the server, navigate to google folder edit the file and towards the end of the file populate the test user and password. Make sure that test user exists in your environment.

     $testUser = "test@example.com";
     $testUserPassword = "Password123";

After modifying the file, run the

     php quickstart.php

This file is checking for existence of ```.credentials/admin-directory_v1-php-quickstart.json```. If the file does not exist it will provide a URL. Copy the link into the browser and follow the instructions. **Make sure you login with Domain Admin User**.



# FYI

As a root folder in Nginx config file, please set **passwordreset** folder as a root. For example,

    root /var/www/sturesetadulted/passwordreset


In this way, *google* and *.credentials* folder will not be accessible directly through the webpage.

## Message **file does not exist**

if you see the message **file does not exist** it means that ```.credentials/admin-directory_v1-php-quickstart.json``` does not exists.
