<?php

namespace App\Services;

use App\Models\ProcessingLog;
use Illuminate\Support\Facades\Log;

class ProcessingLogsService
{
    public static function log(string $type, int $id, string $text)
    {

        $now = \DateTime::createFromFormat('U.u', microtime(true)); // Получаем время с миллисекундами
        $timestamp = $now->format("Y-m-d H:i:s.u");

        $log_obj = ProcessingLog::where("type", $type)->where("instance_id", $id)->first();
        if ($log_obj == null) {
            $log_obj = ProcessingLog::create([
                "instance_id" => $id,
                "type" => $type,
                "logs" => [$timestamp => $text]
            ]);
            return true;
        }
        $logs = $log_obj->logs ?? [];
        $logs[$timestamp] = $text;
        $log_obj->logs = $logs;
        $log_obj->save();

        return true;
    }

    public static function get_logs(string $type, int $id)
    {
        $log_obj = ProcessingLog::where("type", $type)->where("instance_id", $id)->first();

        if (!(bool) $log_obj) {
            Log::channel("custom_log")->info("logs for " . $type . "[$id] was not found");
            return [];

        }

        Log::channel("custom_log")->info("logs for {$type}[$id] found");
        return $log_obj["logs"];
    }
}
