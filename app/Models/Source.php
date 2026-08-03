<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A web page a subject is grounded in: the guide teaches *from this material*
 * rather than from the subject name alone.
 *
 * The extracted text is kept whole so a later pass can chunk and cite it
 * (docs/PRODUCT.md §7); today only a truncated head reaches the prompt.
 */
#[Fillable(['conversation_id', 'url', 'title', 'site', 'text', 'words'])]
class Source extends Model
{
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** What to show on a chip: the page's own title, else the host. */
    public function displayTitle(): string
    {
        return $this->title ?: ($this->site ?: $this->url);
    }

    /**
     * The head of the extract, bounded by words rather than characters so the
     * cut never lands mid-token in a way the model has to guess about.
     */
    public function extract(int $maxWords): string
    {
        return Str::words($this->text, $maxWords, ' …');
    }

    protected function casts(): array
    {
        return [
            'words' => 'integer',
        ];
    }
}
