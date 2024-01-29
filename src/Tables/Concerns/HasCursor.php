<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Concerns;

use MicrosoftAzure\Storage\Table\Models\TableContinuationTokenTrait;
use Safe\Exceptions\UrlException;
use Shrd\Laravel\Azure\Storage\Tables\Cursor;

trait HasCursor
{
    use TableContinuationTokenTrait;

    public function getCursor(): ?Cursor
    {
        return Cursor::coerce($this->getContinuationToken());
    }

    /**
     * @throws UrlException
     */
    public function setCursor($cursor): static
    {
        $this->setContinuationToken(Cursor::coerce($cursor));
        return $this;
    }
}
