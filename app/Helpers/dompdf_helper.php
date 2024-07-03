<?php 

// function pdf_create($html, $filename = '', $stream = TRUE) 
// {
//     require_once(APPPATH . 'helpers/dompdf/autoload.inc.php');

// 	// need to enable magic quotes for the 
// 	$magic_quotes_enabled = get_magic_quotes_runtime();
//     if(!$magic_quotes_enabled)
//     {
//     	ini_set("magic_quotes_runtime", true);
//     }

//     $dompdf = new Dompdf\Dompdf();
//     $dompdf->loadHtml($html);
//     $dompdf->render();

//     ini_set("magic_quotes_runtime", $magic_quotes_enabled);

//     if ($stream)
// 	{
//         $dompdf->stream($filename.".pdf");
//     }
// 	else
// 	{
//         return $dompdf->output();
//     }
// }


use Dompdf\Dompdf;

if (!function_exists('pdf_create')) {
    function pdf_create($html, $filename = '', $stream = TRUE) 
    {
        // Ensure the autoloader is included
        require_once APPPATH . 'helpers/dompdf/autoload.inc.php';

        // Initialize Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        if ($stream) {
            $dompdf->stream($filename . ".pdf");
        } else {
            return $dompdf->output();
        }
    }
}
