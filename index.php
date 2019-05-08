<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Inspire305 Tools</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<style type="text/css">
		body {
			padding: 60px 0;
		}
		.container {
			max-width: 720px;
		}
	</style>
</head>
<body>
<?php
	$logs = array();
	function push_log($type, $message) {
		global $logs;
		$log = array(
			'type'			=> $type,
			'message'		=> $message,
			'datetime'	=> date('Y-m-d H:s:i P'),
		);
		array_push($logs, $log);
	}
	push_log('info', 'Checking for tmp directory.');
	if (!is_dir('tmp/')) {
		push_log('warning', 'Directory: tmp not found! Creating directory.');
		mkdir('tmp');
	} else {
		push_log('success', 'Directory: tmp exists.');
	}
	push_log('info', 'Checking for output directory.');
	if (!is_dir('output/')) {
		push_log('warning', 'Directory: output not found! Creating directory.');
		mkdir('output');
	} else {
		push_log('success', 'Directory: output exists.');
	}
	$timestamp = md5(time());
	$forbidden_emails = array(
		'roarmedia.com',
	);
	$output_path = 'output/';
	push_log('info', 'Checking for input data...');
	if (isset($_POST) && 'submit' == $_POST['submit']) {
		$zipDocs = array();
		$zipFilename = 'inspire305-results_' . $timestamp;
		$zipPath = $zipFilename . '/';
		$votesFilename = 'inspire305-votes_' . $timestamp;
		$mktFilename = 'inspire305-email-signups_' . $timestamp;
		$acceptedFilename = 'inspire305-accepted-votes_' . $timestamp;
		$rejectedFilename = 'inspire305-rejected-votes_' . $timestamp;
		push_log('info', 'Checking for value delmeter.');
		if (!isset($_POST['delimeter']) || empty($_POST['delimeter'])) {
			push_log('danger', 'The value delimeter field has no value or cannot be read.');
		} else {
			$delimeter = $_POST['delimeter'];
			push_log('success', 'The value delimeter has been found.');
		}
		push_log('info', 'Checking for start date.');
		if (isset($_POST['from_date'])) {
			push_log('success', 'The start date has been found.');
			$fdate = strtotime($_POST['from_date']);
		} else {
			push_log('danger', 'The start date field has no value or cannot be read.');
		}
		push_log('info', 'Checking for through date.');
		if (isset($_POST['thru_date'])) {
			push_log('success', 'The through date has been found.');
			$tdate = strtotime($_POST['thru_date']);
		} else {
			push_log('danger', 'The through date field has no value or cannot be read.');	
		}
		push_log('info', 'Checking for existing output/'.$zipFilename.' directory.');
		if (!is_dir($zipPath)) {
			push_log('info', 'Directory: ' . $zipFilename.' does not exist. Creating...');
			mkdir($zipFilename);
		} else {
			push_log('warning', 'Directory: '.$zipFilename.' already exists. Purging existing contents.');
			$trashZip = glob($zipFilename, '/*.*');
			foreach ($trashZip as $trash) {
				push_log('warning', 'Removing '.$trash.'.');
				unlink($trash);
			}
			push_log('info', 'Directory: '.$zipFilename.' contents deleted.');
		}
		push_log('success', 'Directory: '.$zipFilename.' directory ready.');
		if (!isset($FILES)) {
			$fileTmpPath = $_FILES['upload']['tmp_name'];
			$fileName = $_FILES['upload']['name'];
			$fileType = $_FILES['upload']['type'];
			$fileNameCmps = explode('.', $fileName);
			$fileExtension = strtolower(end($fileNameCmps));
			$newFileName = md5(time() . $fileName) . '.' . $fileExtension;
			$uploadFileDir = './tmp/';
			$dest_path = $uploadFileDir . $newFileName;
			if (move_uploaded_file($fileTmpPath, $dest_path)) {
				push_log('success', '<strong>'.$fileName.'</strong> uploaded successfully.');
				$rawCSV = 'tmp/' . $newFileName;
				$csv_array = array();
				if (($h = fopen("{$rawCSV}", "r")) !== false) {
					push_log('info', 'Reading <strong>'.$fileName.'</strong>.');
					while (($data = fgetcsv($h, 1000, $delimeter)) !== false) {
						$csv_array[] = $data;
					}
					fclose($h);
					push_log('success', '<strong>'.$fileName.'</strong> successfully read.');
				} else {
					push_log('danger', 'Unable to read <strong>'.$fileName.'</strong>');
				}
				$csv_array_headers = array_shift($csv_array);
				push_log('info', 'Capturing headers from <strong>'.$fileName.'</strong>.');
				for ($i = 0; $i < count($csv_array_headers); $i++) {
					$csv_array_headers[$i] = strtolower(str_replace(array('"', ' (required)'), '', preg_replace('/[\x00-\x1F\x7F]/', '', $csv_array_headers[$i])));
					push_log('info', 'Header read: '.$csv_array_headers[$i].'.');
				}
				push_log('success', 'Headers successfully captured from CSV');
			} else {
				push_log('danger', 'Unable to successfully upload <strong>'.$fileName.'</strong>.');
			}
		}
		if (isset($fdate) && isset($tdate) && isset($delimeter) && is_array($csv_array) && !empty($csv_array) && is_array($csv_array_headers) && !empty($csv_array_headers)) {
			$cdate = null;
			$status = null;
			$time = null;
			$ip = null;
			$choices = null;
			$email = null;
			for ($i = 0; $i < count($csv_array_headers); $i++) {
				push_log('info', 'Looping through headers to find columns.');
				$pos = $csv_array_headers[$i];
				if ($pos == 'status') {
					$status = $i;
					push_log('info', 'Status column found at '.$i.'.');
				}
				if ($pos == 'time') {
					$time = $i;
					push_log('info', 'Time column found at '.$i.'.');
				}
				if ($pos == 'ip') {
					$ip = $i;
					push_log('info', 'IP column found at '.$i.'.');
				}
				if ($pos == 'choices') {
					$choices = $i;
					push_log('info', 'Choices column found at '.$i.'.');
				}
				if ($pos == 'first name') {
					$fname = $i;
					push_log('info', 'First name column found at '.$i.'.');
				}
				if ($pos == 'last name') {
					$lname = $i;
					push_log('info', 'Last name column found at '.$i.'.');
				}
				if ($pos == 'birth year') {
					$birth = $i;
					push_log('info', 'Birth year column found at '.$i.'.');
				}
				if ($pos == 'email address') {
					$email = $i;
					push_log('info', 'Email Address column found at '.$i.'.');
				}
				if ($pos == 'mkt_optin') {
					$mkt = $i;
					push_log('info', 'Marketing Optin column found at '.$i.'.');
				}
			}
			push_log('info', 'Finished looping through headers.');
			$votes = array();
			$total_votes = 0;
			$votes_rejected = array();
			$votes_accepted = array();
			$marketing_list = array();
			$marketing_list_dups = array();
			if (null !== $status && null !== $time && null !== $ip && null !== $choices && null !== $email && null !== $mkt) {
				push_log('success', 'All necessary headers and columns located.');
				push_log('info', 'Looping through '.$fileName.'.');
				for ($i = 0; $i < count($csv_array); $i++) {
					$duplicates = array(
						'ips'			=> array(),
						'emails'	=> array()
					);
					$row = $csv_array[$i];
					push_log('info', 'Cleaning document values.');
					for ($j = 0; $j < count($row); $j++) {
						$val = strtolower(str_replace('"', '', preg_replace('/[\x00-\x1F\x7F]/', '', $row[$j])));
						$row[$j] = $val;
					}
					if ('array' == $row[$mkt] && !in_array($row[$email], $marketing_list_dups) && strpos($row[$email], '@')) {
						array_push($marketing_list_dups, $row[$email]);
						$mkt_email = array(
							ucwords($row[$fname]),
							ucwords($row[$lname]),
							$row[$email],
							$row[$birth],
						);
						array_push($marketing_list, $mkt_email);
					}
					push_log('info', 'Cleaned '.$j.' values in row '.$i.'.');
					if ('accepted' == $row[$status]) {
						push_log('info', 'Row accepted.');
						$vote_date_raw = explode(' ', $row[$time]);
						$vote_date = strtotime($vote_date_raw[0]);
						push_log('info', 'Checking vote dates.');
						if ($vote_date >= $fdate && $vote_date <= $tdate) {
							if (null == $cdate || $vote_date < $cdate) {
								push_log('info', 'Row '.$i.' has next day after previous row. Resetting duplicates.');
								$cdate = $vote_date;
								$duplicates['ips'] = array();
								$duplicates['emails'] = array();
							} else {
								push_log('info', 'Row '.$i.' has same date as previous row. Checking for duplicate vote.');
							}
							if (!in_array($row[$ip], $duplicates['ips']) || !in_array($row[$email], $duplicates['emails'])) {
								push_log('info', 'Vote in row '.$i.' is valid. Checking for forbidden email.');
								$check_email = explode('@', $row[$email]);
								$check_email = end($check_email);
								if (!in_array($check_email, $forbidden_emails)) {
									push_log('info', 'Email in row '.$i.' is valid. Logging choice.');
									push_log('info', 'Checking if choice exists...');
									$vote = $row[$choices];
									if (!isset($votes[$vote]) && !empty($vote)) {
										push_log('info', 'Choice: '.$vote. ' is not set. Setting and first logging vote.');
										$votes[$vote] = 1;
									} else {
										push_log('info', 'Choice: '.$vote.' exists. Logging vote.');
										$votes[$vote]++;
									}
									push_log('info', 'Vote in row '.$i.' is accepted. Normalizing row...');
									for ($j = 0; $j < count($row); $j++) {
										$val = ucwords($row[$j]);
										$row[$j] = $val;
									}
									push_log('info', 'Moving row '.$i.' to accepted entries.');
									array_push($votes_accepted, $row);
								} else {
									push_log('info', 'Email in row '.$i.' is forbidden. Normalizing row...');
									for ($j = 0; $j < count($row); $j++) {
									$val = ucwords($row[$j]);
										$row[$j] = $val;
									}
									$row[$status] = 'Forbidden';
									push_log('info', 'Moving row '.$i.' to rejected entries.');
									array_push($votes_rejected, $row);
								}
							} else {
								push_log('info', 'Vote in row '.$i.' is a duplicate. Normalizing row...');
								for ($j = 0; $j < count($row); $j++) {
									$val = ucwords($row[$j]);
									$row[$j] = $val;
								}
								$row[$status] = 'Duplicate';
								push_log('info', 'Moving row '.$i.' to rejected entries.');
								array_push($votes_rejected, $row);
							}
						}
					} else {
						push_log('info', 'Row rejected. Normalizing...');
						for ($j = 0; $j < count($row); $j++) {
							$val = ucwords($row[$j]);
							$row[$j] = $val;
						}
						push_log('info', 'Normalized '.$j.' values in row '.$i.'.');
						push_log('info', 'Moving row '.$i.' to rejected entries.');
						array_push($votes_rejected, $row);
					}
				}
				push_log('success', 'Looped through '.$i.' rows in '.$fileName.'.');
				$output_files = array();
				push_log('info', 'Compiling voting results.');
				arsort($votes);
				$votes_array = array();
				foreach ($votes as $choice => $vote) {
					$total_votes+= $vote;
					$votes_array_row = array(
						strtoupper($choice),
						$vote,
					);
					array_push($votes_array, $votes_array_row);
				}
				push_log('info', 'Preparing to generate files.');
				$votes_file = array(
					'filename'	=> $votesFilename,
					'data'			=> $votes_array,
				);
				array_unshift($votes_file['data'], array('Choices', 'Votes'));
				array_push($votes_file['data'], array('Total Votes', $total_votes));
				push_log('info', 'Preparing '.$votesFilename.'.csv.');
				array_push($output_files, $votes_file);
				push_log('info', 'Getting data for additional requested files.');
				if ('on' == $_POST['get_mkt']) {
					$output_file = array(
						'filename'	=> $mktFilename,
						'data'			=> $marketing_list,
					);
					$output_headers = array(
						'First Name',
						'Last Name',
						'Email Address',
						'Birth Year',
					);
					array_unshift($output_file['data'], $output_headers);
					push_log('info', 'Preparing '.$mktFilename.'.csv.');
					array_push($output_files, $output_file);
				}
				$results_headers = array();
				for ($i = 0; $i < count($csv_array_headers); $i++) {
					$val = ucwords($csv_array_headers[$i]);
					$results_headers[$i] = $val;
				}
				if ('on' == $_POST['get_valid']) {
					$output_file = array(
						'filename'	=> $acceptedFilename,
						'data'			=> $votes_accepted
					);
					array_unshift($output_file['data'], $results_headers);
					push_log('info', 'Preparing '.$acceptedFilename.'.csv.');
					array_push($output_files, $output_file);
				}
				if ('on' == $_POST['get_rejected']) {
					$output_file = array(
						'filename'	=> $rejectedFilename,
						'data'			=> $votes_rejected
					);
					array_unshift($output_file['data'], $results_headers);
					push_log('info', 'Preparing '.$rejectedFilename.'.csv.');
					array_push($output_files, $output_file);
				}
				push_log('info', 'Files prepared. Generating csv(s)...');
				foreach ($output_files as $output_file) {
					if (!empty($output_file['filename']) && is_array($output_file['data']) && !empty($output_file['data'])) {
						push_log('info', 'Generating '.$output_file['filename'].'.csv');
						$file = $zipPath . $output_file['filename'] . '.csv';
						$data = $output_file['data'];
						$fp = fopen($file, 'wb');
						foreach ($data as $row) {
							fputcsv($fp, $row);
						}
						fclose($fp);
						if (!$fp) {
							push_log('danger', 'Could not create . '.$output_file['filename'].'.csv');
						} else {
							push_log('success', 'File: '.$output_file['filename'].'.csv created successfully.');
							array_push($zipDocs, $file);
						}
					} else {
						if (empty($output_file['filename'])) {
							push_log('warning', 'An output file is missing a filename. Skipping file.');
						} elseif (!is_array($output_file['data'])) {
							push_log('warning', 'There is something wrong with the data for '.$output_file['filename'].'. Skipping file.');
						} elseif (empty($output_file['data'])) {
							push_log('warning', 'There is no data for '.$output_file['filename'].'. Skipping file.');
						}
					}
					if (is_array($zipDocs) && !empty($zipDocs)) {
						push_log('info', 'Preparing to add files to archive: '.$zipFilename.'.zip');
						$zip = new ZipArchive();
						$zip->open('output/'.$zipFilename.'.zip', ZipArchive::CREATE);
						foreach ($zipDocs as $file) {
							push_log('info', 'Adding '.$file.' to archive...');
							$zip->addFile($file, $file);
						}
						$zip->close();
						if (!$zip) {
							$debug = true;
							push_log('warning', 'Unable to create archive: '.$zipFilename.'.zip.');
						} else {
							push_log('success', 'Archive: '.$zipFilename.'.zip successfully created.');
						}
					}			
				}
				if (is_file('output/'.$zipFilename.'.zip')) {
					push_log('info', 'Cleaning up by deleting temporarry files...');
					unlink($dest_path);
					if (!file_exists($dest_path)) {
						push_log('success', 'File: '.$dest_path.' successfully deleted');
					} else {
						push_log('danger', 'Unable to remove '.$dest_path.'.');
					}
					$zipRemoves = glob($zipFilename.'/*');
					foreach ($zipRemoves as $file) {
						unlink($file);
						if (!is_file($file)) {
							push_log('success', 'File: '.$file.' successfully deleted');
						} else {
							push_log('warning', 'Unable to remove '.$file.'.');
						}
					}
					rmdir($zipFilename);
					if (!is_dir($zipFilename)) {
						push_log('success', 'Temporarry archive successfully deleted');
					} else {
						push_log('danger', 'Unable to remove temporarry archive.');
					}
					push_log('success', 'Operation complete.');
					$files_ready = true;
				} else {
					$debug = true;
					push_log('danger', $zipFilename.'.zip cannot be found.');
				}
			} else {
				$debug = true;
				push_log('danger', 'Unable to proceed with processing due to an error. Please review the logs for more details.');
			}
		} else {
			$debug = true;
			push_log('danger', 'Unable to proceed with processing due to an error. Please review the logs for more details.');
		}
	} else {
		push_log('info', 'No input data found.');
	}
