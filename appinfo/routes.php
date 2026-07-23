<?php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

        ['name' => 'config#get', 'url' => '/api/v1.0/config', 'verb' => 'GET'],
        ['name' => 'config#groupFolders', 'url' => '/api/v1.0/config/groupFolders', 'verb' => 'GET'],
        ['name' => 'config#setValue', 'url' => '/api/v1.0/config/{key}', 'verb' => 'POST'],

        // All entity actions are explicit POST routes. Resource routes are avoided
        // because they conflict with explicit routes and break depending on whether
        // the server uses index.php in URLs.
        ['name' => 'case#getall', 'url' => '/cases/getall', 'verb' => 'POST'],
        ['name' => 'case#create', 'url' => '/cases', 'verb' => 'POST'],
        ['name' => 'case#show', 'url' => '/cases/{id}/show', 'verb' => 'POST'],
        ['name' => 'case#update', 'url' => '/cases/{id}/update', 'verb' => 'POST'],
        ['name' => 'case#destroy', 'url' => '/cases/{id}/delete', 'verb' => 'POST'],

        ['name' => 'payment#getall', 'url' => '/payments/getall', 'verb' => 'POST'],
        ['name' => 'payment#create', 'url' => '/payments', 'verb' => 'POST'],
        ['name' => 'payment#show', 'url' => '/payments/{id}/show', 'verb' => 'POST'],
        ['name' => 'payment#update', 'url' => '/payments/{id}/update', 'verb' => 'POST'],
        ['name' => 'payment#destroy', 'url' => '/payments/{id}/delete', 'verb' => 'POST'],

        ['name' => 'update#getall', 'url' => '/updates/getall', 'verb' => 'POST'],
        ['name' => 'update#create', 'url' => '/updates', 'verb' => 'POST'],
        ['name' => 'update#show', 'url' => '/updates/{id}/show', 'verb' => 'POST'],
        ['name' => 'update#update', 'url' => '/updates/{id}/update', 'verb' => 'POST'],
        ['name' => 'update#destroy', 'url' => '/updates/{id}/delete', 'verb' => 'POST'],

        ['name' => 'city#getall', 'url' => '/city/getall', 'verb' => 'POST'],
        ['name' => 'city#create', 'url' => '/city', 'verb' => 'POST'],
        ['name' => 'city#show', 'url' => '/city/{id}/show', 'verb' => 'POST'],
        ['name' => 'city#update', 'url' => '/city/{id}/update', 'verb' => 'POST'],
        ['name' => 'city#destroy', 'url' => '/city/{id}/delete', 'verb' => 'POST'],

        ['name' => 'casetype#getall', 'url' => '/casetype/getall', 'verb' => 'POST'],
        ['name' => 'casetype#create', 'url' => '/casetype', 'verb' => 'POST'],
        ['name' => 'casetype#show', 'url' => '/casetype/{id}/show', 'verb' => 'POST'],
        ['name' => 'casetype#update', 'url' => '/casetype/{id}/update', 'verb' => 'POST'],
        ['name' => 'casetype#destroy', 'url' => '/casetype/{id}/delete', 'verb' => 'POST'],

        ['name' => 'updatetype#getall', 'url' => '/updatetype/getall', 'verb' => 'POST'],
        ['name' => 'updatetype#create', 'url' => '/updatetype', 'verb' => 'POST'],
        ['name' => 'updatetype#show', 'url' => '/updatetype/{id}/show', 'verb' => 'POST'],
        ['name' => 'updatetype#update', 'url' => '/updatetype/{id}/update', 'verb' => 'POST'],
        ['name' => 'updatetype#destroy', 'url' => '/updatetype/{id}/delete', 'verb' => 'POST'],

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

        ['name' => 'page#index', 'url' => '/{path}', 'verb' => 'GET', 'postfix' => 'catchall', 'requirements' => ['path' => '.+']],
    ],
];
