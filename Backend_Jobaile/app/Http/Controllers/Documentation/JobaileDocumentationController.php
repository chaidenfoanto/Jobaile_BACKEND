<?php

namespace App\Http\Controllers\Documentation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Jobaile API",
 *     version="1.0.0",
 *     description="Dokumentasi API untuk aplikasi Jobaile (versi pertama)",
 *     @OA\Contact(
 *         email="support@jobaile.com",
 *         name="Jobaile Dev Team"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Server utama"
 * )
 */

class JobaileDocumentationController extends Controller
{
    //
}
