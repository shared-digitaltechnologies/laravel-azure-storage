<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Extensions;

use Shrd\Laravel\Azure\Storage\Tables\Entity;

trait MakesFakeEntities
{
    protected function makeFakeEntity(): Entity
    {
        $faker = $this->fake();

        return new Entity([
            "PartitionKey" => $faker->bothify('#####????-?????-##'),
            "RowKey" => $faker->bothify('?????-??????-####'),
            "TestNumber" => $faker->numberBetween(),
            "TestDouble" => $faker->randomFloat(),
            "TestBoolean" => $faker->boolean(),
            "TestText" => $faker->text(),
        ]);
    }
}
