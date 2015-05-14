<?php
function formatRawSize($bytes) {
    if(!empty($bytes)) {
        $s = array('bytes', 'kb', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes)/log(1024));
        $output = sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
        return $output;
    }
}
?>

<table valign="top" width="500" cellspacing="0" cellpadding="10" border="0" style="background-color:#ffffff;margin-top:10px;margin-bottom:10px;margin-right:auto;margin-left:auto;line-height:20px;font-family:arial;font-size:11px;vertical-align:top;">
		<tr>
			<td><span style="font-weight:bold;">ID</span></td>
			<td><span style="font-weight:bold;">Name</span></td>
			<td><span style="font-weight:bold;">Email</span></td>
			<td><span style="font-weight:bold;">Subdomain</span></td>
			<td><span style="font-weight:bold;">VideoStorage</span></td>
			<td><span style="font-weight:bold;">VersionsStorage</span></td>
			<td><span style="font-weight:bold;">AudioStorage</span></td>
			<td><span style="font-weight:bold;">TotalStorage</span></td>
		</tr>
		<?php 
		$separator = '</td><td>';
		$output = '';
		foreach($clients as $k => $client){
			$name = (!empty($client['Client']['name'])) ? $client['Client']['name'] : 'n/a';
			$email = (!empty($client['Client']['email'])) ? $client['Client']['email'] : 'n/a';
			$subdomain = (!empty($client['Client']['subdomain'])) ? $client['Client']['subdomain'] : 'n/a';
			$video = (!empty($client['Client']['video_storage'])) ? formatRawSize($client['Client']['video_storage']) : 0;
			$versions = (!empty($client['Client']['versions_storage'])) ? formatRawSize($client['Client']['versions_storage']) : 0;
			$audio = (!empty($client['Client']['audio_storage'])) ? formatRawSize($client['Client']['audio_storage']) : 0;
			$total = (!empty($client['Client']['total_storage'])) ? formatRawSize($client['Client']['total_storage']) : 0;

			$output .= 	'<tr><td>';
			$output .= 		$client['Client']['id'].$separator;
			$output .= 		$name.$separator;
			$output .= 		$email.$separator;
			$output .= 		$subdomain.$separator;
			$output .= 		$video.$separator;
			$output .= 		$versions.$separator;
			$output .= 		$audio.$separator;
			$output .= 		$total.'</td></tr>';
		} 
		echo $output;
		?>	
</table>