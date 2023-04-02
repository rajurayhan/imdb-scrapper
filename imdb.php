<?php 
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 

$start_time = microtime(true);

// Read the JSON file into a string
$json_string = file_get_contents('imdb.json');

// Decode the JSON string into a PHP associative array
$movies = json_decode($json_string, true);  

// Loop through movies and scrape information
$scrappedData = [];
foreach ($movies as $key => $movie) {
    $movie['link'] = "https://www.imdb.com/title/".$movie["imdbID"]."/"; 
    echo ++$key.": ##################################". PHP_EOL;
    echo "URL: ". $movie['link'].PHP_EOL;
    // Scrape HTML from IMDb movie page
    $ch = curl_init($movie['link']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $html = curl_exec($ch); 
    curl_close($ch);

    // Parse HTML with DOMDocument
    $doc = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress errors
    $doc->loadHTML($html);
    libxml_clear_errors(); // Clear errors

    // Get movie name
    $title = $doc->getElementsByTagName('title')->item(0)->textContent;
    $title = str_replace(' - IMDb', '', $title);
    

    $xpath = new DOMXPath($doc);
    $directorNodes = $xpath->query("//ul[@class='ipc-inline-list ipc-inline-list--show-dividers ipc-inline-list--inline ipc-metadata-list-item__list-content baseAlt']"); 
    $directorNodes = $directorNodes[0]->childNodes;
    $directors = [];
    foreach ($directorNodes as $key => $directorNode) {
        $directors[] = $directorNode->textContent;  
    }

    // Get official website
    $siteParentNode = $xpath->query("//ul[@class='ipc-metadata-list ipc-metadata-list--dividers-all ipc-metadata-list--base']"); 
    if(sizeof($siteParentNode) > 2){
        $mainSiteNode = $siteParentNode[1];
    }
    else{
        $mainSiteNode = $siteParentNode[0];
    }
    $siteUrls = $mainSiteNode->childNodes[2]->getElementsByTagName('a');
    $urls = [];
    foreach ($siteUrls as $key => $url) {
        if (strpos($url->getAttribute('href'), 'externalsites?') == false){
            $urls[] = $url->getAttribute('href');
        } 
    }

    

    // Output movie information 
    echo "Movie: $title\n";
    echo "Directors:". implode(', ', $directors) . "\n";
    echo "Official website:". implode(', ', $urls) . "\n";
    $scrappedData[] = [
        "title" => $title, 
        "directors" => implode(', ', $directors), 
        "sites" => implode(', ', $urls)
    ];
}  

// Create a new spreadsheet object
$spreadsheet = new Spreadsheet();

// Set properties for the Excel file
$spreadsheet->getProperties()
    ->setCreator("Raju Rayhan")
    ->setLastModifiedBy("Raju Rayhan")
    ->setTitle("IMDb Excel")
    ->setSubject("Movie Info")
    ->setDescription("Movie Info");

// Set active sheet
$sheet = $spreadsheet->getActiveSheet();

// Set column headings
$columnHeadings = array('title', 'directors', 'sites');
$sheet->fromArray($columnHeadings, NULL, 'A1');

// Set data from array
$data = $scrappedData;

$row = 2;
foreach ($data as $rowData) {
    $sheet->fromArray($rowData, NULL, 'A' . $row);
    $row++;
}

// Set file format and filename
$writer = new Xlsx($spreadsheet);
$filename = 'imdb.xlsx';

// Save the file to disk
$writer->save($filename);

$end_time = microtime(true);
// Calculate the execution time
$execution_time = $end_time - $start_time;
echo "Execution time: " . number_format($execution_time, 2) . " seconds".PHP_EOL;
?>