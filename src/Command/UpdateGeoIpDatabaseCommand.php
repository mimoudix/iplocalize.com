<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:geoip:update',
    description: 'Downloads and updates the MaxMind GeoIP databases (Country, City, ASN)',
)]
class UpdateGeoIpDatabaseCommand extends Command
{
    private SymfonyStyle $io;
    private string $licenseKey;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger // Standard logger (auto-wired)
    ) {
        parent::__construct();
        $this->licenseKey = $_ENV['MAXMIND_LICENSE_KEY'];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. FIX: Allow unlimited memory for the large City database
        ini_set('memory_limit', '-1');

        $this->io = new SymfonyStyle($input, $output);
        $this->io->title('Starting MaxMind GeoIP Databases Update');

        $databases = [
            'GeoLite2-Country' => 'geoip_country_db_path',
            'GeoLite2-City'    => 'geoip_city_db_path',
            'GeoLite2-ASN'     => 'geoip_asn_db_path',
        ];

        $hasErrors = false;

        foreach ($databases as $editionId => $parameterName) {
            $this->io->section("Updating: $editionId");

            try {
                if (!$this->params->has($parameterName)) {
                    throw new \Exception("Parameter '$parameterName' is missing in services.yaml");
                }
                
                $targetPath = $this->params->get($parameterName);
                $this->processDatabase($editionId, $targetPath);
                
                $this->logger->info("$editionId updated successfully.");
                $this->io->success("$editionId updated.");

            } catch (\Exception $e) {
                $hasErrors = true;
                $msg = "Error updating $editionId: " . $e->getMessage();
                $this->logger->error($msg);
                $this->io->error($msg);
            }
        }

        if ($hasErrors) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function processDatabase(string $editionId, string $targetDbPath): void
    {
        $targetFolder = dirname($targetDbPath);

        if (!is_dir($targetFolder)) {
            mkdir($targetFolder, 0755, true);
        }

        $downloadUrl = "https://download.maxmind.com/app/geoip_download?edition_id={$editionId}&license_key={$this->licenseKey}&suffix=tar.gz";
        $tempTarGz = $targetFolder . "/temp_{$editionId}.tar.gz";
        $tempTar   = $targetFolder . "/temp_{$editionId}.tar";

        try {
            $this->io->text("Downloading $editionId...");
            
            // 2. FIX: Use copy() to stream download (Low RAM usage)
            if (!@copy($downloadUrl, $tempTarGz)) {
                $error = error_get_last();
                throw new \Exception('Failed to download file: ' . ($error['message'] ?? 'Unknown error'));
            }

            $this->io->text('Decompressing archive...');
            if (file_exists($tempTar)) unlink($tempTar);
            
            $phar = new \PharData($tempTarGz);
            $phar->decompress();

            $phar = new \PharData($tempTar);
            $phar->extractTo($targetFolder, null, true);

            $extractedDirs = glob($targetFolder . "/{$editionId}_*", GLOB_ONLYDIR);
            
            if (empty($extractedDirs)) {
                throw new \Exception('Extraction failed, no directory found.');
            }
            
            $extractedDir = $extractedDirs[0];
            $sourceMmdb = $extractedDir . "/{$editionId}.mmdb";

            if (!file_exists($sourceMmdb)) {
                throw new \Exception("{$editionId}.mmdb not found inside extracted folder.");
            }

            $this->io->text("Moving to: $targetDbPath");
            if (!copy($sourceMmdb, $targetDbPath)) {
                throw new \Exception("Failed to copy database to final location.");
            }

        } finally {
            $this->io->text('Cleaning up temporary files...');
            if (file_exists($tempTarGz)) @unlink($tempTarGz);
            if (file_exists($tempTar))   @unlink($tempTar);
            
            if (isset($extractedDir) && is_dir($extractedDir)) {
                array_map('unlink', glob("$extractedDir/*"));
                @rmdir($extractedDir);
            }
        }
    }
}