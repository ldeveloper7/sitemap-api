<?php
function CallAPI($method, $url, $data = false) {
    $curl = curl_init();
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "username:password");
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
        return $result;
}
// https://www.w3schools.com/angular/customers.php
// https://api.shadowandact.com/v1/recent-comment/25/0
// https://api.blavity.com/v1/userSearch/12266?search=
// https://dev.portiqo.com/blog/main-sitemap.xsl
// https://dev.portiqo.com/blog/sitemap_index.xml
$url = "https://dev.portiqo.com/blog/sitemap_index.xml";
$method = "GET";
// echo "<pre>";
// echo CallAPI($method,$url);
$myXMLData =CallAPI($method,$url);
$xml=simplexml_load_string($myXMLData) or die("Error: Cannot create object");
print_r($xml);
?>
