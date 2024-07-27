<?php
    include 'secondary_functions.php';

    function part1() {
        echo "\n" . constyle(constyle("[PART-1]", 1), 36) .": Fetching product URLs ===> \n\n";

        if (!file_exists(__DIR__.'/../shops.txt')) {
            echo "shops.txt not found.\n";
            return;
        }

        $storeUrls = file(__DIR__.'/../shops.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $i = 0;
        foreach ($storeUrls as $storeUrl) {
            $i++;
            if(strpos($storeUrl, 'http') !== false || strpos($storeUrl, '/') !== false) {
                $storeDomain = parse_url($storeUrl, PHP_URL_HOST);
            } else {
                $storeDomain = $storeUrl;
            }

            $productUrls = fetchProductUrls($i, $storeDomain);
            if ($productUrls) {
                saveToJson(__DIR__."/../shops/$storeDomain.json", $productUrls);
            }
        }
    }

    function part2() {
        $shopFiles = glob(__DIR__.'/../shops/*.json');
        foreach ($shopFiles as $shopFile) {
            $storeDomain = basename($shopFile, '.json');
            $productUrls = json_decode(file_get_contents($shopFile), true);
            $allProducts = [];

            foreach ($productUrls as $productUrl) {
                $productData = scrapeProductData($productUrl);
                if ($productData) {
                    $allProducts = array_merge($allProducts, $productData);
                }
            }

            if (count($allProducts) === count($productUrls)) {
                saveToCsv(__DIR__."/../feeds/$storeDomain.csv", $allProducts);
                unlink($shopFile);
            }
        }
    }
?>