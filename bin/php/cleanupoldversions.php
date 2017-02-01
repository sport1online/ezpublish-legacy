#!/usr/bin/env php
<?php

// ezpublish/console ezpublish:legacy:script bin/php/cleanupoldversions.php -n

require_once 'autoload.php';

$cli = eZCLI::instance();

$script = eZScript::instance(
    array(
        'description' => 'This script will progressively cleanup history, 2000 objects at a time.',
        'use-session' => false,
        'use-modules' => true,
        'use-extensions' => true,
    )
);

$script->startup();
$script->initialize();

$db = eZDB::instance();
$rows = $db->arrayQuery('
    select
        contentobject_id,
        count(*) count
    from
        ezcontentobject_version
    where created < unix_timestamp(now() - interval 2 month)
    group by contentobject_id
    having count > 1
    -- limit 2000
    ;
');

foreach ($rows as $row) {
    $cli->output($row['contentobject_id']);

    $object = eZContentObject::fetch($row['contentobject_id']);
    $versions = $object->versions(true, array(
        'conditions' => array('status' => \eZContentObjectVersion::STATUS_ARCHIVED),
    ));

    $db->begin();
    foreach ($versions as $version) {
        $version->removeThis();
    }
    $db->commit();
}

$script->shutdown();
