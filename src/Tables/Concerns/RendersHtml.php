<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;

trait RendersHtml
{
    public function url($cursor): string
    {
        // If we have any extra query string key / value pairs that need to be added
        // onto the URL, we will put them in query string form and then attach it
        // to the URL. This allows for extra information like sortings storage.
        $parameters = is_null($cursor) ? [] : [$this->cursorName => $cursor->encode()];

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path()
            .(str_contains($this->path(), '?') ? '&' : '?')
            .Arr::query($parameters)
            .$this->buildFragment();
    }

    public function render($view = null, $data = []): View|string
    {
        return Paginator::viewFactory()
            ->make($view ?: Paginator::$defaultSimpleView, array_merge($data, [
                'paginator' => $this,
            ]));
    }
}
