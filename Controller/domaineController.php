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
    function extractDateFromUrl($url)
    {
        $filename = basename($url);
        $dateString = substr($filename, 0, 8); // Extract the first 8 characters as the date string
        $year = substr($dateString, 0, 4);
        $month = substr($dateString, 4, 2);
        $day = substr($dateString, 6, 2);
        $date = "$year-$month-$day";
        
        return $date;
    }

    public function writeLog($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function extractDomain($domain)
    {
        $domain = str_replace('.fr', '', $domain);
        $domainParts = explode('.', $domain);

        if (count($domainParts) >= 2) {
            return $domainParts[count($domainParts) - 2] . '.' . $domainParts[count($domainParts) - 1];
        }

        return $domain;
    }

    public function processFileContent($date = null)
    {
        // Get the current date minus one day if no date is provided
        if ($date === null) {
            $currentDate = date('Ymd', strtotime('-1 day'));
        } else {
            $currentDate = $date;
        }

        // Construct the URL with the dynamic date
        $url = 'https://www.example.com/files/' . $currentDate . '.txt';

        $client = new Client();
        $response = $client->get($url); // Use the dynamic URL
        $fileContent = $response->getBody();

        $lines = explode("\n", $fileContent);
        foreach ($lines as $line) {
            $domain = $this->extractDomain(trim($line));
            $date = $this->extractDateFromUrl($url); // Use the dynamic URL

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM domaines WHERE nom = :domain AND date = :date");
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
                echo 'Script skipped!';
            }
        }
    }
}

