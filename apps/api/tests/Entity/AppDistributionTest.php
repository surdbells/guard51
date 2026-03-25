<?php

declare(strict_types=1);

namespace Guard51\Tests\Entity;

use Guard51\Entity\AppDownloadLog;
use Guard51\Entity\AppKey;
use Guard51\Entity\AppPlatform;
use Guard51\Entity\AppRelease;
use Guard51\Entity\ReleaseType;
use Guard51\Entity\TenantAppConfig;
use PHPUnit\Framework\TestCase;

class AppDistributionTest extends TestCase
{
    // ── AppKey Enum ──────────────────────────────────

    public function testAppKeyLabels(): void
    {
        $this->assertEquals('Guard Mobile App', AppKey::GUARD->label());
        $this->assertEquals('Client Mobile App', AppKey::CLIENT->label());
        $this->assertEquals('Desktop App (Windows)', AppKey::DESKTOP_WINDOWS->label());
    }

    public function testAppKeyMobileDesktop(): void
    {
        $this->assertTrue(AppKey::GUARD->isMobile());
        $this->assertTrue(AppKey::CLIENT->isMobile());
        $this->assertFalse(AppKey::GUARD->isDesktop());

        $this->assertTrue(AppKey::DESKTOP_WINDOWS->isDesktop());
        $this->assertFalse(AppKey::DESKTOP_WINDOWS->isMobile());

        $this->assertCount(4, AppKey::mobileApps());
        $this->assertCount(3, AppKey::desktopApps());
    }

    public function testAppKeyDefaultPlatform(): void
    {
        $this->assertEquals(AppPlatform::ANDROID, AppKey::GUARD->defaultPlatform());
        $this->assertEquals(AppPlatform::WINDOWS, AppKey::DESKTOP_WINDOWS->defaultPlatform());
        $this->assertEquals(AppPlatform::MACOS, AppKey::DESKTOP_MAC->defaultPlatform());
        $this->assertEquals(AppPlatform::LINUX, AppKey::DESKTOP_LINUX->defaultPlatform());
    }

    // ── AppPlatform Enum ─────────────────────────────

    public function testPlatformFileExtensions(): void
    {
        $this->assertEquals('apk', AppPlatform::ANDROID->fileExtension());
        $this->assertEquals('ipa', AppPlatform::IOS->fileExtension());
        $this->assertEquals('exe', AppPlatform::WINDOWS->fileExtension());
        $this->assertEquals('dmg', AppPlatform::MACOS->fileExtension());
        $this->assertEquals('AppImage', AppPlatform::LINUX->fileExtension());
    }

    public function testPlatformMobile(): void
    {
        $this->assertTrue(AppPlatform::ANDROID->isMobile());
        $this->assertTrue(AppPlatform::IOS->isMobile());
        $this->assertFalse(AppPlatform::WINDOWS->isMobile());
    }

    // ── ReleaseType Enum ─────────────────────────────

    public function testReleaseTypeProduction(): void
    {
        $this->assertTrue(ReleaseType::STABLE->isProduction());
        $this->assertFalse(ReleaseType::BETA->isProduction());
        $this->assertFalse(ReleaseType::ALPHA->isProduction());
    }

    // ── AppRelease Entity ────────────────────────────

    public function testAppReleaseCreation(): void
    {
        $release = new AppRelease();
        $release->setAppKey(AppKey::GUARD)
            ->setPlatform(AppPlatform::ANDROID)
            ->setVersion('1.2.3')
            ->setVersionCode(10203)
            ->setReleaseType(ReleaseType::STABLE)
            ->setFileUrl('apps/guard/android/guard-1.2.3.apk')
            ->setFileSizeBytes(15234567)
            ->setFileHashSha256('abc123def456')
            ->setReleaseNotes('Bug fixes and GPS improvements.')
            ->setIsMandatory(false)
            ->setUploadedBy('admin-123');

        $this->assertNotEmpty($release->getId());
        $this->assertEquals(AppKey::GUARD, $release->getAppKey());
        $this->assertEquals(AppPlatform::ANDROID, $release->getPlatform());
        $this->assertEquals('1.2.3', $release->getVersion());
        $this->assertEquals(10203, $release->getVersionCode());
        $this->assertEquals(15234567, $release->getFileSizeBytes());
        $this->assertFalse($release->isMandatory());
        $this->assertTrue($release->isActive());
        $this->assertEquals(0, $release->getDownloadCount());
    }

    public function testAppReleaseVersionComparison(): void
    {
        $release = new AppRelease();
        $release->setVersion('1.3.0')->setVersionCode(10300);

        $this->assertTrue($release->isNewerThan('1.2.0'));
        $this->assertTrue($release->isNewerThan('1.2.9'));
        $this->assertFalse($release->isNewerThan('1.3.0'));
        $this->assertFalse($release->isNewerThan('1.4.0'));
        $this->assertFalse($release->isNewerThan('2.0.0'));
    }

