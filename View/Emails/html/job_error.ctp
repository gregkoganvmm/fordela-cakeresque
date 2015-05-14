						<th style="text-align: right; text-shadow: 1px 1px 1px #fff;">A Job Has Failed on the Job Queue</th>
					</tr>
				</thead>
			</table>
		</th>
	</tr>
</thead>

<tbody>
	<tr>
		<td style="vertical-align:top;">
			<table style=" width: 100%; " cellspacing="0" cellpadding="0" border="0">
				<tbody>
					<tr>
						<td style="vertical-align:top;">
							<table style="padding:20px 20px; width: 100%; table-layout: fixed; color:#666;" cellspacing="0" cellpadding="0" border="0">
								<tbody>
									<tr>
										Job <?php echo $job['JobQueue']['id']?> has failed to finish <?php echo $job['JobQueue']['retry']?> times.
									</tr>
									<tr>
										<a style="text-decoration: none;text-shadow: 0px 1px 4px #eeeeee;color:#000" href="http://jobqueue.fordela.com/job_queues/index/show:unfinished">Unfinished Jobs</a>
									</tr>
									<tr>
										Shell: <?php echo $job['JobQueue']['type']?><br>
										Function: <?php echo $job['JobQueue']['function']?>
									</tr>
									<tr>
										Params<br>
										<?php
										$paramsArr = unserialize($job['JobQueue']['params']);
										$params = '<ul>';
										foreach($paramsArr as $param) {
											$params .= '<li>'.$param.'</li>';
										}
										$params .= '</ul>';
										echo $params;
										?>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
</tbody>
