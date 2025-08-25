<?php

namespace App\Services\Core;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoService
{
    /**
     * isRestrictedRoute
     *
     * @param Request $request
     * 
     * @return bool
     */
    public function isRestrictedRoute(Request $request): bool
    {
        $currentRoute = $request->route()->getName();
        $features = config('demo.feature', []);
        foreach ($features as $config) {
            if (isset($config['routes']) && in_array($currentRoute, $config['routes'], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * getFeatureForRoute
     *
     * @param Request $request
     * 
     * @return string|null
     */
    public function getFeatureForRoute(Request $request): ?string
    {
        $currentRoute = $request->route()->getName();
        $features = config('demo.feature', []);
        foreach ($features as $feature => $config) {
            if (isset($config['routes']) && in_array($currentRoute, $config['routes'], true)) {
                return $feature;
            }
        }
        return null;
    }

    /**
     * isFeatureEnabled
     *
     * @param string $feature
     * 
     * @return bool
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return config("demo.feature.{$feature}.enabled", false);
    }

    /**
     * getRestrictedKeys
     *
     * @param string $feature
     * 
     * @return array
     */
    public function getRestrictedKeys(string $feature): array
    {
        $restrictions = config("demo.feature.{$feature}.restrictions", []);
        return collect($restrictions)
            ->flatMap(function ($value, $key) {
                return $this->flattenKeys($value);
            })->all();
    }

    /**
     * getMessageForFeature
     *
     * @param string $feature
     * 
     * @return string
     */
    public function getMessageForFeature(string $feature): string
    {
        return config("demo.feature.{$feature}.default", $this->getGlobalMessage());
    }

    /**
     * flattenKeys
     *
     * @param array $keys
     * 
     * @return array
     */
    public function flattenKeys(array $keys): array
    {
        return collect($keys)
            ->flatMap(function ($value, $key) {
                if (is_array($value)) return $this->flattenKeys($value);
                return [$value];
            })->all();
    }

    /**
     * filterRestrictedKeys
     *
     * @param array $data
     * @param array $restrictedKeys
     * 
     * @return array
     */
    public function filterRestrictedKeys(array $data, array $restrictedKeys): array
    {
        return collect($data)
            ->filter(function ($value, $key) use ($restrictedKeys) {
                return !in_array($key, $restrictedKeys, true);
            })->mapWithKeys(function ($value, $key) use ($restrictedKeys) {
                if (is_array($value)) {
                    return [$key => $this->filterRestrictedKeys($value, $restrictedKeys)];
                }
                return [$key => $value];
            })->all();
    }

    /**
     * hasRestrictedKeys
     *
     * @param array $data
     * @param array $restrictedKeys
     * 
     * @return bool
     */
    public function hasRestrictedKeys(array $data, array $restrictedKeys): bool
    {
        return collect($data)
            ->contains(function ($value, $key) use ($restrictedKeys) {
                if (in_array($key, $restrictedKeys, true)) return true;
                if (is_array($value)) return $this->hasRestrictedKeys($value, $restrictedKeys);
                return false;
            });
    }

    /**
     * getGlobalMessage
     *
     * @return string
     */
    public function getGlobalMessage(): string
    {
        return config('demo.messages.global');
    }

    /**
     * appendGlobalMessage
     *
     * @param mixed $response
     * @param Request $request
     * @param string $status
     * 
     * @return mixed
     */
    public function appendGlobalMessage($response, Request $request, string $status = 'error'): mixed
    {
        if ($request->expectsJson()) {
            $content = json_decode($response->getContent(), true);
            if (is_array($content)) {
                $content = Arr::set(
                    array: $content,
                    key: 'message',
                    value: Arr::has($content, 'message')
                        ? Arr::get($content, 'message') . '. ' . $this->getGlobalMessage()
                        : $this->getGlobalMessage()
                );
                return new JsonResponse($content);
            }
        } else {
            $notify = session('notify', []);
            $existingMessage = collect($notify)->pluck(1)->first();
            $message = $existingMessage
                ? $existingMessage . '. ' . $this->getGlobalMessage()
                : $this->getGlobalMessage();
            $status = collect($notify)->pluck(0)->first() ?? $status;
            $notify = [[$status, $message]];
            return $response->with('notify', $notify);
        }

        return $response;
    }

    /**
     * resetDatabase
     *
     * @return void
     */
    public function resetDatabase(): void
    {
        if (!config('demo.enabled')) return;
        if (!$this->validateResetConfig()) return;

        $lockFile = storage_path('demo_reset.lock');
        $handle = $this->acquireFileLock($lockFile);

        if (!$handle) return;

        try {
            $now = Carbon::now();
            $lastReset = $this->getLastResetTime();
            $nextReset = $lastReset 
                ? $this->calculateNextResetTime($lastReset) 
                : $now;

            if ($now->greaterThanOrEqualTo($nextReset)) {
                $this->executeDatabaseReset($now);
            }
        } catch (\Throwable $throwable) {
        } finally {
            $this->releaseFileLock($handle);
        }
    }

    /**
     * validateResetConfig
     *
     * @return bool
     */
    protected function validateResetConfig(): bool
    {
        $unit = config('demo.database_reset_unit', 'hour');
        $duration = config('demo.database_reset_duration', 4);
        $validUnits = [
            'second', 
            'minute', 
            'hour', 
            'day', 
            'month', 
            'year'
        ];

        return in_array($unit, $validUnits, true) && $duration > 0;
    }

    /**
     * acquireFileLock
     *
     * @param string $lockFile
     * 
     * @return mixed
     */
    protected function acquireFileLock(string $lockFile): mixed
    {
        $handle = fopen($lockFile, 'a+');
        if (!$handle || !flock($handle, LOCK_EX | LOCK_NB)) {
            if ($handle) {
                fclose($handle);
            }
            return null;
        }
        return $handle;
    }

    /**
     * releaseFileLock
     *
     * @param mixed $handle
     * 
     * @return void
     */
    protected function releaseFileLock($handle): void
    {
        if ($handle) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * getLastResetTime
     *
     * @return Carbon|null
     */
    protected function getLastResetTime(): ?Carbon
    {
        $resetFile = storage_path('demo_reset.json');
        if (Storage::exists('demo_reset.json')) {
            $data = json_decode(Storage::get('demo_reset.json'), true);
            if (isset($data['last_reset_at'])) {
                return Carbon::parse($data['last_reset_at']);
            }
        }
        return null;
    }

    /**
     * calculateNextResetTime
     *
     * @param Carbon $lastReset
     * 
     * @return Carbon
     */
    protected function calculateNextResetTime(Carbon $lastReset): Carbon
    {
        $unit = config('demo.database_reset_unit', 'hour');
        $duration = config('demo.database_reset_duration', 4);
        $nextReset = $lastReset->copy();

        match ($unit) {
            'second' => $nextReset->addSeconds($duration),
            'minute' => $nextReset->addMinutes($duration),
            'hour' => $nextReset->addHours($duration),
            'day' => $nextReset->addDays($duration),
            'month' => $nextReset->addMonths($duration),
            'year' => $nextReset->addYears($duration),
        };

        return $nextReset;
    }

    /**
     * executeDatabaseReset
     *
     * @param Carbon $now
     * 
     * @return void
     */
    protected function executeDatabaseReset(Carbon $now): void
    {
        $sqlFile = storage_path('../resources/database/database.sql');
        if (file_exists($sqlFile)) {
            $tables = DB::select('SHOW TABLES');
            $database = DB::getDatabaseName();
            $tableKey = 'Tables_in_' . $database;

            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                DB::statement("DROP TABLE IF EXISTS `$tableName`");
            }
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            DB::unprepared(file_get_contents($sqlFile));
            Storage::put('demo_reset.json', json_encode(['last_reset_at' => $now->toDateTimeString()]));
        }
    }
}