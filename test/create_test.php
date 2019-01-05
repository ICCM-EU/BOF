<?php
# create array for javascript cypress tests for the participants votes

$number_of_participants=60;
$number_of_topics=15;

$votes_full = array();
$votes_quarter = array();
$votes_facilitator = array();

// init topics
for ($t = 0; $t < $number_of_topics; $t++) {
  $votes_full[$t] = array();
  $votes_quarter[$t] = array();
  $votes_facilitator[$t] = array();
}

for ($user = 0; $user < $number_of_participants; $user++) {
  // full votes
  for ($v = 1; $v <= 3; $v++)
  {
    $r = rand(0, $number_of_topics-1);
    if (!in_array($user, $votes_full[$r])) {
      $votes_full[$r][] = $user;
    } else {
      $v--;
    } 
  }  
  // quarter votes
  for ($v = 1; $v <= 5; $v++)
  {
    $r = rand(0, $number_of_topics-1);
    if (!in_array($user, $votes_full[$r]) && !in_array($user, $votes_quarter[$r])) {
      $votes_quarter[$r][] = $user;
    } else {
      $v--;
    } 
  }  
}

echo "var votes_full = [\n";
for ($t = 0; $t < $number_of_topics; $t++) {
  echo "  // topic ".($t+1)."\n";
  echo "  [";
  $first = true;
  foreach ($votes_full[$t] as $u) {
    if (!$first) echo ", ";
    $first = false;
    echo $u;
  }
  echo "  ],\n";
}
echo "]\n";
echo "var votes_quarter = [\n";
for ($t = 0; $t < $number_of_topics; $t++) {
  echo "  // topic ".($t+1)."\n";
  echo "  [";
  $first = true;
  foreach ($votes_quarter[$t] as $u) {
    if (!$first) echo ", ";
    $first = false;
    echo $u;
  }
  echo "  ],\n";
}
echo "]\n";


  
?>
