<?php
    function getShopList() {
        if (!file_exists(__DIR__.'/../shops.txt')) {
            echo "shops.txt not found.\n";
            return false;
        }

        $storeUrls = file(__DIR__.'/../shops.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if(empty($storeUrls)) {
            echo "shops.txt is empty.\n";
            return false;
        }

        $storeUrls = filter_domains($storeUrls);
        return $storeUrls;
    }

    function fetchProductUrls($count, $i, $storeUrl) {
        echo "$i of $count.\tFetching products from [" . constyle(strtoupper($storeUrl), 33) . "]\n\n";

        $collectionUrl = 'https://' . $storeUrl . '/collections/all';
        $productUrls = [];
        $page = 1;

        do {
            $url = $page == 1 ? $collectionUrl : $collectionUrl . "?page=$page";
            
            $xpath = getXPathData($url);

            if($xpath == "break") break;
            // echo $url."\n";
            $nodes = $xpath->query("//a[contains(@href, '/products/')]");
            // echo "\t\t\tTotal Nodes: " . count($nodes) . "\n\n";
            $i = 0;
            foreach ($nodes as $node) {
                $purl = $node->getAttribute('href');
                if(strpos($purl, $storeUrl) === false) {
                    $full_url = "https://" . $storeUrl . $node->getAttribute('href');
                } else {
                    $full_url = $purl;
                }
                if(strpos($full_url, '#') !== false) {
                    $full_url = explode('#', $full_url)[0];
                }
                if(!is_duplicate($full_url, $productUrls)) {
                    $productUrls[] = $full_url;
                    $i++;
                }
            }

            if($i<1) break;
            else $page++;

            clear_line();
            echo constyle("\tPage: ", 92).constyle($page-1, 91).constyle(" ==> URLs in the Page: ", 92).constyle($i, 91).constyle(" ==> Total Product URLs: ", 92).constyle(count($productUrls), 91);

        } while (!empty($nodes));

        clear_line();
        echo constyle("\tCalculating...", 94);
        sleep(1);
        clear_line();
        echo "\t" . constyle("Total Product URLs Found: ", 93).constyle(constyle(count($productUrls), 91), 1) . "\n\n";

        return array_unique($productUrls);
    }

    function scrapeProductData($count, $p, $v, $productUrl) {
        $prcnt = 0;
        $jsonUrl = $productUrl . '.json';
        $response = @file_get_contents($jsonUrl, false, get_context());

        if ($response === false) {
            if (isset($http_response_header)) {
                if(is_array($http_response_header) && strpos($http_response_header[0], '404') !== false) {
                    echo "\t" . constyle("WARNING: Product not found: $productUrl", 93) . "\n";
                    return null;
                }
            } else {
                echo "\n\t" . constyle("ERROR: No internet connection. Please try again later.", 91) . "\n";
                return "return";
            }
        }

        $decoded = @json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $productData = $decoded['product'];
        if (!$productData) {
            return null;
        }

        $productInfo = [];
        $productTitle = $productData['title'];
        $description = strip_tags($productData['body_html']);
        $category = $productData['product_type'] ? $productData['product_type'] : '';

        $images = [];
        foreach ($productData['images'] as $img) {
            $images[$img['id']] = $img['src'];
        }

        $p++;
        foreach ($productData['variants'] as $variant) {

            if($variant['price'] == 0 || $variant['price'] == "") {
                continue;
            }

            if($variant['compare_at_price'] && $variant['compare_at_price'] != "") {
                $regularPrice = $variant['compare_at_price'];
                $salePrice = $variant['price'];
            } else {
                $regularPrice = $variant['price'];
                $salePrice = "";
            }

            if($regularPrice <= 0 || $regularPrice == "") {
                if( $salePrice != "" && $salePrice > 0) {
                    $regularPrice = $salePrice;
                    $salePrice = "";
                } else {
                    continue;
                }
            } else if($regularPrice == $salePrice) {
                $salePrice = "";
            } else if ($salePrice > $regularPrice) {
                $temp = $regularPrice;
                $regularPrice = $salePrice;
                $salePrice = $temp;
            }

            $variantTitle = $variant['title'];
            $mainImageUrl = $images[$variant['image_id']] ?? reset($images);
            $title = "$productTitle - $variantTitle";

            $prcnt = round(($p / $count) * 100, 0);
            clear_line();
            echo "\t[". constyle("PRODUCTS: ", 94) . constyle($p, 91) . "] [" . constyle("VARIANTS: ", 94) . constyle($v, 91) . "] [" . constyle("PROGRESS: ", 94) . constyle($prcnt."%", 91) . "]";

            $productInfo[] = [
                'ID'            =>  $variant['id'],
                'Title'         =>  $title,
                'Category'      =>  $category,
                'Regular_Price' =>  $regularPrice,
                'Sale_Price'    =>  $salePrice,
                'Brand'         =>  $productData['vendor'],
                'URL'           =>  $productUrl . '?variant=' . $variant['id'],
                'Image_URL'     =>  $mainImageUrl,
                'Description'   =>  $description,
            ];
        }
        return $productInfo;
    }
?>