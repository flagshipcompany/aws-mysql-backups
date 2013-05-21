aws-mysql-backups
=================

How to leverage the AWS PHP2 API to backup mysql

##Installation##

* pull this repo
* ``composer.phar install`` to get all the dependencies
* rename ``config.sample.json`` to ``config.json`` with appropriate info

## Usage ##

_Make sure that awsbackup.php is executable_

``awsbackup.php mysql:fullbackup`` will create a gzip backup of the database set in the config, with the credentials. use ``--help`` to see how to overwrite parameters at run time. ``--aws`` will upload the resulting file to AWS S3.



