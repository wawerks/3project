<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle(Request $request, Closure $next)
    {
        try {
            // Only log for authenticated users
            if (Auth::check()) {
                // Get the request path and method
                $path = $request->path();
                $method = $request->method();
                
                // Skip logging for certain paths
                $skipPaths = ['_debugbar', 'livewire', 'sanctum'];
                if (collect($skipPaths)->contains(fn($skip) => str_starts_with($path, $skip))) {
                    return $next($request);
                }

                // Create a more descriptive action message
                $action = match($method) {
                    'GET' => "Viewed $path",
                    'POST' => "Created new $path",
                    'PUT', 'PATCH' => "Updated $path",
                    'DELETE' => "Deleted $path",
                    default => "$method $path"
                };

                // Get request data for more context
                $requestData = [];
                if ($method !== 'GET') {
                    $requestData = $request->except(['password', 'token', '_token']);
                }

                // Create activity log
                $log = ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => $action . (!empty($requestData) ? ' with data: ' . json_encode($requestData) : ''),
                    'action_time' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                \Log::info('Activity logged', [
                    'log_id' => $log->id,
                    'user_id' => Auth::id(),
                    'action' => $action,
                    'path' => $path,
                    'method' => $method
                ]);
            }

            $response = $next($request);
            return $response;

        } catch (\Exception $e) {
            // Log the error but don't interrupt the request
            \Log::error('Failed to log activity: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id(),
                'path' => $request->path(),
                'method' => $request->method()
            ]);

            return $next($request);
        }
    }
}