    public function testAppReleaseFileSizeFormatted(): void
    {
        $release = new AppRelease();

        $release->setFileSizeBytes(500);
        $this->assertEquals('500 bytes', $release->getFileSizeFormatted());

        $release->setFileSizeBytes(2048);
        $this->assertEquals('2 KB', $release->getFileSizeFormatted());

        $release->setFileSizeBytes(15234567);
        $this->assertEquals('14.53 MB', $release->getFileSizeFormatted());

        $release->setFileSizeBytes(1073741824);
        $this->assertEquals('1 GB', $release->getFileSizeFormatted());
    }

    public function testAppReleaseDownloadCount(): void
    {
        $release = new AppRelease();
        $this->assertEquals(0, $release->getDownloadCount());

        $release->incrementDownloads();
        $release->incrementDownloads();
        $release->incrementDownloads();
        $this->assertEquals(3, $release->getDownloadCount());
    }

    public function testAppReleaseDeactivateReactivate(): void
    {
        $release = new AppRelease();
        $this->assertTrue($release->isActive());

        $release->deactivate();
        $this->assertFalse($release->isActive());

        $release->reactivate();
        $this->assertTrue($release->isActive());
    }

    public function testAppReleaseSignedUrl(): void
    {
        $release = new AppRelease();
        $release->setAppKey(AppKey::GUARD)
            ->setPlatform(AppPlatform::ANDROID)
            ->setVersion('1.0.0')
            ->setVersionCode(1)
            ->setFileUrl('test.apk')
            ->setFileSizeBytes(100)
            ->setFileHashSha256('abc')
            ->setUploadedBy('admin');

        $secret = 'test_secret_123';
        $url = $release->generateSignedUrl($secret, 3600);

        $this->assertStringContainsString($release->getId(), $url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringContainsString('sig=', $url);
    }

    public function testAppReleaseVerifySignedUrl(): void
    {
        $secret = 'test_secret_for_signing';
        $releaseId = 'release-abc-123';
        $expires = time() + 3600;

        $payload = "{$releaseId}:{$expires}";
        $signature = hash_hmac('sha256', $payload, $secret);

        // Valid signature
        $this->assertTrue(AppRelease::verifySignedUrl($releaseId, $expires, $signature, $secret));

        // Wrong secret
        $this->assertFalse(AppRelease::verifySignedUrl($releaseId, $expires, $signature, 'wrong_secret'));

        // Expired
        $expiredTime = time() - 10;
        $expiredPayload = "{$releaseId}:{$expiredTime}";
        $expiredSig = hash_hmac('sha256', $expiredPayload, $secret);
        $this->assertFalse(AppRelease::verifySignedUrl($releaseId, $expiredTime, $expiredSig, $secret));

        // Tampered release ID
        $this->assertFalse(AppRelease::verifySignedUrl('different-id', $expires, $signature, $secret));
    }

    public function testAppReleaseToArray(): void
    {
        $release = new AppRelease();
        $release->setAppKey(AppKey::SUPERVISOR)
            ->setPlatform(AppPlatform::ANDROID)
            ->setVersion('2.0.0')
            ->setVersionCode(20000)
            ->setReleaseType(ReleaseType::BETA)
            ->setFileUrl('test.apk')
            ->setFileSizeBytes(5000000)
            ->setFileHashSha256('hash123')
            ->setUploadedBy('admin-1');

        $array = $release->toArray();

        $this->assertEquals('supervisor', $array['app_key']);
        $this->assertEquals('Supervisor Mobile App', $array['app_name']);
        $this->assertEquals('android', $array['platform']);
        $this->assertEquals('2.0.0', $array['version']);
        $this->assertEquals('beta', $array['release_type']);
        $this->assertEquals('4.77 MB', $array['file_size_formatted']);
        $this->assertArrayHasKey('uploaded_at', $array);
    }

    // ── AppDownloadLog Entity ────────────────────────

    public function testAppDownloadLogCreation(): void
    {
        $log = new AppDownloadLog();
        $log->setReleaseId('release-1')
            ->setTenantId('tenant-1')
            ->setDownloadedBy('user-1')
            ->setIpAddress('192.168.1.1')
            ->setUserAgent('Guard51-App/1.0');

        $this->assertNotEmpty($log->getId());
        $this->assertEquals('release-1', $log->getReleaseId());
        $this->assertEquals('tenant-1', $log->getTenantId());
        $this->assertNotNull($log->getDownloadedAt());
    }

    // ── TenantAppConfig Entity ───────────────────────

    public function testTenantAppConfig(): void
    {
        $config = new TenantAppConfig();
        $config->setTenantId('tenant-1')
            ->setAppKey('guard')
            ->setAutoUpdate(true)
            ->setPinnedVersion(null)
            ->setSettings(['theme' => 'dark']);

        $this->assertTrue($config->isAutoUpdate());
        $this->assertFalse($config->isPinned());
        $this->assertEquals(['theme' => 'dark'], $config->getSettings());

        // Pin to specific version
        $config->setPinnedVersion('1.5.0');
        $this->assertTrue($config->isPinned());
        $this->assertEquals('1.5.0', $config->getPinnedVersion());
    }
}
