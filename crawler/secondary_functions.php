<?php
    include 'console_text.php';
    include 'extra_functions.php';

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
                $purl = $node->getAttribute('href');
                if(strpos($purl, $storeUrl) === false) {
                    $full_url = "https://" . $storeUrl . $node->getAttribute('href');
                } else {
                    $full_url = $purl;
                }
                if(!is_duplicate($full_url, $productUrls)) {
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

    function scrapeProductData($p, $productUrl) {
        $jsonUrl = 'https://' . $productUrl . '.json';
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
        clear_line();
        echo $productTitle;
        $description = strip_tags($productData['body_html']);
        $category = $productData['product_type'] ? $productData['product_type'] : '';

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
                'Category' => $category,
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