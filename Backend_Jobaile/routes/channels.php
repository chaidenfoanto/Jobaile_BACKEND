<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('chat.{idRecruiter}.{idWorker}', function ($user, $idRecruiter, $idWorker) {
    return $user->id_user === $idRecruiter || $user->id_user === $idWorker;
});