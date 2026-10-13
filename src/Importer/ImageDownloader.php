<?php

declare(strict_types=1);

namespace App\Importer;

use App\Crawler\MulClient;
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
     * @return array{downloaded: int, skipped: int, failed: int}
     */
    public function downloadAll(): array
    {
        $counts = ['downloaded' => 0, 'skipped' => 0, 'failed' => 0];

        /** @var Units[] $units */
        $units = UnitsQuery::create()
            ->filterByImageUrl(null, \Propel\Runtime\ActiveQuery\Criteria::NOT_EQUAL)
            ->find();

        foreach ($units as $unit) {
            try {
                $result = $this->downloadOne($unit);
                if ($result) {
                    $counts['downloaded']++;
                } else {
                    $counts['skipped']++;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Image download failed', [
                    'unit' => $unit->getMulUid() ?? $unit->getMulId(),
                    'url'    => $unit->getImageUrl(),
                    'error'  => $e->getMessage(),
                ]);
                $counts['failed']++;
            }
        }

        return $counts;
    }

    /**
     * Download image for a single unit. Returns true if file was written, false if skipped.
     */
    private function downloadOne(Units $unit): bool
    {
        $url = $unit->getImageUrl();
        if ($url === null || $url === '') {
            return false;
        }

        $existing = $unit->getImagePath();
        if ($existing !== null && is_file($this->imageDir . '/' . basename($existing))) {
            return false;
        }

        $ext      = $this->extensionFromUrl($url);
        $file     = ($unit->getMulUid() ?? (string) $unit->getMulId()) . '.' . $ext;
        $localRel = 'images/units/' . $file;
        $localAbs = $this->imageDir . '/' . $file;

        $body = $this->client->fetchRaw($url, useCache: false);
        $hash = hash('sha256', $body);

        if ($unit->getImageHash() === $hash && file_exists($localAbs)) {
            $this->logger->debug('Image unchanged, skipping', ['unit' => $unit->getMulUid() ?? $unit->getMulId()]);
            return false;
        }

        if (file_put_contents($localAbs, $body) === false) {
            throw new RuntimeException("Cannot write image: {$localAbs}");
        }

        $unit->setImagePath($localRel);
        $unit->setImageHash($hash);
        $unit->save();

        $this->logger->info('Image downloaded', ['unit' => $unit->getMulUid() ?? $unit->getMulId(), 'path' => $localRel]);

        return true;
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
