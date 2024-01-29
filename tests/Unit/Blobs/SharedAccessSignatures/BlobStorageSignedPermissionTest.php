<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Unit\Blobs\SharedAccessSignatures;

use PHPUnit\Framework\TestCase;
use Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures\BlobStorageSignedPermission;

class BlobStorageSignedPermissionTest extends TestCase
{
    public static function permissionsData(): array
    {
        return [
            "nothing" => [[], ""],
            "all" => [BlobStorageSignedPermission::cases(), 'racwdxyltfmeopi'],
            "reversed" => [array_reverse(BlobStorageSignedPermission::cases()), 'racwdxyltfmeopi'],
        ];
    }


    /**
     * @dataProvider permissionsData
     */
    public function test_combines_permissions_to_correct_access_signature_strings(array $input, string $expected): void
    {
        $this->assertEquals($expected, BlobStorageSignedPermission::toSignedPermissionString($input));
    }
}
