<?php
    include 'console_text.php';

    function get_context()
    {
        $options  = array(
            "http" => array(
                'user_agent' => 'Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0',
                'method' => 'GET',
            ), 
            "ssl"=>array(
                "verify_peer"           =>  false,
                "verify_peer_name"      =>  false,
                'allow_self_signed'     =>  true,
                'verify_depth'          =>  0,
                'curl_verify_ssl_peer'  =>  false,
                'curl_verify_ssl_host'  =>  false,
            ),
        );
        $context  = stream_context_create($options);
        return $context;
    }

    function getXPathData($url) {
        $html = file_get_contents($url, false, get_context()); //@

        if (strpos($html, '404 Not Found') !== false || strpos($html, 'Page Not Found') !== false) {
            return "break";
        }

        if ($html === false) {
            echo "Failed to fetch $url\n";
            return "break";
        }

        libxml_use_internal_errors(true); // suppress errors
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors(); // clear errors

        $headers = $dom->getElementsByTagName('header');
        while ($headers->length > 0) {
            $header = $headers->item(0);
            $header->parentNode->removeChild($header);
        }

        $footers = $dom->getElementsByTagName('footer');
        while ( $footers && $footers->length > 0) {
            $footer = $footers->item(0);
            $footer->parentNode->removeChild($footer);
        }

        $xpath = new DOMXPath($dom);
        return $xpath;
    }

    function fetchProductUrls($i, $storeUrl) {
        echo "$i.\tFetching products from [" . constyle(strtoupper($storeUrl), 35) . "]\n\n";

        $collectionUrl = 'https://' . $storeUrl . '/collections/all';
        $productUrls = [];
        $page = 1;

        do {
            $url = $page == 1 ? $collectionUrl : $collectionUrl . "?page=$page";
            
            $xpath = getXPathData($url);

            if($xpath == "break") break;

            $nodes = $xpath->query("//a[contains(@href, '/products/')]");
            $i = 0;
            foreach ($nodes as $node) {
                $full_url = $storeUrl . $node->getAttribute('href');
                if(!in_array($full_url, $productUrls)) {
                    $productUrls[] = $full_url;
                    $i++;
                }
            }

            if($i<1) break;
            else $page++;

            clear_line();
            echo constyle("\tPage: ", 32).constyle($page-1, 31).constyle(" ==> URLs in the Page: ", 32).constyle($i, 31).constyle(" ==> Total Product URLs: ", 32).constyle(count($productUrls), 31);

        } while (!empty($nodes));

        echo "\n\n" . constyle("\tTotal Product URLs Found: ", 33).constyle(constyle(count($productUrls), 31), 1) . "\n\n";

        return array_unique($productUrls);
    }

    function scrapeProductData($productUrl) {
        $jsonUrl = $productUrl . '.json';
        $response = @file_get_contents($jsonUrl);

        if ($response === false) {
            return null;
        }

        $productData = json_decode($response, true)['product'];
        if (!$productData) {
            return null;
        }

        $productInfo = [];
        $productTitle = $productData['title'];
        $description = strip_tags($productData['body_html']);
        $category = $productData['product_type'];
        $tags = $productData['tags'] ? implode(' > ', explode(', ', $productData['tags'])) : '';

        $categoryPath = $category . ($tags ? ' > ' . $tags : '');

        $images = [];
        foreach ($productData['images'] as $img) {
            $images[$img['id']] = $img['src'];
        }

        foreach ($productData['variants'] as $variant) {
            $variantTitle = $variant['title'];
            $regularPrice = $variant['compare_at_price'] ?? $variant['price'];
            $salePrice = $variant['compare_at_price'] ? $variant['price'] : '';
            $mainImageUrl = $images[$variant['image_id']] ?? reset($images);
            $title = "$productTitle - $variantTitle";

            $productInfo[] = [
                'Title' => $title,
                'Description' => $description,
                'Category' => $categoryPath,
                'Regular Price' => $regularPrice,
                'Sale Price' => $salePrice,
                'Main Image URL' => $mainImageUrl,
            ];
        }

        return $productInfo;
    }

    function saveToJson($filename, $data) {
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }

    function saveToCsv($filename, $data) {
        $fp = fopen($filename, 'w');
        fputcsv($fp, array_keys($data[0]));

        foreach ($data as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
    }
?>