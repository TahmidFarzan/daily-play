<?php
namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MediaHelper
{
    public const DEFAULT_CONVERSION = 'preview';
    public const DEFAULT_CONVERSION_FORMAT = 'webp';

    public const ROLE_DEFAULT           = 'Default';
    public const ROLE_PROFILE_IMAGE     = 'Profile Image';
    public const ROLE_APP_LOGO_IMAGE    = 'App Logo Image';
    public const ROLE_APP_FAVICON_IMAGE = 'App Favicon Image';

    public const ROLE_NEWS_FEATURE_IMAGE        = 'News Feature Image';
    public const ROLE_NEWS_FEATURE_IMAGE_MOBILE = 'News Feature Image (Mobile)';

    public const ROLE_NEWS_CONTENT_IMAGE = 'News Content Image';

    public const ROLE_NEWS_GALLERY_IMAGE = 'News Gallery Image';

    public const ROLE_EVENT_BANNER_IMAGE_DESKTOP = 'Event Banner Image (Desktop)';
    public const ROLE_EVENT_BANNER_IMAGE_MOBILE  = 'Event Banner Image (Mobile)';

    public static function mediaRoles(): Collection
    {
        return SystemHelper::toOptions([
            self::ROLE_DEFAULT,

            self::ROLE_PROFILE_IMAGE,

            self::ROLE_APP_LOGO_IMAGE,
            self::ROLE_APP_FAVICON_IMAGE,

            self::ROLE_NEWS_FEATURE_IMAGE,
            self::ROLE_NEWS_FEATURE_IMAGE_MOBILE,
            self::ROLE_NEWS_CONTENT_IMAGE,
            self::ROLE_NEWS_GALLERY_IMAGE,

            self::ROLE_EVENT_BANNER_IMAGE_DESKTOP,
            self::ROLE_EVENT_BANNER_IMAGE_MOBILE,
        ]);
    }

    public static function generateMediaName(string $mediaName, string $mediaExtension, string | int $maxLength)
    {
        $mediaName = $mediaName ?? "File";
        $mediaName = Str::of($mediaName)->limit($maxLength)->__toString();

        $mediaName    = preg_replace('/[^a-z0-9]+/', '', strtolower($mediaName));
        $randomString = Str::random(5);

        $timestamp              = date('Ymdhis');
        $userId                 = Auth::check() ? '-u' . Auth::id() : '';
        $mediaNameWithExtension = "{$mediaName}-{$randomString}-{$timestamp}{$userId}.{$mediaExtension}";

        return $mediaNameWithExtension;
    }

    public static function parseAndRebuildUrl($url)
    {
        $cleanUrl = $url;
        if (! ($url == null) && (strpos($url, '?') !== false)) {
            $urlComponents = parse_url($url);
            $cleanUrl      = $urlComponents['scheme'] . '://' . $urlComponents['host'] . $urlComponents['path'];
        }
        return $cleanUrl;
    }

    public static function defaultAppImage($resulation = "1:1", $mediaName = null)
    {
        $mediaUrl     = self::demoImageUrlByResulation($resulation, "App") ?? null;
        $replacements = ['&' => 'and', "'" => ''];

        $formatedMediaName = Str::replace(array_keys($replacements), array_values($replacements), $mediaName);
        $mediaFileName     = Str::lower(Str::slug($formatedMediaName));

        $mediaPath = "uploads/icons/app/{$mediaFileName}.png";

        $mediaPublicPath = public_path($mediaPath);

        if (file_exists($mediaPublicPath)) {
            $mediaUrl = asset($mediaPath);
        }

        return $mediaUrl;
    }

    public static function defaultAuthImage($resulation = "1:1", $mediaName = "user")
    {
        $mediaUrl     = self::demoImageUrlByResulation($resulation, "App") ?? null;
        $replacements = ['&' => 'and', "'" => ''];

        $formatedMediaName = Str::replace(array_keys($replacements), array_values($replacements), $mediaName);
        $mediaFileName     = Str::lower(Str::slug($formatedMediaName));
        $mediaPath         = "uploads/icons/auth/{$mediaFileName}.png";

        $mediaPublicPath = public_path($mediaPath);

        if (file_exists($mediaPublicPath)) {
            $mediaUrl = asset($mediaPath);
        }

        return $mediaUrl;
    }

    public static function demoImageUrlByResulation($resulation = "1:1", $text = null)
    {
        return self::imageUrlGenerateFormOnlineByResulation($resulation, $text);
    }

    public static function demoImageUrl($imageWidth, $imageHeight, $text = null)
    {
        return self::imageUrlGenerateFormOnline($imageWidth, $imageHeight, $text);
    }

    private static function imageUrlGenerateFormOnlineByResulation($resulation, $text = null)
    {
        $imageSize = self::imageWidthHeightByRatio($resulation);

        $imageWidth  = $imageSize["width"];
        $imageHeight = $imageSize["height"];

        return self::imageUrlGenerateFormOnline($imageWidth, $imageHeight, $text);
    }

    private static function imageUrlGenerateFormOnline($imageWidth, $imageHeight, $text = null, $imageBgColor = "ededed", $imageBgTextColor = "000000")
    {
        $imageBgColor     = $imageBgColor ?? "ededed";
        $imageBgTextColor = $imageBgTextColor ?? "000000";

        $url = "https://dummyimage.com/{$imageWidth}x{$imageHeight}/{$imageBgColor}/{$imageBgTextColor}";

        if ($text && ! ($text == null)) {
            $url = "$url&text={$text}";
        }
        return $url;
    }

    private static function imageWidthHeightByRatio($resulation = "1:1")
    {
        $width  = 512;
        $height = 512;

        switch ($resulation) {
            case "16:9":
                $width  = 1280;
                $height = 720;
                break;

            case "4:3":
                $width  = 400;
                $height = 300;
                break;

            case "3:4":
                $width  = 300;
                $height = 400;
                break;

            case "2:3":
                $width  = 400;
                $height = 600;
                break;

            case "3:2":
                $width  = 600;
                $height = 400;
                break;

            case "1:1":
                $width  = 512;
                $height = 512;
                break;

            case "8:1":
                $width  = 728;
                $height = 90;
                break;

            case "1:1.2":
                $width  = 300;
                $height = 250;
                break;

            default:
                $width  = 400;
                $height = 255;
                break;
        }

        return [
            "width"  => $width,
            "height" => $height,
        ];
    }
}
