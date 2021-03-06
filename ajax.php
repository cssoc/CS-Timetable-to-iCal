<?php

include_once 'timetable.php';

// Simple autoloader
function autoloadLib($className) {
	$filePath = 'lib/' . $className . '.class.php';
	if (file_exists($filePath)) {
		require_once $filePath;
		return true;
	}
	return false;
}
spl_autoload_register('autoloadLib');

// Include twig
require_once 'Twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, array(
		'cache' => 'cache/Twig',
		'debug' => false
));

// Use sessions to tidy the get request
session_start();

// Load all timetables and get the request page
$_PAGE = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
$timetables = getCachedTimetablesList();

// Load the appropriate page - this should be redone
// Definitely needs to be redone now it's a 100 line if/else...
if($_PAGE == 1) {
	$variables = array('page' => 2);
	
	echo $twig->render("page1.twig", array('var' => 'year', 'vars' => $variables, 'timetables' => $timetables));
} elseif($_PAGE == 2 && isset($_GET['year'])) {
	
	// Save in session
	$_SESSION['year'] = $_GET['year'];
	
	// Extract the session array	
	extract($_SESSION, EXTR_PREFIX_ALL, "S");
	
	// Check it exists
	if(isset($timetables[$S_year]))
		$timetable = $timetables[$S_year];
	else {
		echo $twig->render("error.twig", array('message' => "Unable to find $S_year."));
		die();
	}
	
	$variables = array('page' => 3);
	
	echo $twig->render("page1.twig", array('var' => 'grp', 'vars' => $variables, 'timetables' => $timetable));
	
} elseif($_PAGE == 3 && isset($_SESSION['year']) && isset($_GET['grp'])) {
	
	// Save in session
	$_SESSION['grp'] = $_GET['grp'];
	
	// Extract the session array
	extract($_SESSION, EXTR_PREFIX_ALL, "S");
	
	// Check it exists
	if(isset($timetables[$S_year][$S_grp]))
		$timetable = $timetables[$S_year][$S_grp];
	else {
		echo $twig->render("error.twig", array('message' => "Unable to find group $S_grp in $S_year."));
		die();
	}
	
	$variables = array('page' => 4);
	
	echo $twig->render("page1.twig", array('var' => 'sem', 'vars' => $variables, 'timetables' => $timetable));
} elseif($_PAGE == 4 && isset($_SESSION['year']) && isset($_SESSION['grp']) && isset($_GET['sem'])) {
	
	// Save in session
	$_SESSION['sem'] = $_GET['sem'];
	
	// Extract the session array
	extract($_SESSION, EXTR_PREFIX_ALL, "S");
	
	// Check it exists
	$timetable = "";
	try {
		$timetable = getTimetableFor($S_year, $S_grp, $S_sem);
	} catch(Exception $e){};
	
	if(empty($timetable)) {
		echo $twig->render("error.twig", array('message' => "Unable to find semester $SS_em for group $S_grp in $S_year."));
		die();
	}
	
	$variables = array('page' => 5);
	
	echo $twig->render("page4.twig", array('vars' => $variables, 'subjects' => $timetable->getSubjects()));
	
} elseif($_PAGE == 5 && isset($_SESSION['year']) && isset($_SESSION['grp']) && isset($_SESSION['sem'])) {
	if(empty($_POST['subject'])) {
		echo $twig->render("error.twig", array('message' => "You didn't choose any subjects!"));
		die();
	}
	
	// Extract the session array
	extract($_SESSION, EXTR_PREFIX_ALL, "S");	
	
	$timetable = getTimetableFor($S_year, $S_grp, $S_sem);
	$disabledSubjects = array_diff_key($timetable->getSubjects(), array_flip($_POST['subject']));
	$timetable->excludeSubjects($disabledSubjects);
	
	$_SESSION['excludedSubjects'] = $disabledSubjects;

	$variables = array('page' => 6);
	
	echo $twig->render("page5.twig", array('vars' => $variables, 'table' => $timetable->getTimetableTableArray()));
	
} elseif($_PAGE == 6 && isset($_SESSION['year']) && isset($_SESSION['grp']) && isset($_SESSION['sem']) && isset($_SESSION['excludedSubjects'])) {
	
	// Extract the session array
	extract($_SESSION, EXTR_PREFIX_ALL, "S");
	
	// Get our timetable
	$timetable = getTimetableFor($S_year, $S_grp, $S_sem);
	$timetable->excludeSubjects($S_excludedSubjects);
	
	// Convert to a calendar
	$calendar = null;
	try {
		$calendar = CalendarFromTimetableFactory::build($timetable);
	} catch (Exception $e) {
		echo $twig->render("error.twig", array('message' => "Something failed while converting your timetable to a .ics file", 'detail' => array($e->getTraceAsString())));
		die();
	}
	
	// Destroy session by wiping the cookie
	if(isset($_GET['wipe'])) {
		$cookieParams = session_get_cookie_params();
		setcookie(session_name(), '', 0, $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);
		session_destroy();
	}
	
	$calendar->setTitle("CS-Calendar_".implode('-', array($S_year, $S_grp, $S_sem)));
	
	// Force download the file
	$calendar->downloadVCalendar();

} elseif($_PAGE == 7) {
	echo $twig->render("page7.twig");
		
} else {

	echo $twig->render("error.twig", array('message' => "Something failed while converting your timetable to a .ics file",
																				 'detail' => array(var_export($_SESSION, true), var_export($_POST, true), var_export($_GET, true))
																				));
	die();
}

