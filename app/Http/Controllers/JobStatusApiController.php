<?php

namespace App\Http\Controllers;

use App\Services\JobStatusChecker;
use Illuminate\Http\Request;

class JobStatusApiController extends Controller
{
    protected $jobStatusChecker;

    /**
     * Constructor
     */
    public function __construct(JobStatusChecker $jobStatusChecker)
    {
        $this->jobStatusChecker = $jobStatusChecker;
    }

    /**
     * Get the status of running jobs
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        $jobsRunning = $this->jobStatusChecker->areJobsRunning();
        $jobsInfo = $jobsRunning ? $this->jobStatusChecker->getRunningJobsInfo() : [];

        return response()->json([
            'jobsRunning' => $jobsRunning,
            'jobsInfo' => $jobsInfo
        ]);
    }
}
