<?php

require 'vendor/autoload.php';
require 'Model/Domaine.php';

use Domaine;
use autoload;
use GuzzleHttp\Client;

class DomainesController
{
    private $db;
    private $fileUrl;
    private $logFile;

    public function __construct($dbHost, $dbName, $dbUser, $dbPass, $fileUrl, $logFile)
    {
        $this->db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->fileUrl = $fileUrl;
        $this->logFile = $logFile;
    }

    public function writeLog($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function extractDomain($url)
    {
        $url = str_replace('.fr', '', $url);
        $domainParts = explode('.', $url);

        if (count($domainParts) >= 2) {
            return $domainParts[count($domainParts) - 2] . '.' . $domainParts[count($domainParts) - 1];
        }

        return $url;
    }

    public function processFileContent()
    {
        $client = new Client();
        $response = $client->get($this->fileUrl);
        $fileContent = $response->getBody();

        $lines = explode("\n", $fileContent);
        foreach ($lines as $line) {
            $url = trim($line);
            $domain = $this->extractDomain($url);
            $date = date('Y-m-d');

            $stmt = $this->db->prepare('SELECT COUNT(*) FROM domaines WHERE nom = :domain AND date = :date');
            $stmt->bindValue(':domain', $domain);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                $stmt = $this->db->prepare('INSERT INTO domaines (nom, date) VALUES (:domain, :date)');
                $stmt->bindParam(':domain', $domain);
                $stmt->bindParam(':date', $date);
                $stmt->execute();

                $this->writeLog("Domaine: $domain, Date: $date");
                echo 'Script executed successfully!';
            } else {
                $this->writeLog("Skipping duplicate domain: $domain, Date: $date");
                echo 'Script failed!';
            }
        }
    }
}

