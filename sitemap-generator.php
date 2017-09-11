<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "simple_html_dom.php";
$file = "dev_portiqo2.xml";
$start_url = "https://dev.portiqo.com/";
define ('CLI', true);
$skip = array (".pdf");
$extension = array (".html", ".php","/");
$freq = "daily";
$priority = "1.0";
define ('VERSION', "1.0");
define ('NL', CLI ? "\n" : "<br>");
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
function rel2abs($rel, $base) {
	if(strpos($rel,"//") === 0) {
		return "http:".$rel;
	}
	/* return if  already absolute URL */
	if  (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
	$first_char = substr ($rel, 0, 1);
	/* queries and  anchors */
	if ($first_char == '#'  || $first_char == '?') return $base.$rel;
	/* parse base URL  and convert to local variables:
	$scheme, $host,  $path */
	extract(parse_url($base));
	/* remove  non-directory element from path */
	$path = preg_replace('#/[^/]*$#',  '', $path);
	/* destroy path if  relative url points to root */
	if ($first_char ==  '/') $path = '';
	/* dirty absolute  URL */
	$abs =  "$host$path/$rel";
	/* replace '//' or  '/./' or '/foo/../' with '/' */
	$re =  array('#(/.?/)#', '#/(?!..)[^/]+/../#');
	for($n=1; $n>0;  $abs=preg_replace($re, '/', $abs, -1, $n)) {}
	/* absolute URL is  ready! */
	return  $scheme.'://'.$abs;
}
function GetUrl ($url) {
	$agent = "Mozilla/5.0 (compatible; Portiqo PHP XML Sitemap Generator/" . VERSION . ", https://dev.portiqo.com/)";
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_USERAGENT, $agent);
	curl_setopt ($ch, CURLOPT_VERBOSE, 1);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);

	$data = curl_exec($ch);

	curl_close($ch);

	return $data;
}
function Scan ($url) {
	global $start_url, $scanned, $pf, $extension, $skip, $freq, $priority;
	echo $url . NL;
	$url = filter_var ($url, FILTER_SANITIZE_URL);
	if (!filter_var ($url, FILTER_VALIDATE_URL) || in_array ($url, $scanned)) {
		return;
	}
	array_push ($scanned, $url);
	$html = str_get_html (GetUrl ($url));
	$a1   = $html->find('a');
	foreach ($a1 as $val) {
		$next_url = $val->href or "";
		$fragment_split = explode ("#", $next_url);
		$next_url       = $fragment_split[0];
		if ((substr ($next_url, 0, 7) != "http://")  && 
			(substr ($next_url, 0, 8) != "https://") &&
			(substr ($next_url, 0, 6) != "ftp://")   &&
			(substr ($next_url, 0, 7) != "mailto:"))
		{
			$next_url = @rel2abs ($next_url, $url);
		}
		$next_url = filter_var ($next_url, FILTER_SANITIZE_URL);
		if (substr ($next_url, 0, strlen ($start_url)) == $start_url) {
			$ignore = false;
			if (!filter_var ($next_url, FILTER_VALIDATE_URL)) {
				$ignore = true;
			}
			if (in_array ($next_url, $scanned)) {
				$ignore = true;
			}
			if (isset ($skip) && !$ignore) {
				foreach ($skip as $v) {
					if (substr ($next_url, 0, strlen ($v)) == $v)
					{
						$ignore = true;
					}
				}
			}
			if (!$ignore) {
				foreach ($extension as $ext) {
					if (strpos ($next_url, $ext) > 0) {
						$pr = number_format (round ( $priority / count ( explode( "/", trim ( str_ireplace ( array ("http://", "https://"), "", $next_url ), "/" ) ) ) + 0.5, 3 ), 1 );
						fwrite ($pf, "  <url>\n" .
									 "    <loc>" . htmlentities ($next_url) ."</loc>\n" .
									 "    <changefreq>$freq</changefreq>\n" .
									 "    <priority>$pr</priority>\n" .
									 "  </url>\n");
						Scan ($next_url);
					}
				}
			}
		}
	}
}
function XML2JSON($xml) {
    function normalizeSimpleXML($obj, &$result) {
        $data = $obj;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $res = null;
                normalizeSimpleXML($value, $res);
                if (($key == '@attributes') && ($key)) {
                    $result = $res;
                } else {
                    $result[$key] = $res;
                }
            }
        } else {
            $result = $data;
        }
    }
    normalizeSimpleXML(simplexml_load_string($xml), $result);
    return json_encode($result);
}
$pf = fopen ($file, "w");
if (!$pf) {
	echo "Cannot create $file!" . NL;
	return;
}
$start_url = filter_var ($start_url, FILTER_SANITIZE_URL);
/*"<?xml-stylesheet type=\"text/xsl\" href=\"https://www.portiqo.com/sitemap.xsl\"?>\n" .*/
/*"<!-- Created with Portiqo" . VERSION . " https://www.portiqo.com/ -->\n" .*/
fwrite ($pf, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			 "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n" .
			 "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			 "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
			 "        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n" .
			 "  <url>\n" .
			 "    <loc>" . htmlentities ($start_url) ."</loc>\n" .
			 "    <changefreq>$freq</changefreq>\n" .
			 "    <priority>$priority</priority>\n" .
			 "  </url>\n");
$scanned = array ();
$url = "https://dev.portiqo.com/blog/sitemap_index.xml";
$method = "GET";
// Scan ($start_url);
$myXMLData =CallAPI($method,$url);
$myXMLData = XML2JSON($myXMLData);
$myXMLData =  get_object_vars(json_decode($myXMLData));
foreach ($myXMLData['sitemap'] as $key => $value) {	
	fwrite($pf, "  <url>\n" .
				 "    <loc>" . ($value->loc) ."</loc>\n" .
				 "    <changefreq>$freq</changefreq>\n" .
				 "    <priority>$priority</priority>\n" .
				 "  </url>\n");
}
fwrite ($pf, "</urlset>\n");
fclose ($pf);
?>