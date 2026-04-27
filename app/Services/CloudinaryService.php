<?php

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    protected Cloudinary $cloudinary;

    protected UploadApi $uploadApi;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => config('cloudinary.cloud_name'),
                    'api_key' => config('cloudinary.api_key'),
                    'api_secret' => config('cloudinary.api_secret'),
                ],
                'url' => ['secure' => true],
            ])
        );

        $this->uploadApi = new UploadApi;
    }

    /**
     * Upload a product cover/image.
     */
    public function uploadProductImage(UploadedFile $file, ?string $publicId = null): array
    {
        return $this->upload($file, 'products', $publicId, [
            'transformation' => config('cloudinary.transformations.product_cover'),
        ]);
    }

    /**
     * Upload a user avatar.
     */
    public function uploadAvatar(UploadedFile $file, string $userId): array
    {
        return $this->upload($file, 'avatars', "avatar_{$userId}", [
            'transformation' => config('cloudinary.transformations.avatar'),
            'invalidate' => true,
        ]);
    }

    /**
     * Upload a seller banner.
     */
    public function uploadBanner(UploadedFile $file, string $sellerId): array
    {
        return $this->upload($file, 'sellers', "banner_{$sellerId}", [
            'transformation' => config('cloudinary.transformations.banner'),
            'invalidate' => true,
        ]);
    }

    /**
     * Upload a category image/icon.
     */
    public function uploadCategoryImage(UploadedFile $file, string $slug): array
    {
        return $this->upload($file, 'categories', "cat_{$slug}", [
            'transformation' => config('cloudinary.transformations.category_icon'),
        ]);
    }

    /**
     * Upload a KYC document (private, not delivered via CDN).
     */
    public function uploadKycDocument(UploadedFile $file, string $userId): array
    {
        return $this->upload($file, 'kyc', "kyc_{$userId}_".time(), [
            'type' => 'private',
            'resource_type' => 'auto',
        ]);
    }

    /**
     * Delete an asset by public_id.
     */
    public function delete(string $publicId): bool
    {
        $result = $this->uploadApi->destroy($publicId);

        return ($result['result'] ?? '') === 'ok';
    }

    /**
     * Generate a transformation URL for an existing asset.
     */
    public function url(string $publicId, string $preset = 'product_cover'): string
    {
        return $this->cloudinary->image($publicId)
            ->format('auto')
            ->quality('auto')
            ->toUrl();
    }

    /**
     * Core upload method.
     */
    protected function upload(
        UploadedFile $file,
        string $folderKey,
        ?string $publicId,
        array $extraOptions = []
    ): array {
        $folder = config("cloudinary.folders.{$folderKey}", config('cloudinary.folder'));

        $options = array_merge([
            'folder' => $folder,
            'resource_type' => 'image',
            'quality' => 'auto',
            'fetch_format' => 'auto',
        ], $extraOptions);

        if ($publicId) {
            $options['public_id'] = $publicId;
            $options['overwrite'] = true;
        }

        $result = $this->uploadApi->upload($file->getRealPath(), $options);

        return [
            'public_id' => $result['public_id'],
            'url' => $result['secure_url'],
            'width' => $result['width'],
            'height' => $result['height'],
            'format' => $result['format'],
            'bytes' => $result['bytes'],
        ];
    }
}
