#!/usr/bin/env php
<?php

// ezpublish/console ezpublish:legacy:script bin/php/cleanupoldversions.php

require_once 'autoload.php';

$cli = eZCLI::instance();

$script = eZScript::instance([
    'description' => 'This script will progressively cleanup history, 2000 objects at a time.',
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true,
]);

$options = $script->getOptions();

$limit = isset($options['arguments'][0])
    ? (int) $options['arguments'][0]
    : 2000;

$script->startup();
$script->initialize();

$db = eZDB::instance();
$rows = $db->arrayQuery("
    select
        contentobject_id,
        count(*) count,
        max(modified) newest
    from
        ezcontentobject_version
    where created < unix_timestamp(now() - interval 2 month)
    group by contentobject_id
    having count > 1
    and newest < unix_timestamp(now() - interval 2 month)
    limit $limit
    ;
");

$length = count($rows);
foreach ($rows as $index => $row) {
    $object = eZContentObject::fetch($row['contentobject_id']);
    $class = $object->attribute('content_class')->Identifier;
    $purge = 0 === strpos($class, 'sport1_');

    $cli->output(sprintf(
        "%s\t%s\t%s\t%s\t%s",
        $row['contentobject_id'],
        $class,
        $index + 1,
        $length,
        $purge ? 'cleaning' : 'ignoring'
    ));

    if ($purge) {
        $versions = $object->versions(true, [
            'conditions' => ['status' => \eZContentObjectVersion::STATUS_ARCHIVED],
            'limit' => [
                'limit' => $object->getVersionCount() - 1,
                'offset' => 0,
            ],
            'sort' => ['modified' => 'asc'],
        ]);

        $db->begin();
        foreach ($versions as $version) {
            $version->removeThis();
        }
        $db->commit();

        eZContentObject::clearCache();
    }
}

$script->shutdown();
