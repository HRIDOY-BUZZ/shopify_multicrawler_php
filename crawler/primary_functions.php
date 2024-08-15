<?php
    function part1() {
        echo "\n" . constyle(constyle("[PART-1]", 1), 96) .": Fetching product URLs ===> \n\n";

        if (!file_exists(__DIR__.'/../shops.txt')) {
            echo "shops.txt not found.\n";
            return false;
        }

        $storeUrls = file(__DIR__.'/../shops.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if(empty($storeUrls)) {
            echo "shops.txt is empty.\n";
            return false;
        }
        
        $i = 0;
        foreach ($storeUrls as $storeUrl) {
            $i++;
            $storeUrl = trim($storeUrl);
            if(strpos($storeUrl, 'http') !== false || strpos($storeUrl, '/') !== false) {
                $storeDomain = parse_url($storeUrl, PHP_URL_HOST);
            } else {
                $storeDomain = $storeUrl;
            }

            if (!is_dir(__DIR__ . '/../shops/')) {
                mkdir(__DIR__ . '/../shops/');
            }

            if (!is_dir(__DIR__ . '/../shops/')) {
                echo "\t" . constyle("Error creating directory: `shops`. Please check permissions...", 91) . "\n\n";
                return false;
            } else {
                $productUrls = fetchProductUrls(count($storeUrls), $i, $storeDomain);
                if ($productUrls) {
                    saveToJson(__DIR__."/../shops/$storeDomain.json", $productUrls);
                }
            }
        }
        return true;
    }

    function part2() {
        echo "\n" . constyle(constyle("[PART-2]", 1), 96) .": Crawling product data ===> \n\n";
        $shopFiles = glob(__DIR__.'/../shops/*.json');
        if(count($shopFiles) == 0) {
            echo "No shop files found in shops directory.\n";
            return false;
        }

        $i = 0;
        foreach ($shopFiles as $shopFile) {
            $storeDomain = basename($shopFile, '.json');
            $productUrls = json_decode(file_get_contents($shopFile), true);
            $allProducts = [];
            
            if (!is_dir(__DIR__ . '/../feeds/')) {
                mkdir(__DIR__ . '/../feeds/');
            }

            if (!is_dir(__DIR__ . '/../feeds/')) {
                echo "\t" . constyle("Error creating directory: `feeds`. Please check permissions...", 91) . "\n\n";
                return false;
            } else {
                $csvFilePath = __DIR__ . '/../feeds/' . $storeDomain . '.csv';
                if (!$fp = @fopen($csvFilePath, 'w')) {
                    echo constyle("\nError: Unable to open file: ".$csvFilePath, 91) . "\n\n";
                    echo constyle("Please check if the file is already open.", 91) . "\n\n";
                    return false;
                }

                fputcsv($fp, array("ID", "Title", "Category", "Regular Price", "Sale Price", "Brand", "URL", "ImageURL", "Description"));
                
                echo ++$i . " of ". count($shopFiles) . ".\tCrawling products from [" . constyle(strtoupper($storeDomain), 33) . "]\n\n";
                
                $p = 0; $v = 0;
                foreach ($productUrls as $productUrl) {
                    $productData = scrapeProductData(count($productUrls), $p, $v, $productUrl);
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
                echo constyle("\tCalculating...", 94);
                unlink($shopFile);
                sleep(1);
                clear_line();
                echo "\t" . constyle("Total Products Crawled: ", 93) . constyle($p, 91) . " ==> " . constyle("Total items Found: ", 93) . constyle($v, 91) . "\n\n";
            }
        }
        return true;
    }
?>