?>
	<header class="container border-bottom">
		<h1 class="display-4">
			Inspire305 Poll Tools
		</h1>
		<p class="lead">
			Use the form below to generate your data.
		</p>
		<?php
			if (true === $debug) {
				$alert = end($logs);
				echo '<div class="alert alert-danger">';
				echo $alert['message'] . ' <a class="alert-link" data-toggle="modal" data-target="#programLog" href="#programLog">Click here to view the program log  for more details</a>.';
				echo '</div>';
			}
		?>
	</header>
	<?php if (is_array($votes) && !empty($votes)): ?>
	<section id="results" class="container">
		<h3 class="mt-4 mb-2">Poll Results</h3>
		<table class="table mt-2 mb-4">
			<caption>Poll Results as of <?php echo date('h:i:sa l, F jS Y'); ?>.</caption>
			<thead class="thead-light">
				<tr>
					<th scope="col">Choices</th>
					<th scope="col">Votes</th>
				</tr>
			</thead>
			<tbody>
				<?php
					$winner = 0;
					foreach ($votes as $choice => $votes) {
						if (0 == $winner) {
							$row_mod = ' class="table-success"';
							$row_badge = ' <span class="badge badge-success">Grand Prize</span>';
						} elseif (1 == $winner) {
							$row_mod = ' class="table-success"';
							$row_badge = ' <span class="badge badge-success">Runner-Up</span>';
						} else {
							$row_mod = '';
							$row_badge = '';
						}
						echo '<tr'.$row_mod.'><th scope="row">'.strtoupper($choice).$row_badge.'</td><td>'.$votes.'</td></tr>';
						$winner++;
					}
					echo '<tr class="table-secondary"><th scope="row">Total Votes</th><td>'.$total_votes.'</td></tr>';
				?>
			</tbody>
		</table>
		<?php 
			if (true === $files_ready) {
				echo '<a role="button" class="btn btn-success btn-block" href="output/'.$zipFilename.'.zip" title="Download '.$zipFilename.'.zip">Download Your Files (.zip)</a>';
			}
		?>
		<hr>
	</section>
	<?php endif; ?>
	<section id="uploadForm" class="container">
		<h3 class="mt-4 mb-2">
			Polling Tools Form
		</h3>
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" class="mt-2 mb-4" enctype="multipart/form-data">
			<fieldset>
				<legend>
					<small class="badge badge-secondary">Step 1:</small><br>
					Upload your CSV document and set your compiling options.
					</legend>
				<div class="form-row">
					<div class="form-group col-12">
						<label for="upload">
							File:
							<span class="text-danger">*</span>
						</label>
						<input class="form-control-file" type="file" name="upload" required>
					</div>
					<div class="form-group col-sm-2">
						<label for="delimeter">
							Delimeter
							<span class="text-danger">*</span>
						</label>
						<input class="form-control" type="text" name="delimeter" value="" maxlength="1" required>
					</div>
					<div class="form-group col-sm-5">
						<label for="from_date">
							Start date:
							<span class="text-danger">*</span>
						</label>
						<input class="form-control" type="date" name="from_date" required>
					</div>
					<div class="form-group col-sm-5">
						<label for="thru_date">
							Through date:
							<span class="text-danger">*</span>
						</label>
						<input class="form-control" type="date" name="thru_date" required>
					</div>
				</div>
			</fieldset>
			<fieldset>
				<legend>
					<small class="badge badge-secondary">Step 2:</small><br>
					Choose your output options. Choose <strong>at least one</strong>.
				</legend>
				<div class="form-row">
					<div class="form-group col-6 col-md-3">
						<div class="custom-control custom-switch">
							<input class="custom-control-input" type="checkbox" id="get_mkt" name="get_mkt">
							<label class="custom-control-label" for="get_mkt">
								Email Signups
							</label>
						</div>
					</div>
					<div class="form-group col-6 col-md-3">
						<div class="custom-control custom-switch">
							<input class="custom-control-input" type="checkbox" id="get_valid" name="get_valid">
							<label class="custom-control-label" for="get_valid">
								Poll Results
							</label>
						</div>
					</div>
					<div class="form-group col-6 col-md-3">
						<div class="custom-control custom-switch">
							<input class="custom-control-input" type="checkbox" id="get_rejected" name="get_rejected">
							<label class="custom-control-label" for="get_rejected">
								Rejected Entries
							</label>
						</div>
					</div>
					<div class="form-group col-6 col-md-3">
						<div class="custom-control custom-switch">
							<input class="custom-control-input" type="checkbox" id="get_all">
							<label class="custom-control-label" for="get_all">
								Select All/None
							</label>
						</div>
					</div>
				</div>
			</fieldset>
			<div class="form-group">
				<button class="btn btn-primary btn-block mt-2 mb-2" name="submit" type="submit" value="submit" disabled>Submit</button>
			</div>
		</form>
		<p class="text-right text-muted">
			<a data-toggle="modal" data-target="#programLog" href="#programLog">Program Log</a>
		</p>
	</section>

	<div class="modal fade" id="programLog" tabindex="-1" role="dialog" aria-labelledby="programLog" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<section class="modal-content">
				<header class="modal-header">
					<h5 class="modal-title" id="programLogLabel">Program Log</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">
							&times;
						</span>
					</button>
				</header>
				<div class="modal-body">
					<?php
						if (is_array($logs) && !empty($logs)) {
							$logs = array_reverse($logs);
							echo '<section class="container">';
							echo '<table class="table table-striped text-monospace mt-2">';
							echo '<thead class="thead-dark"><tr><th scope="col" width="20%">Type</th><th scope="col">Message</td></th></thead>';
							echo '<tbody>';
							foreach ($logs as $log) {
								echo '<tr class="table-'.$log['type'].'">';
								echo '<td style="font-size: 0.6em">'.$log['datetime'].'</td>';
								echo '<td>'.$log['message'].'</td>';
								echo '</tr>';
							}
							echo '</tbody>';
							echo '</table>';
							echo '</section>';
						}
					?>
				</div>
				<footer class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">
						Close
					</button>
				</footer>
			</section>
		</div>
	</div>

	<footer class="container border-top mt-4 pt-2">
		<h6 class="text-muted text-center text-uppercase">
			<small>
				Developed by <a href="mailto:kwilliams@roarmedia.com?subject=Inspire305 Tools&cc=webmaster@roarmedia.com" title="Contact the developer" target="_blank">K. Williams</a> exclusively for use by <a href="https://roarmedia.com" title="Go to: RoarMedia.com" target="_blank">Roar Media</a> and <a href="https://inspire305.org" title="Go to: Inspire305.org" target="_blank">Inspire305</a>. All Rights Reserved.
			</small>
		</h6>
	</footer>
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script type="text/javascript">
		$('#get_all').change(function() {
			if ($(this).is(':checked')) {
				$('.custom-control-input').prop('checked', true);
			} else {
				$('.custom-control-input').prop('checked', false);
			}
		});
		$('input').change(function() {
			var fileReady = false;
			var inputReady = false;
			var outputReady = false;
			if (!$('.form-control-file').val()) {
				fileReady = false;
			} else {
				fileReady = true;
			}
			for (var i = 0; i < $('.form-control').length; i++) {
				var val = $('.form-control').eq(i).val();
				if (val == '') {
					inputReady = false;
					break;
				} else {
					inputReady = true;
				}
			}
			if (!$('.custom-control-input:checked').length) {
				outputReady = false;
			} else {
				outputReady = true;
			}
			console.log(fileReady, inputReady, outputReady);
			if (fileReady == true && inputReady == true && outputReady == true) {
				$('button').prop('disabled', false);
			} else {
				$('button').prop('disabled', true);
			}
		});
	</script>
</body>
</html>