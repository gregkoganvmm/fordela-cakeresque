<?php
if(empty($templateVars['clientlogo'])){ //if has logo
    $headerTitle = 'Fordela';
    if(!empty($templateVars['clientname'])){ //if client name
        $headerTitle = $templateVars['clientname']; //client name
    }
    $logo = '<span style="font-size:24px;">'.$headerTitle.'</span>';
}
else{
    $client_logo = 'https://d6m0wigsq4v0h.cloudfront.net/clients/'.$templateVars['clientid'].'/'.$templateVars['clientlogo']; //logo img url
    $logo = '<img border="0" style="border: medium none;max-height: 50px;" src="'.$client_logo.'" />';
}

$vmsUrl = $templateVars['vmsUrl'];
$subdomain = $templateVars['subdomain'];
$username = $templateVars['username'];
$managerUserId = $templateVars['managerUserId'];
?>

<table valign="top" width="500" cellspacing="0" cellpadding="10" border="0" style="max-width:500px;min-width:500px;margin-top:10px;margin-bottom:10px;margin-right:auto;margin-left:auto;line-height:20px;font-family:arial;font-size:12px;color:#5A5D64;vertical-align:top;">
    <tbody>
        <tr>
            <td>
                <table width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;padding: 0px 0px 10px;border-collapse:collapse;">
                    <tbody>
                        <tr>
                            <td align="left" width="25%" size="24" color="#5A5D64" style="font-family:arial;text-align:left;font-size:24px;color:#5a5d64;"><a style="font-family:arial;text-align:left;font-size:24px;color:#5a5d64;" href="<?php echo $vmsUrl?>"><?php echo $logo?></a></td>
                            <td align="right" width="75%" color="#5A5D64" style="font-family:arial;text-align:right;text-shadow:1px 1px 1px #f5f5f5;color:#5A5D64;">
                                <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                        <tr>
                                            <td align="right" color="#5A5D64" style="font-family:arial;font-size:16px;font-weight:bold;text-align:right;text-shadow:1px 1px 1px #f5f5f5;color:#5A5D64;"><a style="font-size:16px;font-family:arial;font-weight:bold;color:#5A5D64;vertical-align:top;" href="mailto:<?php echo $username;?>"><?php echo $username;?></a> has logged in</td>
                                        </tr>
                                        <tr>
                                            <td align="right" color="#5A5D64" style="font-family:arial;font-size:12px;font-weight:bold;text-align:right;text-shadow:1px 1px 1px #f5f5f5;color:#5A5D64;"><?php echo $date;?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center" color="#5A5D64" style="font-family:arial;font-size:12px;text-align:center;text-shadow:1px 1px 1px #f5f5f5;color:#5A5D64;">Want to disable these notifications?<br> <a style="font-size:12px;font-family:arial;color:#5A5D64;vertical-align:top;" href="<?php echo $vmsUrl.'/account/users/edit/'.$managerUserId?>">Login and update your preferences</a></td>
                        </tr>
                    </tbody>
                </table>

            </td>
        </tr>
    </tbody>
</table>
