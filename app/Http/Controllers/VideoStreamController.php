<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    /**
     * Stream a video file with support for HTTP Range requests.
     * Path is relative to storage/app/public (e.g. "contents/videos/foo.mp4").
     */
    public function stream(Request $request, $path)
    {
        $base = storage_path('app/public');

        // Capture original incoming path for diagnostics
        $origPath = $path;

        // Decode and normalize the incoming route parameter
        $path = urldecode($path);
        $path = ltrim($path, '/\\');

        // If the path accidentally includes a leading "storage/" segment, strip it
        if (strpos($path, 'storage' . DIRECTORY_SEPARATOR) === 0 || strpos($path, 'storage/') === 0) {
            $path = preg_replace('#^storage[/\\]#', '', $path);
        }

        // Normalize separators for the current OS
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $filePath = $base . DIRECTORY_SEPARATOR . $normalized;

        // Compute realpaths for safety checks and log diagnostics
        $realBase = realpath($base);
        $real = realpath($filePath);

        // Emit detailed diagnostics only when the app is in debug mode to avoid noisy logs in production
        if (config('app.debug')) {
            Log::debug('VideoStreamController::stream path diagnostic', [
                'origPath' => $origPath,
                'decodedPath' => $path,
                'normalizedPath' => $normalized,
                'filePath' => $filePath,
                'realBase' => $realBase,
                'realPath' => $real,
                'file_exists' => file_exists($filePath),
                'is_file' => is_file($filePath),
                'request_path' => $request->path(),
            ]);
        }

        // Resolve real path and ensure it's inside storage/app/public for safety
        if (! $real || ! $realBase || strpos($real, $realBase) !== 0 || ! is_file($real)) {
            Log::warning('VideoStreamController::stream rejecting path', ['filePath' => $filePath, 'realPath' => $real]);
            abort(404);
        }

        $size = filesize($real);
        $mime = mime_content_type($real) ?: 'application/octet-stream';

        $start = 0;
        $end = $size - 1;
        $status = 200;

        if ($request->headers->has('range')) {
            $range = $request->header('range'); // e.g. "bytes=0-1023"
            if (preg_match('/bytes=(\d+)-(?:(\d+))?/', $range, $matches)) {
                $start = intval($matches[1]);
                if (isset($matches[2]) && $matches[2] !== '') {
                    $end = intval($matches[2]);
                }

                // Clamp values
                $start = max(0, $start);
                $end = min($end, $size - 1);

                if ($start > $end) {
                    return response('', 416);
                }

                $status = 206;
            }
        }

        $length = $end - $start + 1;

        $response = new StreamedResponse(function () use ($real, $start, $length) {
            $handle = fopen($real, 'rb');
            if ($handle === false) {
                return;
            }

            fseek($handle, $start);
            $buffer = 1024 * 8; // 8KB chunks
            $sent = 0;

            while (! feof($handle) && $sent < $length) {
                $read = min($buffer, $length - $sent);
                $data = fread($handle, $read);
                echo $data;
                flush();
                $sent += strlen($data);
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }

            fclose($handle);
        }, $status);

        $response->headers->set('Content-Type', $mime);
        $response->headers->set('Content-Length', $length);
        $response->headers->set('Accept-Ranges', 'bytes');

        if ($status === 206) {
            $response->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $size));
        }

        return $response;
    }
}
