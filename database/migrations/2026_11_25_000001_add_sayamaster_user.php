<?php

use App\Models\Event;
use App\Models\EventUser;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $event = Event::where('status', 'active')->first() ?? Event::first();
        if (!$event) {
            return;
        }

        $master = User::updateOrCreate(
            ['phone' => 'sayamaster'],
            [
                'name' => 'Master Admin FAS 2026',
                'email' => 'sayamaster@faslombok2026.id',
                'password' => Hash::make('master354'),
                'role' => 'master',
                'status' => 'active',
            ]
        );

        EventUser::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $master->id],
            ['event_role' => 'master']
        );
    }

    public function down(): void
    {
        User::where('phone', 'sayamaster')->delete();
    }
};
