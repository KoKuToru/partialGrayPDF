<?php

if ($_FILES["file"]["error"] != UPLOAD_ERR_OK)
{
	echo "We have a error. Try Again.<br>\n";
	echo "Echo: ".$_FILES["file"]["error"];
	exit();
}

$filename_upload   = $_FILES["file"]["tmp_name"];
$filename_original = $_FILES["file"]["name"];
$filename_converted  = dirname($filename_upload)."/gray_".$filename_original;
$filename_new        = dirname($filename_upload)."/partialgray_".$filename_original;

//convert to gray:
unset($output);
$res = 0;
$command = "gs -o \"".escapeshellcmd($filename_converted)."\" -sDEVICE=pdfwrite -sProcessColorModel=DeviceGray -sColorConversionStrategy=Gray -sColorConversionStrategyForImages=Gray \"".escapeshellcmd($filename_upload)."\"";
exec($command, $output, $res);
if ($res != 0)
{
	echo "We couldn't convert the PDF to Gray<br>\n";
	echo $command."<br>\n";
	foreach($output as $line)
	{
		echo $line."<br>\n";
	}
	echo "Return value: ".$res."<br>\n";
	exit();
}

//clean up user input
$color_pages = explode(",", $_POST["pages"]);
$color_pages_clean = array();
foreach($color_pages as $page)
{
	$color_pages_clean[] = str_replace(" ", "", $page);
}
unset($color_pages);

//fill missing pages
$merge_pages = array();
$next_page = 1;
foreach($color_pages_clean as $page)
{
	$min_max = explode("-", $page);
	
	//convert to integers
	foreach($min_max as &$num)
	{
		$num = (int)$num;
	}

	if ($min_max[0] != $next_page)
	{
		$merge_pages[] = "B".$next_page."-".($min_max[0]-1);
	}


	if (count($min_max) > 1)
	{
		//min max mode
		$merge_pages[] = "A".$min_max[0]."-".$min_max[1];
		$next_page = $min_max[1]+1;
	}
	else
	{
		//single page
		$merge_pages[] = "A".$min_max[0];
                $next_page = $min_max[0]+1;
	}
}
unset($color_pages_clean);

$merge_pages[] = "B".$next_page."-end";

//B = gray, A = color
$command = "pdftk A=\"".escapeshellcmd($filename_upload)."\" B=\"".escapeshellcmd($filename_converted)."\" cat ";
foreach($merge_pages as $pages)
{
	$command = $command.$pages." ";
}
$command = $command." output \"".escapeshellcmd($filename_new)."\"";

//run comamnd
unset($output);
$res = 0;
exec($command, $output, $res);
if ($res != 0)
{
        echo "We couldn't partial convert the PDF to Gray<br>\n";
        echo $command."<br>\n";
        foreach($output as $line)
        {
                echo $line."<br>\n";
        }
        echo "Return value: ".$res."<br>\n";
        exit();
}

//send:
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="partialgray_'.$filename_original.'"');
header('Content-Transfer-Encoding: binary');
readfile($filename_new);

//remove files:
unlink($filename_upload);
unlink($filename_converted);
unlink($filename_new);
?>
