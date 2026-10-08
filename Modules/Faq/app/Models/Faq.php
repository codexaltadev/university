<?php

namespace Modules\Faq\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Faq\Database\Factories\FaqFactory;
use Platform\Contracts\Scopeable;

class Faq extends Model implements Scopeable
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $table = 'faq_faqs';

    protected $fillable = ['question', 'answer', 'department_id'];

    public function scopeKeys(): array
    {
        return $this->department_id ? ["department:{$this->department_id}"] : [];
    }

    protected static function newFactory(): FaqFactory
    {
        return FaqFactory::new();
    }
}
