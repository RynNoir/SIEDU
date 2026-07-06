<?php

namespace App\Models;

use Database\Factories\EvaluationImpressionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evaluation_id', 'impression_text', 'suggestion_text'])]
class EvaluationImpression extends Model
{
    /** @use HasFactory<EvaluationImpressionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
