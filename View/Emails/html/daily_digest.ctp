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
                                <td align="right" color="#5A5D64" style="font-family:arial;font-size:16px;font-weight:bold;text-align:right;text-shadow:1px 1px 1px #f5f5f5;color:#5A5D64;">Analytics Report</td>
                            </tr>
                            <tr>
                                <td align="right" color="#5A5D64" style="font-family:arial;font-size:12px;font-weight:bold;text-align:right;text-shadow:1px 1px 1px #f5f5f5;color:#5A5D64;"><?php echo $date;?></td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>

            <table valign="top" width="100%" border="0" align="left" cellpadding="3" cellspacing="0" class="analytics-report" style="width:100%;color:#5A5D64;font-family:arial;text-align:left;font-size:12px;vertical-align:top;border:1px solid #CCC;-moz-box-shadow:0px 5px 20px #bbb;-webkit-box-shadow:0px 5px 20px #bbb;box-shadow:0px 5px 20px #bbb;">
                <thead>
                <tr>
                    <th bgcolor="#666666" color="#FFFFFF" valign="top" width="30%" style="text-shadow: 0px 1px 3px #222;color:#ffffff;padding-left: 10px;background:url('http://www.fordela.com/sites/default/files/email/treven.png') repeat-x scroll center top #666666;color:#ffffff;padding-left: 10px;border-top: 1px solid #555;border-bottom: 1px solid #111;border-left: 1px solid #555;">Name / Company</th>
                    <th bgcolor="#666666" color="#FFFFFF" valign="top" width="41%" style="text-shadow: 0px 1px 3px #222;color:#ffffff;padding-left: 10px;background:url('http://www.fordela.com/sites/default/files/email/treven.png') repeat-x scroll center top #666666;color:#ffffff;padding-left: 10px;border-top: 1px solid #555;border-bottom: 1px solid #111;">Title</th>
                    <th bgcolor="#666666" color="#FFFFFF" valign="top" width="29%" style="text-shadow: 0px 1px 3px #222;color:#ffffff;padding-left: 10px;background:url('http://www.fordela.com/sites/default/files/email/treven.png') repeat-x scroll center top #666666;color:#ffffff;padding-left: 10px;border-top: 1px solid #555;border-bottom: 1px solid #111;border-right: 1px solid #555;">Completion</th>
                </tr>
                </thead>
                <tbody>
                <?php if(!empty($users)):?>
                    <?php $i = 0;?>
                    <?php foreach($users as $user): ?>
                        <?php
                        if(!empty($user['videos'])){
                            // Do nothing
                        } else {
                            continue; // skip this row if no videos
                        }

                        if( $odd = $i%2 ){
                            $rowColor = 'EEEEEE';
                            $border = 'border-top:1px solid #eee;border-bottom:1px solid #DDD;';
                        }else{
                            $rowColor = 'FAFAFA';
                            $border = 'border-bottom:1px solid #DDD;border-top:1px solid #FDFDFD;';
                        }
                        ?>
                        <tr>

                            <td valign="top" bgcolor="#<?php echo $rowColor;?>" style="padding-left:10px;font-size:12px;background:url('http://www.fordela.com/sites/default/files/email/treven.png') repeat-x scroll center top #<?php echo $rowColor;?>;<?php echo $border;?>border-left:1px solid #CCC;">

                                <?php
                                echo $user['name'];
                                if(!empty($user['company'])) {
                                    echo '<br>'.$user['company'];
                                }
                                // Can we add country here?
                                ?>

                            </td>

                            <td valign="top" colspan="2" bgcolor="#<?php echo $rowColor;?>" style="padding-right:10px;background:url('http://www.fordela.com/sites/default/files/email/treven.png') repeat-x scroll center top #<?php echo $rowColor;?>;<?php echo $border;?>border-right:1px solid #CCC;">
                                <table bgcolor="transparent" valign="top" width="100%" border="0" align="left" cellpadding="0" cellspacing="3" style="border-collapse:collapse;">
                                    <tbody>
                                    <?php if(!empty($user['videos'])):?>
                                        <?php foreach($user['videos'] as $video):?>
                                            <?php if($video['playthrough'] > 1):?>
                                                <tr>
                                                    <td bgcolor="transparent" border="0" width="56%" valign="top" style="font-size:12px;font-family:arial;">
                                                        <?php
                                                        echo $video['title'];
                                                        if(!empty($video['country'])) {
                                                            echo '<br>'.$video['country'];
                                                        }
                                                        ?>
                                                    </td>
                                                    <?php $notwatched = 100 - $video['playthrough'];?>
                                                    <td bgcolor="transparent" border="0" width="44%" valign="top">
                                                        <table bgcolor="transparent" valign="top" width="100%" border="0" align="left" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                                            <tbody>
                                                            <tr>
                                                                <td bgcolor="transparent" border="0" width="33%" align="right" size="10" style="font-size:10px;text-align:right;padding-right:5px;"><span style="font-family:arial;font-size:10px;text-align:right;color:#5A5D64;"><?php echo $video['playthrough'];?>%</span></td>
                                                                <td bgcolor="transparent" border="0" width="67%">
                                                                    <div style="height:9px;max-height:9px;">
                                                                        <table bgcolor="transparent" valign="top" width="100%" border="0" align="left" cellpadding="0" cellspacing="0" style="border-collapse:collapse;height:9px;max-height:9px;font-size:9px;">
                                                                            <tbody>
                                                                            <tr>
                                                                                <td border="0" bgcolor="#A10005" width="<?php echo $video['playthrough'];?>%" style="line-height:9px;height:9px;max-height:9px;font-size:9px;border-radius:2px;box-shadow:inset 0px 0px 1px #810005,inset 0px -2px 8px #610005;">&nbsp;</td>
                                                                                <td bgcolor="transparent" border="0" width="<?php echo $notwatched;?>%" style="line-height:9px;height:9px;max-height:9px;font-size:9px;">&nbsp;</td>
                                                                            </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            <?php endif;?>
                                        <?php endforeach;?>
                                    <?php else:?>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    </tbody>
                                    <?php endif;?>
                                </table>
                            </td>
                        </tr>
                        <?php $i++;endforeach;?>
                <?php else: ?>
                    <tr>
                        <td bgcolor="#FAFAFA" colspan="3" style="font-size:9px;background:url('http://www.fordela.com/sites/default/files/email/treven.png') repeat-x scroll center top #FAFAFA;">There has been no activity on your account today</td>
                    </tr>
                <?php endif;?>
                </tbody>
            </table>
            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                <tbody>
                <tr>
                    <td><img src="http://www.fordela.com/sites/default/files/email/shadowcurl500.png" /></td>
                </tr>
                </tbody>
            </table>
            <table width="100%" cellspacing="0" cellpadding="0" border="0" size="12" color="#5A5D64" style="font-size:12px;font-family:arial;font-weight:bold;color:#5A5D64;vertical-align:top;width:100%;">
                <tbody>
                <tr>
                    <td size="12" color="#5A5D64" style="font-size:12px;font-family: arial;font-weight:bold;color:#5A5D64;vertical-align:top;"><a style="font-size:12px;font-family:arial;font-weight:bold;color:#5A5D64;vertical-align:top;" href="<?php echo $vmsUrl;?>/analytics">Login</a> to see more analytics</td>
                    <?php if(isset($templateVars['white_label']) && $templateVars['white_label']):?>
                    <?php else:?>
                        <td align="right"><a href="http://www.fordela.com"><img border="0" width="100" src="http://www.fordela.com/sites/fordela.com/files/fordela_logo.png" /></a></td>
                    <?php endif;?>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
