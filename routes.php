<?php
use Waka\Utils\Models\JobList;

Route::get('/api/utils', function () {
    $user = BackendAuth::getUser();
    if (!$user) {
        return null;
    }
    $jobList = JobList::where('user_id', $user->id);
    return response()->json([
        'error' => JobList::OnlyUser()->state('error')->count(),
        'end' => JobList::OnlyUser()->state('end')->count(),
        'run' => JobList::OnlyUser()->state('run')->count(),
    ], 200);
})->middleware('web');
