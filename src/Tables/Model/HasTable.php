<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Model;

use Illuminate\Support\Str;

trait HasTable
{
    protected ?string $table = null;

    public function getTable(): string
    {
        if($this->table === null) {
            $this->table = Str::pluralStudly(class_basename(static::class));
        }
        return $this->table;
    }
}
