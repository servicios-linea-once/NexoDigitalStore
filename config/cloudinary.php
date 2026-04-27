<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    | Used for all image storage: products, avatars, banners, categories.
    */
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
    'api_key' => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'url' => env('CLOUDINARY_URL'),

    /*
    |--------------------------------------------------------------------------
    | Folder Structure
    |--------------------------------------------------------------------------
    */
    'folder' => env('CLOUDINARY_FOLDER', 'nexo-digital-store'),

    'folders' => [
        'products' => env('CLOUDINARY_FOLDER', 'nexo-digital-store').'/products',
        'avatars' => env('CLOUDINARY_FOLDER', 'nexo-digital-store').'/avatars',
        'categories' => env('CLOUDINARY_FOLDER', 'nexo-digital-store').'/categories',
        'banners' => env('CLOUDINARY_FOLDER', 'nexo-digital-store').'/banners',
        'sellers' => env('CLOUDINARY_FOLDER', 'nexo-digital-store').'/sellers',
        'kyc' => env('CLOUDINARY_FOLDER', 'nexo-digital-store').'/kyc',   // private
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Presets & Transformations
    |--------------------------------------------------------------------------
    */
    'transformations' => [
        'product_cover' => [
            'width' => 600,
            'height' => 600,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'product_thumb' => [
            'width' => 200,
            'height' => 200,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'avatar' => [
            'width' => 200,
            'height' => 200,
            'crop' => 'fill',
            'gravity' => 'face',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'banner' => [
            'width' => 1200,
            'height' => 400,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
        'category_icon' => [
            'width' => 120,
            'height' => 120,
            'crop' => 'fill',
            'quality' => 'auto',
            'format' => 'auto',
        ],
    ],
];
