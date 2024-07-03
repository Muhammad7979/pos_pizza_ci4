<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo lang('items_lang.items_generate_barcodes'); ?></title>
	<link rel="stylesheet" rev="stylesheet" href="<?php echo base_url();?>dist/barcode_font.css" />
</head>

<body class=<?php echo "font_".$barcodeLib->get_font_name($barcodeConfig['barcode_font']); ?> 
      style="font-size:<?php echo $barcodeConfig['barcode_font_size']; ?>px">
	<table cellspacing=<?php echo $barcodeConfig['barcode_page_cellspacing']; ?> width='<?php echo $barcodeConfig['barcode_page_width']."%"; ?>' >
		<tr>
			<?php
			$count = 0;
			foreach($items as $item)
			{
				if ($count % $barcodeConfig['barcode_num_in_row'] == 0 and $count != 0)
				{
					echo '</tr><tr>';
				}
				echo '<td>' . $barcodeLib->display_barcode($item, $barcodeConfig) . '</td>';
				++$count;
			}
			?>
		</tr>
	</table>
</body>

</html>
