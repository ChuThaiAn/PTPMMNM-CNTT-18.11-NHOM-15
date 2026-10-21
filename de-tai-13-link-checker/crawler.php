<?php
class WebCrawler {
    private $pdo;
    private $scanRunId;
    private $allowedHost;
    private $visitedUrls = [];
    private $maxPages;
    private $maxDepth;

    public function __construct($pdo, $scanRunId, $startUrl, $maxPages, $maxDepth) {
        $this->pdo = $pdo;
        $this->scanRunId = $scanRunId;
        $this->allowedHost = parse_url($startUrl, PHP_URL_HOST);
        $this->maxPages = $maxPages;
        $this->maxDepth = $maxDepth;
    }

    public function start($url, $depth = 0) {
        if ($depth > $this->maxDepth || count($this->visitedUrls) >= $this->maxPages) {
            return;
        }

        $normalizedUrl = $this->normalizeUrl($url);
        if (isset($this->visitedUrls[$normalizedUrl])) {
            return;
        }
        $this->visitedUrls[$normalizedUrl] = true;

        if (parse_url($normalizedUrl, PHP_URL_HOST) !== $this->allowedHost) {
            return;
        }

        $html = $this->fetchPageContent($normalizedUrl, $statusCode);
        
        $stmt = $this->pdo->prepare("INSERT INTO pages (scan_run_id, url, status_code, depth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->scanRunId, $normalizedUrl, $statusCode, $depth]);
        $sourcePageId = $this->pdo->lastInsertId();

        if ($statusCode !== 200 || empty($html)) {
            return;
        }

        $resources = $this->extractLinksAndImages($html, $normalizedUrl);

        foreach ($resources as $res) {
            $targetUrl = $res['url'];
            $type = $res['type'];

            $targetStatus = $this->checkTargetStatus($targetUrl, $errorType);

            $linkStmt = $this->pdo->prepare("INSERT INTO links (scan_run_id, source_page_id, target_url, resource_type, status_code, error_type) VALUES (?, ?, ?, ?, ?, ?)");
            $linkStmt->execute([$this->scanRunId, $sourcePageId, $targetUrl, $type, $targetStatus, $errorType]);

            if ($type === 'link' && $targetStatus === 200 && parse_url($targetUrl, PHP_URL_HOST) === $this->allowedHost) {
                $this->start($targetUrl, $depth + 1);
            }
        }
    }

    private function fetchPageContent($url, &$statusCode) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'LinkCheckerBot/1.0');
        
        $html = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $statusCode = 0;
        }
        curl_close($ch);
        return $html;
    }

    private function checkTargetStatus($url, &$errorType) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo = curl_errno($ch);
        curl_close($ch);

        $errorType = null;
        if ($errNo !== 0) {
            $errorType = 'timeout_or_connection_error';
            return 0;
        }

        if ($statusCode >= 400) {
            $errorType = 'broken_http_' . $statusCode;
        }

        return $statusCode;
    }

    private function extractLinksAndImages($html, $baseUrl) {
        $results = [];
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('a') as $node) {
            $href = $node->getAttribute('href');
            if (!empty($href)) {
                $abs = $this->resolveUrl($href, $baseUrl);
                if ($abs) $results[] = ['url' => $abs, 'type' => 'link'];
            }
        }

        foreach ($dom->getElementsByTagName('img') as $node) {
            $src = $node->getAttribute('src');
            if (!empty($src)) {
                $abs = $this->resolveUrl($src, $baseUrl);
                if ($abs) $results[] = ['url' => $abs, 'type' => 'image'];
            }
        }

        return $results;
    }

    private function resolveUrl($rel, $base) {
        if (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
        if ($rel[0] == '#' || strpos($rel, 'javascript:') === 0) return null;

        $parsed = parse_url($base);
        $scheme = $parsed['scheme'];
        $host = $parsed['host'];
        $path = isset($parsed['path']) ? $parsed['path'] : '/';

        if ($rel[0] == '/') {
            $path = '';
        }

        $pathDir = dirname($path);
        if ($pathDir == DIRECTORY_SEPARATOR) $pathDir = '';

        $uri = "$scheme://$host" . $pathDir . "/" . $rel;
        return filter_var($uri, FILTER_VALIDATE_URL);
    }

    private function normalizeUrl($url) {
        return strtok($url, '#');
    }
}