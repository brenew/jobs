<?php

namespace mercurypress;

/**
 * Get Open Jobs Shortcode
 */
function get_open_jobs_shortcode() {	
	$endpoint = 'https://api.resumatorapi.com/v1/jobs/status/open?apikey=m6JG2AJasdNl6JIdMdG1bfUaVTpP6VDz';
	
	$request = wp_remote_get( $endpoint );

	if( is_wp_error( $request ) ) {
		$error_string = $request->get_error_message();
		return 'Service Error: '.$error_string; // Bail early
	}
	
	$body = wp_remote_retrieve_body( $request );
	
    $output = '<div class="jobs-list">';
	$data = json_decode( $body );

	if (is_array ($data) ){
		if(empty($data)){
			$output .= '<div class="jobs-list--job band">';
			$output .= '<div class="jobs-list--inner">';
			$output .= '<p class="jobs-list--meta">';
			$output .= 'Thank you for your interest in career opportunities at MercuryWorks.</br></br>';
			$output .= 'Currently, we don\'t have any open positions. However, we are always eager to meet talented professionals who would like to join our team.</br></br>';			
			$output .= 'If you are interested in possible future opportunities please send your resume to <a href="mailto:hr@mercuryworks.com">hr@mercuryworks.com</a> and don\'t forget to check back often.';
			$output .= '</p></div></div>';
		}
		else{
			foreach( $data as $job ) {
				$output .= formatJobPosting($job);
			}
		}
	} else {
		$output .= formatJobPosting($data);
	}
	
	$output .= '</div>';
	return $output;
  }
add_shortcode('get_open_jobs', __NAMESPACE__ . '\get_open_jobs_shortcode');

/**
* Format a job for the available careers page
*/
function formatJobPosting( $job ) {
	
	$time = time();
	$date = date_create($job->original_open_date);
	$formattedDate = date_format($date,"l, F d, Y");
	
	$output = '<a href="../available-careers/details?job='.$job->id.'&unique='.$time.'" class="jobs-list--job band" data-settings="{"animation":"fadeInUp"}">';
	$output .= '<div class="jobs-list--inner">';
	$output .= '<h2 class="jobs-list--title">'.$job->title.'</h2>';
	$output .= '<p class="jobs-list--meta">'.$job->type.' / '.$job->department.'</p>';
	$output .= '<div class="jobs-list--details">View Details &amp; Apply <span class="fa fa-arrow-right"></span></div>';
	$output .= '</div>';
	$output .= '</a>';
	
	return $output;
}

/**
 * Get Job Details Shortcode
 */
function get_job_details_shortcode() {
	
    $jobId =  $_GET['job'];
	
	$endpoint = '';
	$args = array(
		'timeout'     => 10,
	); 
	$time = time();
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$endpoint = 'https://api.resumatorapi.com/v1/jobs/'.$jobId.'?apikey=m6JG2AJasdNl6JIdMdG1bfUaVTpP6VDz&unique='.$time;
	} else {
		$endpoint = 'http://api.resumatorapi.com/v1/jobs/'.$jobId.'?apikey=m6JG2AJasdNl6JIdMdG1bfUaVTpP6VDz&unique='.$time;
	}
	
	$request = wp_remote_get( $endpoint, $args );

	if( is_wp_error( $request ) ) {
		$error_string = $request->get_error_message();
		return 'Service Error: '.$error_string; // Bail early
	}

	$body = wp_remote_retrieve_body( $request );
	$job = json_decode( $body );

	// UserId of the hiring manager
	$userId = $job->hiring_lead;

	// Get Hiring manager information
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		$endpoint = 'https://api.resumatorapi.com/v1/users/'.$userId.'?apikey=m6JG2AJasdNl6JIdMdG1bfUaVTpP6VDz&unique='.$time;
	} else {
		$endpoint = 'http://api.resumatorapi.com/v1/users/'.$userId.'?apikey=m6JG2AJasdNl6JIdMdG1bfUaVTpP6VDz&unique='.$time;
	}

	$request = wp_remote_get( $endpoint, $args );

	if( is_wp_error( $request ) ) {
		$error_string = $request->get_error_message();
		return 'Service Error: '.$error_string; // Bail early
	}

	$body = wp_remote_retrieve_body( $request );
	$manager = json_decode( $body );

	$date = date_create($job->original_open_date);
	$formattedDate = date_format($date,"l, F d, Y");
	
    $output = '';
	$output .= '<div class="job-detail">';
	
	$output .= '    <div class="job-detail--wrap job-detail--overview" id="job_overview">';
	$output .= '        <div class="job-detail--inner">';
	$output .= '            <h2 class="job-detail--job-title">'.$job->title.'</h2>';
	$output .= '            <p class="job-detail--job-meta">'.$job->type.' / '.$job->department.'</p>';
	$output .= '        </div>';
	$output .= '    </div>';
	$output .= '    <div class="job-detail--wrap" id="job_details">';
	$output .= '        <div class="job-detail--inner">';
	$output .= '            <div class="job-detail--description">'.$job->description.'</div>';
	$output .= '        </div>';
	$output .= '    </div>';
	$output .= '</div>';

	$output .= '<div class="job-detail--wrap job-detail--application" id="job_application">';
	$output .= '	<div class="job-detail--inner">';
	$output .= '		<h3 class="job-detail--form-title">Apply For The ' . $job->title . ' Position</h3>';
	$output .= 			do_shortcode( '[gravityform id=7 ajax=false title=false description=false field_values=\'manager='.$manager->email.'\']' );
	$output .= '	</div>';
	$output .= '</div>';

	return $output;
  }
add_shortcode('get_job_details', __NAMESPACE__ . '\get_job_details_shortcode');

/**
 * Send Application form to Jazz
 */
function job_apply_to_api( $entry, $form ) {
	
    $jobId = '';
	
    $jobId .=  $_GET['job'];
	
	//PC::debug($entry['12']);
	// var_dump($entry['12']);
	
	$c =  base64_encode(file_get_contents($entry['12']));
	
	//PC::debug($c);
	
	$api_url = 'https://api.resumatorapi.com/v1/applicants';
	$body = array(
		'apikey'              	=> 'm6JG2AJasdNl6JIdMdG1bfUaVTpP6VDz',
		'job'              		=> $jobId,
		'first_name'            => $entry['4'],
		'last_name'             => $entry['5'],
		'email'               	=> $entry['2'],
		'phone'               	=> $entry['3'],
		'referral'            	=> $entry['7'],
		'date'                	=> date( 'Y-m-d' ),
		'linkedin'             	=> $entry['8'],
		'twitter'             	=> $entry['9'],
		'website'             	=> $entry['10'],
		'wmyu'             		=> $entry['11'],
		'base64-resume'         => $c,
		'status'              	=> '1',
	);
	$body = json_encode($body);
	// var_dump($body);
	//testing-> PC::debug($body); 
	
	$request = wp_remote_post( $api_url, array(
		'headers'   => array('Content-Type' => 'application/json; charset=utf-8'), 
		'body' 		=> $body ) 
	);
	
	$body = wp_remote_retrieve_body( $request );
	
	$data = json_decode( $body );
	// var_dump($data);
	//PC::debug($data);
}
add_action( 'gform_after_submission_7', __NAMESPACE__ . '\job_apply_to_api', 10, 2 );
