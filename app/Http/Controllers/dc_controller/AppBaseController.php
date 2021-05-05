<?php

namespace App\Http\Controllers\dc_controller;

use InfyOm\Generator\Utils\ResponseUtil;
use App\Http\Controllers\Controller;
use Response;

/**
 * @SWG\Swagger(
 *   basePath="/api/v1",
 *   @SWG\Info(
 *     title="Laravel Generator APIs",
 *     version="1.0.0",
 *   )
 * )
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public function sendResponse($result, $message)
    {
        return Response::json(ResponseUtil::makeResponse($message, $result));
    }

    public function sendError($error, $code = 404)
    {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

    protected function isSuperAdmin()
    {
        return abort_if(!auth()->user()->is_super_admin, 403, 'This action is unauthorized.');
    }
}