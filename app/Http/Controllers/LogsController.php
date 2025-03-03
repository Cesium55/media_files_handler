<?php

namespace App\Http\Controllers;

use App\Models\ProcessingLog;
use App\Services\ProcessingLogsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogsController extends Controller
{
    function get_logs(Request $request)
    {
        $request->validate([
            "type" => "string|required|max:100",
            "id" => "required|int|min:1"
        ]);

        Log::channel("custom_log")->info("Requesting log for " . $request["type"] . " with id = " . $request["id"]);

        return ProcessingLogsService::get_logs($request["type"], $request["id"]);

    }
}
