<?php
    function part1() {
        echo "\n" . constyle(constyle("[PART-1]", 1), 96) .": Fetching product URLs ===> \n\n";

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
        echo "\n" . constyle(constyle("[PART-2]", 1), 96) .": Crawling product data ===> \n\n";
        $shopFiles = glob(__DIR__.'/../shops/*.json');

        $i = 0;
        foreach ($shopFiles as $shopFile) {
            $storeDomain = basename($shopFile, '.json');
            $productUrls = json_decode(file_get_contents($shopFile), true);
            $allProducts = [];
            
            $fp = fopen(__DIR__ . '/../feeds/' . $storeDomain . '.csv', 'w');
            fputcsv($fp, array("ID", "Title", "Category", "Regular Price", "Sale Price", "URL", "ImageURL", "Description"));
            
            echo ++$i . ".\tCrawling products from [" . constyle(strtoupper($storeDomain), 95) . "]\n\n";
            
            $p = 0; $v = 0;
            foreach ($productUrls as $productUrl) {
                $productData = scrapeProductData($p, $v, $productUrl);
                if ($productData) {
                    foreach ($productData as $product) {
                        fputcsv($fp, $product);
                    }
                    $p++;
                    $v += count($productData);
                }
            }
            fclose($fp);

            clear_line();
            echo constyle("\tCalculating...\n", 94);
            unlink($shopFile);
            sleep(1);
            echo "\n\t" . constyle("Total Products Crawled: ", 93) . constyle($p, 91) . " ==> " . constyle("Total items Found: ", 93) . constyle($v, 91) . "\n\n";
        }
    }
?>