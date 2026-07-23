<?php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        ['name' => 'config#get', 'url' => '/api/v1.0/config', 'verb' => 'GET'],
        ['name' => 'config#groupFolders', 'url' => '/api/v1.0/config/groupFolders', 'verb' => 'GET'],
        ['name' => 'config#setValue', 'url' => '/api/v1.0/config/{key}', 'verb' => 'POST'],

        ['name' => 'case#getall', 'url' => '/cases/getall', 'verb' => 'POST'],
        ['name' => 'payment#getall', 'url' => '/payments/getall', 'verb' => 'POST'],
        ['name' => 'update#getall', 'url' => '/updates/getall', 'verb' => 'POST'],
        ['name' => 'city#getall', 'url' => '/city/getall', 'verb' => 'POST'],
        ['name' => 'casetype#getall', 'url' => '/casetype/getall', 'verb' => 'POST'],
        ['name' => 'updatetype#getall', 'url' => '/updatetype/getall', 'verb' => 'POST'],

        ['name' => 'team#addMember', 'url' => '/team/addmember', 'verb' => 'POST'],
        ['name' => 'team#deleteMember', 'url' => '/team/deletemember', 'verb' => 'POST'],
        ['name' => 'team#getCircleMembers', 'url' => '/team/getCircleMembers', 'verb' => 'POST'],
        ['name' => 'team#searchUsers', 'url' => '/team/searchUsers', 'verb' => 'POST'],
        ['name' => 'team#usersByGroup', 'url' => '/team/usersByGroup', 'verb' => 'POST'],
        ['name' => 'team#userGroups', 'url' => '/team/userGroups', 'verb' => 'POST'],

        ['name' => 'attachment#create', 'url' => '/attachment', 'verb' => 'POST'],
        ['name' => 'attachment#chunk', 'url' => '/attachment/chunk', 'verb' => 'POST'],
        ['name' => 'attachment#finalize', 'url' => '/attachment/finalize', 'verb' => 'POST'],
        ['name' => 'attachment#index', 'url' => '/attachment/{object_type}', 'verb' => 'POST'],
        ['name' => 'attachment#show', 'url' => '/attachment/{object_id}/{object_type}', 'verb' => 'POST'],
        ['name' => 'attachment#destroy', 'url' => '/attachment/{id}', 'verb' => 'DELETE'],

        ['name' => 'acl#byObject', 'url' => '/acl/byObject/{object_type}/{object_id}', 'verb' => 'POST'],
        ['name' => 'acl#addAcl', 'url' => '/acl', 'verb' => 'POST'],
        ['name' => 'acl#deleteAcl', 'url' => '/acl/{aclId}', 'verb' => 'DELETE'],

        ['name' => 'dashboard#stats', 'url' => '/dashboard/stats', 'verb' => 'GET'],

        // POST routes for resource show — needed because the catchall GET /{path}
        // below intercepts GET /{resource}/{id} before the resource routes match
        ['name' => 'case#show', 'url' => '/cases/{id}', 'verb' => 'POST'],
        ['name' => 'payment#show', 'url' => '/payments/{id}', 'verb' => 'POST'],
        ['name' => 'update#show', 'url' => '/updates/{id}', 'verb' => 'POST'],
        ['name' => 'city#show', 'url' => '/city/{id}', 'verb' => 'POST'],
        ['name' => 'casetype#show', 'url' => '/casetype/{id}', 'verb' => 'POST'],
        ['name' => 'updatetype#show', 'url' => '/updatetype/{id}', 'verb' => 'POST'],

        ['name' => 'page#index', 'url' => '/{path}', 'verb' => 'GET', 'postfix' => 'catchall', 'requirements' => ['path' => '.+']],
    ],
    'resources' => [
        'case' => ['url' => '/cases'],
        'payment' => ['url' => '/payments'],
        'update' => ['url' => '/updates'],
        'city' => ['url' => '/city'],
        'casetype' => ['url' => '/casetype'],
        'updatetype' => ['url' => '/updatetype'],
    ],
];
