<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JudgeController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/pengumuman', [PublicController::class, 'pengumuman'])->name('pengumuman');
Route::get('/pengumuman/{competition}', [PublicController::class, 'hasilDetail'])->name('pengumuman.detail');
Route::get('/juara-umum', [PublicController::class, 'juaraUmum'])->name('juara-umum');

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Judge Portal (Mobile-First)
Route::prefix('juri')->name('judge.')->middleware(['judge'])->group(function () {
    Route::get('/', [JudgeController::class, 'dashboard'])->name('dashboard');
    Route::get('/scan', [JudgeController::class, 'scanner'])->name('scanner');
    Route::post('/resolve-qr', [JudgeController::class, 'resolveQr'])->name('resolve-qr');
    Route::get('/lomba/{competition}', [JudgeController::class, 'competitionEntries'])->name('competition.entries');
    Route::get('/nilai/{entry}', [JudgeController::class, 'scoringForm'])->name('scoring.form');
    Route::post('/nilai/{entry}', [JudgeController::class, 'submitScore'])->name('scoring.submit');
});

// Master Admin Portal
Route::prefix('master')->name('master.')->middleware(['master'])->group(function () {
    Route::get('/', [MasterController::class, 'dashboard'])->name('dashboard');

    // Competitions & Rubrics
    Route::get('/lomba', [MasterController::class, 'competitions'])->name('competitions');
    Route::post('/lomba', [MasterController::class, 'storeCompetition'])->name('competitions.store');
    Route::get('/lomba/{competition}', [MasterController::class, 'competitionDetail'])->name('competitions.detail');
    Route::post('/lomba/{competition}/rubrik', [MasterController::class, 'updateRubricCriteria'])->name('competitions.rubric.update');
    Route::post('/lomba/{competition}/lock', [MasterController::class, 'toggleLock'])->name('competitions.lock');
    Route::post('/lomba/{competition}/publish', [MasterController::class, 'publish'])->name('competitions.publish');

    // Participants & Teams
    Route::get('/peserta', [MasterController::class, 'participants'])->name('participants');
    Route::post('/peserta', [MasterController::class, 'storeParticipant'])->name('participants.store');
    Route::put('/peserta/{participant}', [MasterController::class, 'updateParticipant'])->name('participants.update');
    Route::delete('/peserta/{participant}', [MasterController::class, 'destroyParticipant'])->name('participants.destroy');
    Route::post('/peserta/import', [MasterController::class, 'importParticipants'])->name('participants.import');
    Route::get('/cetak-qr', [MasterController::class, 'printQr'])->name('print.qr');

    // Groups / Kelompok / TPQ
    Route::post('/kelompok', [MasterController::class, 'storeGroup'])->name('groups.store');
    Route::put('/kelompok/{group}', [MasterController::class, 'updateGroup'])->name('groups.update');
    Route::delete('/kelompok/{group}', [MasterController::class, 'destroyGroup'])->name('groups.destroy');

    // Grade Levels / Tingkat Kelas
    Route::post('/tingkat-kelas', [MasterController::class, 'storeGradeLevel'])->name('grade-levels.store');
    Route::put('/tingkat-kelas/{gradeLevel}', [MasterController::class, 'updateGradeLevel'])->name('grade-levels.update');
    Route::delete('/tingkat-kelas/{gradeLevel}', [MasterController::class, 'destroyGradeLevel'])->name('grade-levels.destroy');

    // Judges & Permissions
    Route::get('/juri', [MasterController::class, 'judges'])->name('judges');
    Route::post('/juri', [MasterController::class, 'storeJudge'])->name('judges.store');
    Route::post('/juri/assign', [MasterController::class, 'assignJudge'])->name('judges.assign');
    Route::delete('/juri/assign/{assignment}', [MasterController::class, 'revokeJudgeAssignment'])->name('judges.assign.revoke');

    // Queue & Real-time Calling
    Route::get('/antrean', [MasterController::class, 'queue'])->name('queue');
    Route::post('/antrean/{entry}/status', [MasterController::class, 'updateQueueStatus'])->name('queue.status');

    // Scores Monitoring Matrix & Audit
    Route::get('/monitoring-nilai', [MasterController::class, 'scoresMonitoring'])->name('scores');

    // Reports & CSV Exports
    Route::get('/laporan', [MasterController::class, 'reports'])->name('reports');
    Route::get('/laporan/export-csv/{competition}', [MasterController::class, 'exportCompetitionCsv'])->name('reports.export.csv');
});
