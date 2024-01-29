<?php

namespace Shrd\Laravel\Azure\Storage\Tables;


use Illuminate\Contracts\Container\BindingResolutionException;

class Model extends Entity
{
    use Model\HasTableConnection,
        Model\HasTable;

    /**
     * @var array<string, EdmType>
     */
    protected array $schema = [];

    /**
     * @throws BindingResolutionException
     */
    public function __construct($entity = null, string $etag = '')
    {
        parent::__construct($entity, $etag);
        $this->initConnection();
    }

    public function getType(): string
    {
        return class_basename($this);
    }

    public function getGlobalId(): string
    {
        $key = $this->getKey();
        $type = $this->getType();
        return base64_encode("$type:$key");
    }

    public function __toString(): string
    {
        return $this->getGlobalId();
    }

    public function setAttribute(string $name, $value, ?EdmType $type = null, $rawValue = ''): static
    {
        if($type === null) {
            $name = $this->getPropertyName($name);
            if(in_array($name, $this->schema)) {
                $type = $this->schema;
            }
        }

        return parent::setAttribute($name, $value, $type, $rawValue);
    }
}
