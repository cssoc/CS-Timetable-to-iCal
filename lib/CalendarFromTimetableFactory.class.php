<?php 

class CalendarFromTimetableFactory {

  public static function build(Timetable $timetable) {
  	$calendar = new Calendar();
  	
  	$subjects = $timetable->getSubjectEvents();
  	
  	/* @var $subject Subject */
  	foreach($subjects as $subject) {
  		
  		// Create event for each subject
  		$event = new CalendarEvent();
  		
  		// Title (including id if we have it)
  		if($subject->getID() != $subject->getTitle())
  			$event->setTitle(sprintf('[%s] %s', $subject->getID(), $subject->getTitle()));
  		else 
  			$event->setTitle($subject->getID());
  		
  		// Description
  		$description = '';
  		$description .= empty($subject->getTitle()) ? '' : sprintf('Subject: %s\n', $subject->getTitle());
  		$description .= empty($subject->getID()) ? '' : sprintf('Course Code: %s\n', $subject->getID());
  		$description .= empty($subject->getGroups()) ? '' : sprintf('Groups: %s\n', implode(", ", $subject->getGroups()));
  		$description .= empty($subject->getWeekInfo()) ? '' : sprintf('Week: %s\n', $subject->getWeekInfo());
  		$description .= empty($subject->getDates()) ? '' : sprintf('\nOccurrences: %s\n', '\n - '.implode('\n - ', $subject->getDates('l jS F Y')));
  		$event->setDescription($description);
  		
  		// Location
  		$event->setLocation($subject->getLocation());
 
  		// Add an event for every occurance
  		foreach($subject->getDates() as $date) {
  			$dateEvent = clone $event;

  			$dateEvent->setStartDate($date);
  			$dateEvent->setStartTimeString($subject->getStartTime());
  			
  			$dateEvent->setEndDate($date);
  			$dateEvent->setEndTimeString($subject->getEndTime());
  			
  			$calendar->addEvent($dateEvent);
  		}
  		
  	}
  	
  	return $calendar;
  }
   
}

?>
