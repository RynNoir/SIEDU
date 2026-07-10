<?php

use App\Models\EvaluationPeriod;
use App\Models\User;

test('membuka periode menutup periode open lain (periode tunggal)', function () {
    $admin = User::factory()->admin()->create();
    $lama = EvaluationPeriod::factory()->open()->create();
    $baru = EvaluationPeriod::factory()->create(); // draft

    $this->actingAs($admin)->post(route('admin.evaluation-periods.open', $baru))
        ->assertRedirect();

    expect($baru->fresh()->status->value)->toBe('open');
    expect($lama->fresh()->status->value)->toBe('closed');
});
