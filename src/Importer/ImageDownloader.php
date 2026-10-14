<?php

declare(strict_types=1);

namespace App\Importer;

use App\Crawler\MulClient;
use App\Database\ConnectionGuard;
use App\Model\Units;
use App\Model\UnitsQuery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

final class ImageDownloader
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly MulClient $client,
        private readonly string $imageDir,
        ?LoggerInterface $logger = null,
        private readonly ?ConnectionGuard $guard = null,
    ) {
        $this->logger = $logger ?? new NullLogger();

        if (!is_dir($this->imageDir) && !mkdir($this->imageDir, 0755, true)) {
            throw new RuntimeException("Cannot create image dir: {$this->imageDir}");
        }
    }

    /**
     * Download images for all units that have an image_url but no local file.
     * Existing files are not re-checked: every request to MUL costs 10 s.
     *
     * @return array{downloaded: int, adopted: int, skipped: int, failed: int}
     */
    public function downloadAll(): array
    {
        $counts = ['downloaded' => 0, 'adopted' => 0, 'skipped' => 0, 'failed' => 0];

        /** @var Units[] $units */
        $units = UnitsQuery::create()
            ->filterByImageUrl(null, \Propel\Runtime\ActiveQuery\Criteria::NOT_EQUAL)
            ->find();

        foreach ($units as $unit) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    // A retry after a lost connection only has to store what the first try fetched.
                    if ($unit->isModified()) {
                        $unit->save();
                        $counts['downloaded']++;
                    } else {
                        $counts[$this->download($unit)]++;
                    }
                    break;
                } catch (\Throwable $e) {
                    if ($attempt === 1 && $this->guard?->recover() === true) {
                        $this->logger->warning('Database connection was lost and has been reopened');
                        continue;
                    }
                    $this->logger->warning('Image download failed', [
                        'unit'  => $unit->getMulUid() ?? $unit->getMulId(),
                        'url'   => $unit->getImageUrl(),
                        'error' => $e->getMessage(),
                    ]);
                    $counts['failed']++;
                    break;
                }
            }
        }

        return $counts;
    }

    /** @return 'downloaded'|'adopted'|'skipped' */
    public function download(Units $unit): string
    {
        $url = $unit->getImageUrl();
        if ($url === null || $url === '') {
            return 'skipped';
        }

        $existing = $unit->getImagePath();
        if ($existing !== null && is_file($this->imageDir . '/' . basename($existing))) {
            return 'skipped';
        }

        $ext      = $this->extensionFromUrl($url);
        $file     = ($unit->getMulUid() ?? (string) $unit->getMulId()) . '.' . $ext;
        $localRel = 'images/units/' . $file;
        $localAbs = $this->imageDir . '/' . $file;

        // A file copied over from another installation saves a 10 s request.
        if (is_file($localAbs)) {
            $unit->setImagePath($localRel);
            $unit->setImageHash(hash_file('sha256', $localAbs) ?: null);
            $unit->save();
            return 'adopted';
        }

        $body = $this->client->fetchRaw($url, useCache: false);
        $hash = hash('sha256', $body);

        if (file_put_contents($localAbs, $body) === false) {
            throw new RuntimeException("Cannot write image: {$localAbs}");
        }

        $unit->setImagePath($localRel);
        $unit->setImageHash($hash);
        $unit->save();

        $this->logger->info('Image downloaded', ['unit' => $unit->getMulUid() ?? $unit->getMulId(), 'path' => $localRel]);

        return 'downloaded';
    }

    private function extensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (is_string($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
                return $ext;
            }
        }
        return 'png';
    }
}
