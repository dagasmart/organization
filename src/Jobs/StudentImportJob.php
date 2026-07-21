<?php

namespace DagaSmart\Organization\Jobs;

use DagaSmart\Organization\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class StudentImportJob implements ShouldQueue // ✅ 必须实现此接口
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数
     */
    public int $tries = 3;

    /**
     * 任务超时时间（秒），根据文件大小调整
     */
    public int $timeout = 600;

    /**
     * 失败后等待重试的间隔（秒）
     */
    public int $backoff = 60;

    private string $filePath;

    private int $userId;

    private string $batchId;

    public function __construct(?string $filePath = null, ?int $userId = 0, ?string $batchId = null)
    {
        \Log::info('准备分发任务', ['userId' => $batchId]);
        $this->filePath = $filePath ?? null;
        $this->userId = $userId ?? 0;
        $this->batchId = $batchId ?? Str::uuid()->toString();

        // 指定专用队列，避免阻塞默认队列
        $this->onQueue('student-import');
    }

    public function handle(): void
    {
        \Log::info('准备分发任务', ['userId' => $this->batchId]);

        $rows = Student::all();

        fastexcel()->data($rows->toArray())->export(public_storage_path('users.xlsx'));

        \Log::info('任务分发完成');

    }

}
